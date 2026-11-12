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

## 5. Per-call options replace the defaults as a whole

- **Tried:** configure `StoryblokContent` with `Version::Draft` as the default, then read with
  `(new ContentOptions())->withResolveRelations([...])`.
- **Happened:** that call silently reads the *published* version again, because the per-call `ContentOptions` replaces
  the defaults instead of merging into them. Documented, but an easy way to lose draft mode on exactly the calls that
  resolve relations.
- **Did instead:** `Blog::options()` always builds `new ContentOptions(Version::from(config('storyblok.version')))` and
  every call starts from it.
- **Suggestion:** a `StoryblokContent::options()` that returns a copy of the defaults, or merge per-call options onto
  the defaults.

## 6. Reading time needs the full body, so listings cannot drop it

- **Tried:** keep home listings light with `excludingFields: ['body']`.
- **Happened:** read time is computed from the richtext, so the listing has to load every post's body.
- **Did instead:** load bodies in listings (10 per page, cached for five minutes).
- **Suggestion:** none for the package; this is a consequence of "read time is not stored". Storing it as a field
  would be the alternative.

## 7. Relations: a loop per field, and a `StoryTransfer` check every time

- **Tried:** show a post's author and categories.
- **Happened:** `getRelation()` returns `StoryTransfer|array|null` and takes one uuid, so each multi-reference field
  is a loop with an `instanceof StoryTransfer` check, and `getContent()` is typed as the generic transfer.
- **Did instead:** one `post()` mapper in `App\Blog\Blog` does the loops and narrows the types.
- **Suggestion:** `getRelations(array $uuids): list<StoryTransfer>` (skipping unresolved ones), and a typed variant such as
  `getRelation($uuid, AuthorTransfer::class)`.

## 8. No ready-made fake for tests

- **Tried:** feature-test the routes without the network.
- **Happened:** `ContentClient` is a clean seam, but I had to write about 120 lines
  (`tests/Support/FakeContentClient.php`) to emulate story-by-slug, story-by-uuid, listings and `resolve_relations`.
- **Suggestion:** ship an in-memory `ArrayContentClient` (or a `StoryblokContent::fake()`) for application tests.

## 9. Turning a `LinkTransfer` into an href

- **Tried:** render nav links from `nav_link` bloks.
- **Happened:** a `multilink` can be `story`, `url`, `email` or `asset`, each with its own field to read, so I wrote a
  `match` on `getLinktype()` (`Blog::href()`).
- **Suggestion:** `LinkTransfer::getHref()` that does this, with story links as a root-relative path.

## 10. Embedded blocks: `getBloks()` is keyed by blok id and holds lists

- **Tried:** turn richtext `blok` nodes into rendered components.
- **Happened:** the node carries an id and `getBloks()[$id]` is a *list* of components (one blok node can hold several),
  so every render site looks the id up and loops. It works and is documented; it just takes glue
  (`App\Blog\RichtextBlocks`, temporary until step 5).
- **Suggestion:** a walk helper that yields `[node, components]` pairs, so renderers do not index by id themselves.

## 11. Heading anchors and the index have to come from your own walk

- **Tried:** build the "on this page" index and heading anchors from the richtext.
- **Happened:** the package hands over the raw node tree, so collecting headings, slugifying, de-duplicating ids and
  counting words all live in my own renderer (`App\\Blog\\RichtextRenderer`). I wrote the renderer instead of adding
  `storyblok/richtext-resolver`, because anchors, the index and the embedded blocks all need hooks into the same walk.
- **Suggestion:** a small helper on `RichtextTransfer`, such as `getHeadings(int $level = 2): list<array{text: string}>`
  and `getText(): string` (plain text for reading time and excerpts). The renderer could then be a thin layer.

## 12. The links endpoint can return dates, but the package cannot ask for them

- **Tried:** a sitemap with `<lastmod>` from `links()->all()`.
- **Happened:** Storyblok's links endpoint returns `published_at`, `created_at` and `updated_at` when called with
  `include_dates=1`. `LinkRepository::all()` only sends what `ContentOptions` produces, so there is no way to pass it,
  and `LinkEntry` has no date getters.
- **Did instead:** the sitemap takes URLs from the links tree (one request) and `lastmod` for posts from a second,
  body-less story listing (`excludingFields: ['body']`). Other pages get no `lastmod`.
- **Suggestion:** `ContentOptions::withDates()` (or an argument on `links()->all()`) and `LinkEntry::getPublishedAt()`.

## 13. `links()->all()` has no pagination

- **Tried:** read the whole links tree for the sitemap.
- **Happened:** `all()` makes a single request. The endpoint documents `paginated=1`, `page` and `per_page`, but the
  package sends none of them, and the docs I could find do not state the size of an unpaginated response, so I cannot
  say at what site size it starts to truncate. (Unverified; irrelevant for this blog, relevant for a large space.)
- **Suggestion:** have `all()` walk the pages the way `datasources()->entries()` already does ("every entry is
  returned; the package fetches all pages for you").

## 14. Cache invalidation is all-or-nothing

- **Tried:** purge only what a publish changed (the checked-in question: is there an explicit purge-on-publish?).
- **Happened:** there is no per-key purge. `clearCache()` empties the whole cache it was given, so every webhook
  (including a typo fix in one post) throws away every cached read, and the next visitors pay for all of them. Fine for
  a small blog; with many stories it means a burst of requests against the 50 req/s Delivery API limit after each publish.
- **Did instead:** a dedicated `storyblok` file cache store, so clearing it cannot touch sessions or anything else, and
  `POST /webhooks/storyblok` calls `clearCache()`.
- **Suggestion:** `forget(string $fullSlug)` (story, plus listings and the links tree, which depend on it), or tagging
  entries by story uuid so a webhook payload can invalidate precisely.

## 15. Timings and logger are not reachable through `create()`

- **Tried:** configure the TTLs from `config/storyblok.php` and log when an old copy is served.
- **Happened:** `StoryblokContent::create(..., cache:)` builds the caching client with fixed defaults and no logger, so I
  build `StoryblokContentClient` and `CachingContentClient` by hand and pass them to `usingClient()`
  (`AppServiceProvider`). Documented, but it means the one-liner setup is not enough for production, where you want the
  stale-served warning in your logs.
- **Suggestion:** `create()` accepts `freshFor`, `keepStaleFor` and `logger`, or a small `CacheOptions` value.

## 16. Webhook signature checking is left to the app

- **Tried:** verify Storyblok's webhook signature.
- **Happened:** the package documents the scheme (hex HMAC-SHA1 of the raw body in `webhook-signature`) but ships no
  verifier, so the comparison lives in my controller (`hash_hmac` + `hash_equals`). I took the scheme from the package
  docs; I could not check it against a real signed webhook from this space.
- **Suggestion:** `WebhookVerifier::isValid(string $rawBody, ?string $signature, string $secret): bool`, so everyone
  gets the constant-time comparison right.

## 17. `_editable` and `_uid` are dropped, so no click-to-edit in the Visual Editor

- **Tried:** wire the Storyblok Visual Editor with click-to-edit outlines.
- **Happened:** a draft read from the real API carries `_editable` (the `<!--#storyblok#{...}-->` comment the bridge
  uses to outline a block) and `_uid` on every component. Neither survives hydration: the generated classes have no
  field for them and the package never mentions them. The bridge therefore only gets live reload, not outlines.
- **Did instead:** the bridge reloads the page on `change` and `published`. Editors see saved changes, but cannot click
  a block in the preview to jump to its field.
- **Suggestion:** keep `_uid` and `_editable` on every transfer (`getUid()`, `getEditable()`), so a template can print
  the comment before a block. For the story itself the envelope would need the numeric story id too.

## 18. Choosing draft means choosing a token, and the package leaves both to you

- **Tried:** switch a request to draft content.
- **Happened:** `Version::Draft` only sets `version=draft`; it needs the preview token, which the client is built with,
  so the app has to build a different `StoryblokContent` per mode (`AppServiceProvider` picks the token from
  `DraftMode::active()`). Every per-call `ContentOptions` also has to repeat the version (see entry 5), so
  `DraftMode::version()` exists just to hand the right one to each call.
- **Suggestion:** `StoryblokContent::forDrafts(string $previewToken): self` returning a sibling instance, and merge
  per-call options onto the defaults.

## 19. The HTTP client seam is Guzzle, not PSR-18

- **Tried:** run the real client and cache in tests with the network faked.
- **Happened:** `create(httpClient:)` takes `GuzzleHttp\ClientInterface` and calls its `request()` method, whereas the
  cache argument is a PSR-16 interface. A fake therefore has to be a real Guzzle client with a custom handler
  (`tests/Support/FakeGuzzle.php`), and the app has to depend on Guzzle directly.
- **Suggestion:** accept `Psr\Http\Client\ClientInterface` (PSR-18) plus a PSR-17 request factory, which also removes
  the hard Guzzle dependency.

## 20. The filter operator depends on the field type, and the wrong one silently returns nothing

- **Tried:** list an author's posts with `filterQuery`, as the category page already does.
- **Happened:** categories are a multi-reference field and need `any_in_array`; the author is a single reference and
  needs `in`. Using `any_in_array` on the author field is not an error: against the real space it returned 0 posts, the
  same as a uuid that matches nothing. Only a live check (5 posts with `in`, 0 with `any_in_array`) showed which is right.
- **Did instead:** `Blog::author()` uses `in`, with a comment, and the test fake implements both operators the way
  Storyblok behaves, so the two cannot be confused in tests.
- **Suggestion:** typed helpers on `StoryQuery` that pick the operator, such as `whereReference('author', $uuid)` and
  `whereAnyReference('categories', $uuid)`, instead of a raw `array<string, array<string, string>>`.

## Not package-related (Storyblok CLI), kept here so it is not rediscovered

- The CLI wants `<path>/components/<space-id>/components.json` and `<path>/datasources/<space-id>/datasources.json`;
  `--path storyblok` therefore needs the space id in the directory name.
- `storyblok login --token` needs the `users:read` scope; a token scoped only for the Management API is rejected.
- A wrong-region or no-access login answers 404 ("The requested resource was not found"), not 401/403.
- `storyblok stories push` is built for copying stories between spaces (pulled manifest, old/new ids), so it does not
  fit seeding a fresh space with cross-references. I wrote `php artisan storyblok:push-stories` instead (Management API,
  create-or-update by slug, uuid placeholders).
