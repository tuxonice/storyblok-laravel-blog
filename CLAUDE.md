# Developer blog: Laravel + Storyblok + tuxonice/storyblok-transfers

This file is a handoff from a planning chat on claude.ai. Read it fully before writing code.
Anything marked **(unverified)** was assumed during planning and must be checked against the real docs or code.

## Goal

Build a developer blog with **Laravel + Blade + Bootstrap 5** and **Storyblok** as the CMS.
The blog is also the first real consumer of the author's own package, `tuxonice/storyblok-transfers`,
so the second goal is to find out whether the package is usable in a real project.

Because the package is the author's, **never patch files in `vendor/`**. When something is awkward, add it to
`FRICTION.md` (see below) instead, and propose a change to the package.

## The package

- Repo: https://github.com/tuxonice/storyblok-transfers (MIT, PHP ^8.3, first release v0.1.0 on 2026-11-09)
- Install: `composer require tuxonice/storyblok-transfers`
- It does two things:
  1. **Generate** a typed PHP transfer class per Storyblok component: `vendor/bin/generate`, with
     `STORYBLOK_SPACE_ID` and `STORYBLOK_MANAGEMENT_TOKEN` in the environment.
  2. **Read** stories from the Content Delivery API into those classes, with relations, drafts, languages, links
     and datasources.
- Basic usage from the README:

```php
$storyblok = StoryblokContent::fromEnvironment();           // reads STORYBLOK_DELIVERY_TOKEN
$story = $storyblok->stories()->bySlug('home', PageTransfer::class);
echo $story?->getContent()->getTitle();
```

- Docs live in the repo under `documentation/`. **Read these before coding:** `getting-started.md`, `laravel.md`,
  `generating-transfers.md`, `reading-content.md`, `relations.md`, `rich-text.md`, `links-and-datasources.md`,
  `caching.md`, `limitations.md`, `reference/field-types.md`.

### Known constraints (from the README and PRs)

- Field keys: `headline_2` becomes `headline2`, which the schema rejects, so the field is skipped with a warning.
  Use names like `headline_two`. **Never put a digit after an underscore in a field key.**
- The generator **deletes every `*Transfer.php` and `*TransferImmutable.php`** in its output directory before
  writing. Use a dedicated output directory (for example `app/Storyblok/Transfers`) and put no hand-written classes there.
- Deleting a component in Storyblok does not delete its definition JSON, so its class keeps being generated.
  Remove the definition file by hand.
- Array properties default to `[]` and are never null. Everything else is nullable, so templates must use `?->` or `??`.
- A `bloks` field can hold more than its declared type says. Check with `instanceof` in Blade.
- Resolved relations come back in a `RelationMap` next to the content. The generated property stays a uuid string.
- Caching: pass a PSR-16 cache to `StoryblokContent::create()` or `fromEnvironment()`. Published reads are cached for
  5 minutes, a stale copy is served for up to a day if a refresh fails, drafts are never cached, and "not found"
  is remembered for the fresh window. **(unverified)** whether there is an explicit purge-on-publish. Check `caching.md`.
- The Storyblok Content Delivery API allows 50 uncached requests per second. Page size above 25 lowers that.

## Design (approved)

Direction: **developer blog, dark by default**, own look (no external brand system). Mockups were made as a
canvas with two pages (home, post) and are described here because Claude Code cannot open the link.

### Tokens

| Token | Value | Use |
|---|---|---|
| page background | `#0e1116` | body |
| code background | `#0a0d12` | code blocks |
| surface | `#151a21` | cards, note box, author card |
| border | `#262d37` | all hairlines |
| border strong | `#323a46` | category chips |
| dashed / placeholder | `#3a424d` | cover placeholder, note box border |
| text | `#e8ecf1` | headings, active nav |
| body text | `#cfd6df` | article paragraphs |
| muted | `#9aa4b2` | dates, excerpts, inactive nav |
| disabled | `#8791a0` | "← newer" when no page |
| accent | `#f5a524` | primary button background (with `#0e1116` text), eyebrow labels, mono links |
| code: variables | `#7cc4ff` | `$var`, Blade directives |
| code: classes | `#ffcf70` | class names, `x-` components |
| code: strings | `#9be3a6` | string literals |

- Fonts: **Inter** (400, 500, 600, 700) for text, **JetBrains Mono** (400, 500) for dates, tags, nav, code and
  small labels. Self-host both in the real build.
- Radius: 8px for buttons, chips, cards, code blocks and the theme toggle. Avatars are fully round.
- 1px borders, no shadows, no gradients, no emoji. Hover is `opacity: 0.75`. Interactive targets are at least 44px tall.
- Text contrast is at least 4.5:1 on the background. Keep it that way if you change shades.
- Container: max width 1140px (Bootstrap `container-xl`) with 24px side padding. Layouts wrap on narrow screens.

### Bootstrap 5 mapping

Use Sass with `data-bs-theme="dark"` as the default and override variables rather than fighting classes:

```scss
$body-bg: #0e1116;
$body-color: #e8ecf1;
$primary: #f5a524;
$border-color: #262d37;
$border-radius: 0.5rem;
$font-family-sans-serif: "Inter", system-ui, sans-serif;
$font-family-monospace: "JetBrains Mono", ui-monospace, monospace;
$link-color: #f5a524;
```

The theme toggle is drawn in the mockup, but **no light palette has been designed yet**. Build dark first, then
propose a light palette with the same structure and ask the author to approve it.

### Pages

**Header** (all pages): mono wordmark `your-blog_` (the underscore is accent), nav `posts / categories / about / rss`
in mono 14px, round theme-toggle button. The site name comes from `site_settings`.

**Home**
1. Featured post: mono eyebrow `// featured`, H1 48px/1.1 weight 700 with -0.02em tracking, excerpt 18px muted,
   meta line in mono (`date · N min read · #tag #tag`), amber pill-less button "Read the post" with an arrow icon.
   Beside it, a code panel (filename strip plus highlighted PHP) showing how the page reads the post.
2. "Latest posts": list rows with a hairline between them. Each row has a mono date column (104px), the title
   (22px, 600), a muted excerpt, and a mono line `#tag #tag · N min read`. The whole row is one link.
   Mono "all posts" link at the top right, "← newer / older →" pagination at the bottom.
3. Sidebar (260-320px): `// about` text, `// categories` as chips, `// subscribe` RSS link.
4. Footer: "Built with Storyblok, Laravel and tuxonice/storyblok-transfers", links to rss, sitemap and GitHub.

**Post**
1. Breadcrumb `~/posts / slug` in mono, H1 56px, meta row (avatar initials, author name, date, read time, `#tags`),
   400px cover image.
2. Article column max 700px, paragraphs 18px/1.8 in body text color, H2 30px. To its right, an "on this page" index
   (mono label, links to the H2 anchors).
3. Embedded blocks: code panel (filename strip, mono 14px), note box (mono label plus text, surface background,
   dashed-strong border), pull quote (28px, weight 600, hairlines above and below, no side border).
4. Author card (avatar, name, bio), then "Keep reading" with three post cards in a row.

**Not designed yet:** category page, author page, about page, 404, search, light theme, mobile layouts
(the pages are built to stack, but were not reviewed at phone width).

## Content model (Storyblok)

Folders: `posts/`, `authors/`, `categories/`, plus root stories `home`, `about`, `settings`.

| Component | Type | Fields |
|---|---|---|
| `post` | content type | `title` (text, required), `excerpt` (textarea), `cover_image` (asset, images), `published_at` (datetime), `author` (reference to `author`, one), `categories` (reference to `category`, many), `tags` (options from datasource `tags`), `body` (richtext with embedded blocks), `seo_title`, `seo_description`, `og_image` |
| `author` | content type | `name`, `role`, `bio` (textarea), `avatar` (asset), `website` (multilink), `github` (text) |
| `category` | content type | `name`, `description` |
| `blog_index` | content type | `intro` (textarea), `featured_post` (single reference to `post`), `posts_per_page` (number) |
| `page` | content type | `title`, `body` (richtext) |
| `site_settings` | content type | `site_name`, `nav` (blocks of `nav_link`), `footer_links` (blocks of `nav_link`), `default_og_image` |
| `nav_link` | nestable | `label`, `link` (multilink) |
| `code_block` | nestable, embedded in body | `filename`, `language`, `code` (textarea) |
| `callout` | nestable, embedded in body | `label`, `text` |
| `pull_quote` | nestable, embedded in body | `text` |
| `image_figure` | nestable, embedded in body | `image`, `caption` |

Notes:
- Reading time is computed in Laravel, not stored.
- Categories are references (tests relations). Tags are a datasource (tests datasources).
- Whether the schema is created by hand in Storyblok or pushed with the Storyblok CLI is still open.
  Component definitions are not written yet.

## Laravel structure (proposal)

- `config/storyblok.php`, env keys `STORYBLOK_SPACE_ID`, `STORYBLOK_DELIVERY_TOKEN`, `STORYBLOK_PREVIEW_TOKEN`,
  `STORYBLOK_MANAGEMENT_TOKEN` (the last one only for generating, never deployed to production).
- A service provider binding `StoryblokContent` as a singleton with a Laravel cache store (follow `laravel.md`).
- Generated classes in `app/Storyblok/Transfers/` (nothing hand-written there), plus a composer script
  `composer storyblok:generate`.
- Routes: `/` (home), `/posts/{slug}`, `/categories/{slug}`, `/about`, `/feed.xml`, `/sitemap.xml`,
  `/webhooks/storyblok` (signature verified).
- Blade: `layouts/app.blade.php`, `posts/index`, `posts/show`, and `components/blocks/{code_block,callout,pull_quote,image_figure}.blade.php`.
- The "read time", "on this page" index and heading anchors have to be derived from the richtext JSON.

## Build order

1. Laravel 11+ project (match the PHP 8.3+ requirement), Vite, Bootstrap 5 with the Sass overrides above, fonts.
2. Static layout in Blade with the final markup and **hard-coded sample data**, so the design is right before any CMS work.
3. Create the Storyblok space and schema. Run the generator. Commit the generated classes.
4. Home and post pages reading real content. Relations: author and categories.
5. Richtext with embedded blocks and the heading index.
6. Category pages, pagination, RSS, sitemap (use the links endpoint).
7. Caching, then webhook invalidation (see the caching note above).
8. Draft mode and the Storyblok Visual Editor bridge (preview token, `_storyblok` query params).
9. Light theme, mobile pass, then optionally a second language.

## Working rules for Claude Code

- Check every generated method name against the real generated class. The Blade snippets in the mockups
  (`getBody()`, a block `name()` helper) are guesses **(unverified)**.
- All titles, dates, read times, author names and bios in the mockups are sample content. Replace them with real content.
- Keep a `FRICTION.md` at the repo root. Every time the package forces glue code, a workaround or a surprise, add one entry:
  what you tried, what happened, what you did instead, and a suggested change to the package. Likely areas:
  embedded blocks in richtext, relation lookups through `RelationMap`, heading anchors, datasource tags,
  the links endpoint for the sitemap, draft mode, cache invalidation.
- Run the generator after every schema change, and run the tests (add Pest or PHPUnit feature tests for the routes).
- Ask before adding dependencies beyond Laravel, Bootstrap, Vite and the package's own requirements.
