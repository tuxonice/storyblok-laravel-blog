#!/usr/bin/env bash
#
# Deploys the blog on the server with one folder per release and an atomic
# switch, so a failed build never takes the site down.
#
#   deploy.sh [branch]   build a release from the branch (default: main) and switch to it
#   deploy.sh rollback   switch back to the previous release
#
# Layout under the base folder (default: the folder this script is in):
#
#   releases/<timestamp>/   one checkout per deploy, the newest KEEP_RELEASES are kept
#   shared/.env             production settings, linked into every release
#   shared/storage/         logs, caches, sessions, linked into every release
#   shared/database/        the SQLite database, linked into every release
#   current -> releases/…   Apache's DocumentRoot is current/public
#
# Settings (environment variables, all optional):
#
#   DEPLOY_PATH     base folder                    default: the script's folder
#   DEPLOY_REPO     git URL to clone               default: https://github.com/tuxonice/storyblok-laravel-blog.git
#   KEEP_RELEASES   releases to keep               default: 5
#   PHP             PHP binary                     default: php
#   COMPOSER        Composer binary                default: composer
#   RELOAD_CMD      run after the switch, e.g. "sudo systemctl reload php8.4-fpm".
#                   PHP's realpath and OPcache caches can keep serving the old
#                   release for a while without it.

set -euo pipefail
umask 002 # group-writable, so the web server's group can write storage

BASE="${DEPLOY_PATH:-$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)}"
REPO="${DEPLOY_REPO:-https://github.com/tuxonice/storyblok-laravel-blog.git}"
KEEP_RELEASES="${KEEP_RELEASES:-5}"
PHP="${PHP:-php}"
COMPOSER="${COMPOSER:-composer}"
RELOAD_CMD="${RELOAD_CMD:-}"

RELEASES="$BASE/releases"
SHARED="$BASE/shared"
CURRENT="$BASE/current"

say() { printf '\033[1;33m==>\033[0m %s\n' "$*"; }
die() { printf '\033[1;31mError:\033[0m %s\n' "$*" >&2; exit 1; }

require() {
    for tool in "$@"; do
        command -v "$tool" >/dev/null 2>&1 || die "'$tool' is not installed or not on PATH."
    done
}

# Only one deploy or rollback at a time.
lock() {
    mkdir -p "$BASE"
    exec 9>"$BASE/.deploy.lock"
    flock -n 9 || die "another deploy is running (lock: $BASE/.deploy.lock)."
}

reload() {
    if [[ -n "$RELOAD_CMD" ]]; then
        say "Reloading: $RELOAD_CMD"
        eval "$RELOAD_CMD"
    else
        say "RELOAD_CMD is not set; PHP may serve the previous release until its caches expire."
    fi
}

switch_to() {
    # A new symlink renamed over the old one: the switch is atomic.
    ln -sfn "$1" "$BASE/current.new"
    mv -Tf "$BASE/current.new" "$CURRENT"
    say "Live: $1"
}

BUILDING=""

cleanup() {
    if [[ -n "$BUILDING" ]]; then
        rm -rf "$BUILDING"
        say "Removed the unfinished release $BUILDING; the live site was not changed."
    fi
}

prepare_shared() {
    mkdir -p "$RELEASES" "$SHARED/database" \
        "$SHARED/storage/app/public" "$SHARED/storage/app/private" \
        "$SHARED/storage/framework/cache/data" "$SHARED/storage/framework/sessions" \
        "$SHARED/storage/framework/views" "$SHARED/storage/logs"
    touch "$SHARED/database/database.sqlite"
}

# First deploy only: start shared/.env from the release's .env.example with
# production values and a fresh key, then stop so the tokens can be filled in.
create_env() {
    local release="$1"

    cp "$release/.env.example" "$SHARED/.env"
    chmod 640 "$SHARED/.env"
    sed -i \
        -e 's/^APP_ENV=.*/APP_ENV=production/' \
        -e 's/^APP_DEBUG=.*/APP_DEBUG=false/' \
        -e 's/^LOG_LEVEL=.*/LOG_LEVEL=warning/' \
        -e 's/^STORYBLOK_DEFAULT_VERSION=.*/STORYBLOK_DEFAULT_VERSION=published/' \
        "$SHARED/.env"
    (cd "$release" && "$PHP" artisan key:generate --force --no-interaction)

    die "created $SHARED/.env. Fill in APP_URL, the Storyblok tokens, STORYBLOK_WEBHOOK_SECRET and ALLOWED_IPS,
       remove STORYBLOK_MANAGEMENT_TOKEN and STORYBLOK_CLI_TOKEN, then run the deploy again."
}

deploy() {
    local branch="$1"
    local release
    release="$RELEASES/$(date +%Y%m%d%H%M%S)"

    require git "$PHP" "$COMPOSER" npm flock
    lock
    prepare_shared

    # Remove the release unless it went live: a failed build leaves the site untouched.
    BUILDING="$release"
    trap cleanup EXIT

    say "Cloning $branch into $release"
    git clone --quiet --depth 1 --single-branch --branch "$branch" "$REPO" "$release"
    git -C "$release" rev-parse HEAD > "$release/REVISION"
    rm -rf "$release/.git"

    say "Linking shared files"
    rm -rf "$release/storage"
    ln -s "$SHARED/storage" "$release/storage"
    ln -sfn "$SHARED/database/database.sqlite" "$release/database/database.sqlite"

    say "Installing PHP packages"
    # Before .env exists, package discovery (and key:generate in create_env) only needs the code.
    (cd "$release" && "$COMPOSER" install --no-dev --prefer-dist --optimize-autoloader \
        --no-interaction --no-progress)

    if [[ ! -f "$SHARED/.env" ]]; then
        ln -s "$SHARED/.env" "$release/.env"
        create_env "$release"
    fi
    ln -s "$SHARED/.env" "$release/.env"

    say "Building assets"
    (cd "$release" && npm ci --ignore-scripts --no-audit --no-fund --loglevel=error && npm run build)
    rm -rf "$release/node_modules"

    say "Migrating the database and caching config, routes and views"
    (cd "$release" \
        && "$PHP" artisan migrate --force --no-interaction \
        && "$PHP" artisan optimize)

    switch_to "$release"
    BUILDING=""
    reload

    say "Removing old releases (keeping $KEEP_RELEASES)"
    local live
    live="$(readlink -f "$CURRENT")"
    find "$RELEASES" -mindepth 1 -maxdepth 1 -type d | sort | head -n "-$KEEP_RELEASES" | while read -r old; do
        [[ "$old" == "$live" ]] || rm -rf "$old"
    done

    say "Deployed $branch at $(cat "$release/REVISION")"
}

rollback() {
    require flock
    lock

    [[ -L "$CURRENT" ]] || die "nothing is deployed yet."

    local live previous
    live="$(readlink -f "$CURRENT")"
    previous="$(find "$RELEASES" -mindepth 1 -maxdepth 1 -type d | sort | grep -B1 -Fx "$live" | head -n 1)"

    [[ -n "$previous" && "$previous" != "$live" ]] || die "there is no release older than $live."

    switch_to "$previous"
    reload
    say "Rolled back to $(cat "$previous/REVISION" 2>/dev/null || basename "$previous")."
    say "Migrations are not rolled back; the database keeps its current schema."
}

case "${1:-main}" in
    rollback) rollback ;;
    -h | --help) sed -n '2,28p' "${BASH_SOURCE[0]}" | sed 's/^# \{0,1\}//' ;;
    *) deploy "${1:-main}" ;;
esac
