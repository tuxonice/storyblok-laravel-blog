# Open work

Written after the nine build steps in `CLAUDE.md` were finished and merged. Items are grouped by who has to act.
Strike through or delete an item when it is done.

## 1. Needs the author (Storyblok and production)

1. **Webhook.** Create it in Storyblok (Settings -> Webhooks) pointing at `/webhooks/storyblok`, and set
   `STORYBLOK_WEBHOOK_SECRET`. Until then the endpoint refuses everything and the cache only refreshes every 5 minutes.
2. **Visual Editor.** Set the preview location to the site's HTTPS URL, and set "Preview path" to `/` for
   `site_settings`, `blog_index` and `author`.
3. **Production environment.** Never deploy `STORYBLOK_MANAGEMENT_TOKEN`. Keep `STORYBLOK_DEFAULT_VERSION=published`.
   Check the trusted-proxy setup: the sitemap, feed and robots.txt build their URLs from the request host.
4. **Real content.** Replace the placeholder posts, author name and bio, and the guessed GitHub link. Add covers and
   avatars: no images exist in the space yet.

## 2. Unverified (could not be tested for real)

- **Webhook signature.** The scheme (hex HMAC-SHA1 of the raw body in `webhook-signature`) comes from the package
  docs. Confirm with a real delivery from Storyblok.
- **Visual Editor.** Draft mode was tested with signed URLs and in a browser, never inside the real editor iframe.
- **Real devices.** The mobile pass used browser emulation only.
- **Storyblok image service.** Open Graph images are resized by appending `/m/1200x630` to the asset URL. This is not
  yet confirmed against a real asset, because the space has no images.

## 3. Gaps against the goal of testing the package

- **Datasources and languages are unused.** Tags show as raw slugs (`#php`) instead of the names in the `tags`
  datasource. A tag page or a second language would exercise both.
- **`FRICTION.md` has 19 entries.** Turn the most useful into issues or a PR on `tuxonice/storyblok-transfers`. Top five:
  1. keep `_editable` and `_uid` on transfers,
  2. merge per-call options onto the defaults,
  3. a `getRelations()` helper,
  4. link and heading helpers (`getHref()`, `getHeadings()`),
  5. per-story cache invalidation.

## 4. Site gaps

- ~~SEO fields ignored.~~ Meta description, Open Graph, Twitter cards, canonical URLs and JSON-LD now use
  `seo_title`, `seo_description` and `og_image`.
- ~~`robots.txt`.~~ Now generated: it points at the sitemap and blocks everything outside production.
- **Undesigned pages.** 404 and error pages are Laravel's defaults, including the 500 shown when Storyblok is down
  with an empty cache. There is no author page, no search and no tag pages.
- **Author data.** The author card is plain: `avatar`, `website` and `github` are stored but not shown, and it does
  not link anywhere.
- **Reading time.** Every listing loads each post's full body to compute it (`FRICTION.md` entry 6). Storing it as a
  field would avoid that.
- **No `/posts` page.** The `~/posts` breadcrumb and the "all posts" link both go to the home page.
- **Accessibility.** No Lighthouse or accessibility audit has been run.

## 5. Project hygiene

- ~~README.~~ Written: setup, configuration, Storyblok space setup, architecture, development and production notes.
- ~~CI.~~ `.github/workflows/ci.yml` runs Pint, `composer validate --strict` and the tests on PHP 8.4 and 8.5;
  `audit.yml` audits dependencies weekly; Dependabot keeps dependencies and the pinned actions current.
- **Confirm the first CI run.** The workflows have not run on GitHub yet. The same steps passed in a clean clone on
  PHP 8.4 and 8.5, and the YAML parses and pins every action to a commit SHA, but `actionlint` was not run. Check the
  first pull request's Actions tab, and make `Code style` and `Tests` required checks in the branch protection rules.
- ~~LICENSE file.~~ Added (MIT, Helder Correia, 2026).
- ~~PHP version.~~ `composer.json` now requires `^8.4`, matching the lock file (Symfony 8 needs 8.4.1 or newer), CI
  and the README.
- **Security headers.** There is no CSP or similar beyond the draft-mode `frame-ancestors`.
- **Deployment.** The README lists production steps (`php artisan optimize`, asset build), but no deploy pipeline or
  server configuration exists.
