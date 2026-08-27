---
name: seo
description: >-
  Use when working on SEO: meta tags, canonical/OpenGraph/Twitter metadata, the
  sitemap, robots.txt, redirects, or slugs on properties/pages/posts. Trigger
  phrases: "meta tags", "canonical URL", "open graph", "sitemap", "robots.txt",
  "add a redirect", "change a slug", "SEO settings". Grounds work in the real
  SeoMetadata morph, the SitemapService, and RedirectMiddleware.
---

# Purpose
SEO is powered by a polymorphic `SeoMetadata` morph, a real `SitemapService`, a
dynamic `robots.txt`, and a redirect table enforced by middleware. This skill
keeps agents from hand-writing head meta, breaking the morph, creating duplicate
URLs, or destabilizing indexed slugs.

# Rules
- CORRECTION to the earlier audit: a sitemap generator DOES exist.
  [`app/Services/SitemapService.php`](app/Services/SitemapService.php) builds the
  XML (cached 24h under key `sitemap.xml`) from published `Property`, `Page`, and
  `Post` records plus static/blog URLs. It is served at `/sitemap.xml` via
  [`SeoController::sitemap`](app/Http/Controllers/SeoController.php) (route name
  `sitemap`). `robots.txt` is served at `/robots.txt` via `SeoController::robots`
  using [`RobotsService`](app/Services/RobotsService.php). Do NOT claim the sitemap
  is missing, and do not add a second sitemap generator.
- `SeoMetadata` ([`app/Models/SeoMetadata.php`](app/Models/SeoMetadata.php)) is
  polymorphic via the `seoable()` morph. Pages/posts/properties attach metadata
  through it. Fields: `meta_title`, `meta_description`, `open_graph` (array),
  `twitter` (array), `canonical_url`, `index_status` (bool). Do not flatten the
  morph or move these onto the parent tables.
- Render meta with the `<x-seo>` component
  ([`resources/views/components/seo.blade.php`](resources/views/components/seo.blade.php)),
  which delegates to [`SeoService`](app/Services/SeoService.php)
  (`metaTagsArray()` / `renderMetaTags()`). Do not hand-write `<title>`/`<meta>` in
  views — reuse the component so canonical/OG/Twitter tags stay consistent.
- Redirects are stored in the `Redirect` model
  ([`app/Models/Redirect.php`](app/Models/Redirect.php)) and enforced by
  [`RedirectMiddleware`](app/Http/Middleware/RedirectMiddleware.php) (cached 1h,
  follows chains with cycle detection, default status 301). Preserve redirect rules
  so old URLs keep working; when you change a slug, add a redirect from the old path.
- Slugs (`properties.slug`, `pages.slug`, `posts.slug`) are indexed lookup columns.
  Keep them stable; regenerate only on explicit request. Public slug PATH SEGMENTS
  (e.g. the `apartments`/`blog` prefixes) are configurable via the `slug()` helper
  ([`app/Helpers/slug.php`](app/Helpers/slug.php)) + admin Slug Settings — use the
  helper, never hardcode those segments.
- Do not create duplicate URLs — route through existing slug/redirect logic; never
  add a parallel URL pattern that can collide with the catch-all `/{page:slug}`
  route (registered last in [`routes/web.php`](routes/web.php)).
- SEO settings admin surface:
  [`resources/views/admin/settings/partials/_seo.blade.php`](resources/views/admin/settings/partials/_seo.blade.php)
  via [`SettingsController`](app/Http/Controllers/SettingsController.php).

# Workflow
1. To attach/edit metadata, use the `seoable` morph on the parent model — not new columns.
2. Render via `<x-seo :model="$model" />` (or pass `:seo`), never hand-written head meta.
3. When changing a slug, add a `Redirect` from old → new and clear the `redirects` cache.
4. After content changes affecting URLs, remember the sitemap is cached 24h
   (`sitemap.xml`) — clear cache if an immediate refresh is needed.

# Common Mistakes
- Claiming there is no sitemap (there is — `SitemapService`).
- Hand-writing `<meta>`/`<title>` instead of using `<x-seo>`.
- Flattening the `SeoMetadata` morph onto parent tables.
- Changing a slug without adding a redirect (breaks indexed URLs).
- Hardcoding public path segments instead of the `slug()` helper.

# Validation
- Visit `/sitemap.xml` and `/robots.txt` — both return valid content.
- Confirm changed slugs have a matching `Redirect` row and old URLs still resolve.
- `php artisan test --filter=CrudTest` / `BlogTest` still pass for slug/SEO areas.

# Related Files
- [`app/Services/SitemapService.php`](app/Services/SitemapService.php), [`app/Services/RobotsService.php`](app/Services/RobotsService.php), [`app/Services/SeoService.php`](app/Services/SeoService.php)
- [`app/Http/Controllers/SeoController.php`](app/Http/Controllers/SeoController.php), [`resources/views/components/seo.blade.php`](resources/views/components/seo.blade.php)
- [`app/Models/SeoMetadata.php`](app/Models/SeoMetadata.php), [`app/Models/Redirect.php`](app/Models/Redirect.php), [`app/Http/Middleware/RedirectMiddleware.php`](app/Http/Middleware/RedirectMiddleware.php)
- [`app/Helpers/slug.php`](app/Helpers/slug.php), [`routes/web.php`](routes/web.php), [`resources/views/admin/settings/partials/_seo.blade.php`](resources/views/admin/settings/partials/_seo.blade.php)
