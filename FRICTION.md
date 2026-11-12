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

## Not package-related (Storyblok CLI), kept here so it is not rediscovered

- The CLI wants `<path>/components/<space-id>/components.json` and `<path>/datasources/<space-id>/datasources.json`;
  `--path storyblok` therefore needs the space id in the directory name.
- `storyblok login --token` needs the `users:read` scope; a token scoped only for the Management API is rejected.
- A wrong-region or no-access login answers 404 ("The requested resource was not found"), not 401/403.
- `storyblok stories push` is built for copying stories between spaces (pulled manifest, old/new ids), so it does not
  fit seeding a fresh space with cross-references. I wrote `php artisan storyblok:push-stories` instead (Management API,
  create-or-update by slug, uuid placeholders).
