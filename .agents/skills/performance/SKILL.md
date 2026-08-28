---
name: performance
description: >-
  Use when addressing performance: eager loading to fix N+1 queries, caching,
  lazy-loading media, DB indexes, or production optimization. Trigger phrases:
  "fix N+1", "eager load", "page is slow", "add an index", "cache this",
  "lazy load images", "optimize", "route:cache", "config:cache". Grounds work
  in this repo's sync-queue reality and existing cache conventions.
---

# Purpose
Keep pages fast without introducing background infrastructure that does not
exist. Exactly ONE custom queued job exists
([`FetchNearbyPlacesJob`](app/Jobs/FetchNearbyPlacesJob.php)) and `.env` runs the
queue driver as `sync`, so even that job executes inline on the request. This
skill stops agents from assuming async processing, adding blocking external API
calls, or indexing blindly.

# When to Use
- Diagnosing or fixing slow list/detail pages (properties, bookings, blog).
- Adding eager loading, caching, indexes, or lazy-loaded media.
- Preparing production optimization commands.

# Rules
- Avoid N+1 — eager load the common relationships with `with()` / `load()`:
  `property → photos`, `property → amenities`, `booking → property`,
  `post → category`, `post → tags`, and `property → propertyPlaces.place` (the
  persistent POI pivot — never loop pivot rows and lazy-hit `->place`).
- Exactly one custom queued job exists
  ([`FetchNearbyPlacesJob`](app/Jobs/FetchNearbyPlacesJob.php)) and
  `QUEUE_CONNECTION` is overridden to `sync` in `.env` (config default is
  `database`). Under `sync` that job runs INLINE during the admin resync request,
  so its `$tries = 3` / `$backoff = [30,120,300]` / `$timeout = 60` are inert
  until a real driver plus `php artisan queue:work` are provisioned. If you need
  real async work, confirm the queue driver + worker with the user first — do NOT
  assume a worker is running.
- No blocking external API calls on page render. The Geoapify nearby-places
  integration IS implemented, but [`GeoapifyService`](app/Services/GeoapifyService.php)
  is called ONLY from [`FetchNearbyPlacesJob`](app/Jobs/FetchNearbyPlacesJob.php),
  triggered only by the admin resync action. The public property page reads
  persisted `places` / `property_places` rows and issues ZERO outbound HTTP
  requests — [`tests/Feature/GeoapifyNearbyPlacesTest.php`](tests/Feature/GeoapifyNearbyPlacesTest.php)
  pins this with `Http::preventStrayRequests()`. Never call Geoapify (or any
  external API) from a controller, view, or other render path (see the
  `nearby-places` skill).
- Cache driver is `file` (`.env` override; config default `database`). Cache only
  read-safe data and INVALIDATE on writes. The sitemap is already cached ~24h in
  [`app/Services/SitemapService.php`](app/Services/SitemapService.php) — reuse it,
  do not add a second sitemap cache. POI fetches are cached per property for 24h
  under `geoapify_places_{id}`; a cache hit skips the API call and failures are
  never cached. The admin resync action `Cache::forget()`s that key — reuse it
  rather than adding a second POI cache.
- Lazy-load below-the-fold media (`loading="lazy"`) and serve responsive images;
  do not block first render on galleries.
- Indexes are intentional. Slugs (`properties.slug`, `pages.slug`, `posts.slug`)
  and booking lookup columns are indexed. Add an index only for a real
  `WHERE`/`JOIN`/`ORDER BY` column, via a migration.

# Workflow
1. Reproduce the slow path; identify the query count (N+1) or blocking call.
2. Add eager loading in the controller/query, not in the view.
3. For repeated reads of stable data, cache with a clear key and invalidate on
   the corresponding write.
4. For new hot query columns, add an index in a new additive migration.
5. Re-check the query count and confirm no new blocking external call was added.

# Common Mistakes
- Assuming a queue worker processes jobs asynchronously (it is `sync`, so
  `FetchNearbyPlacesJob` runs inline with no retries).
- Adding an external API call (Geoapify/maps/geocoding) on the property page
  render — a test pins the page at zero outbound HTTP requests.
- Caching without an invalidation path, serving stale data after writes.
- Indexing every column instead of the ones actually filtered/sorted.
- Duplicating the sitemap cache instead of using `SitemapService`.

# Validation
- `php artisan test --filter=PerformanceTest` passes.
- Confirm reduced query count (debug bar / query log) on the touched page.
- In dev after config/route/view changes: `php artisan optimize:clear`.
- Production: `php artisan config:cache`, `php artisan route:cache`,
  `php artisan view:cache`.

# Related Files
- [`app/Services/SitemapService.php`](app/Services/SitemapService.php)
- [`app/Jobs/FetchNearbyPlacesJob.php`](app/Jobs/FetchNearbyPlacesJob.php), [`app/Services/GeoapifyService.php`](app/Services/GeoapifyService.php)
- [`config/queue.php`](config/queue.php), [`config/cache.php`](config/cache.php)
- [`resources/views/properties/index.blade.php`](resources/views/properties/index.blade.php), [`resources/views/properties/show.blade.php`](resources/views/properties/show.blade.php)
- [`tests/Feature/PerformanceTest.php`](tests/Feature/PerformanceTest.php), [`tests/Feature/GeoapifyNearbyPlacesTest.php`](tests/Feature/GeoapifyNearbyPlacesTest.php)
