# Friction log: tuxonice/storyblok-transfers

One entry per surprise, workaround or bit of glue code the package caused.
Format: what I tried, what happened, what I did instead, suggested change.

## 1. Generated classes fail Laravel Pint

- **Tried:** run `make lint` (Pint with the Laravel preset) after `storyblok:generate`.
- **Happened:** all 16 generated `*Transfer.php` files fail (`class_attributes_separation`, `phpdoc_*`,
  `no_superfluous_phpdoc_tags`, `no_empty_phpdoc`).
- **Did instead:** excluded `app/Storyblok/Transfers` in `pint.json`. The files are regenerated on every run, so
  formatting them is pointless.
- **Suggestion:** make the generator emit code that passes `@PSR12`/Laravel Pint, or document the exclusion in `laravel.md`.

## 2. Generated classes for components the project no longer uses

- **Tried:** push the new schema into a space that already had components.
- **Happened:** the generator produced 16 classes, five of them for components I did not define (`Config`, `Hero`,
  `NavItem`, `RichtextSection`, `Test`). Known and documented, but it means deleting components in Storyblok is not enough.
- **Did instead:** deleted the old components in Storyblok, then removed their definition JSON by hand and regenerated.
- **Suggestion:** an option such as `--only=post,author,...` or `--prune` that removes stale definition JSON files.

## 3. Version in the handoff differs from the installed version

- **Happened:** CLAUDE.md says first release v0.1.0 (2026-11-09); `composer require` installed `^0.4.0`.
- **Did instead:** used the documentation shipped in `vendor/tuxonice/storyblok-transfers/documentation` as the source of truth.
- **Suggestion:** none for the package; keep the handoff in sync.

## 4. Relation fields come back as bare uuids (expected, worth recording)

- `PostTransfer::getAuthor(): ?string` and `getCategories(): array` (uuids). Resolving them needs `RelationMap` from the
  story envelope. To be exercised in step 4.

## Not package-related (Storyblok CLI), kept here so it is not rediscovered

- The CLI wants `<path>/components/<space-id>/components.json` and `<path>/datasources/<space-id>/datasources.json`;
  `--path storyblok` therefore needs the space id in the directory name.
- `storyblok login --token` needs the `users:read` scope; a token scoped only for the Management API is rejected.
- A wrong-region or no-access login answers 404 ("The requested resource was not found"), not 401/403.
- `storyblok stories push` is built for copying stories between spaces (pulled manifest, old/new ids), so it does not
  fit seeding a fresh space with cross-references. I wrote `php artisan storyblok:push-stories` instead (Management API,
  create-or-update by slug, uuid placeholders).
