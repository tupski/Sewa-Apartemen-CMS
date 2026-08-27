---
name: maps
description: >-
  Use when working on contact/location maps or map embeds: the contact page map,
  the property "what's around" map, or any settings-driven map iframe. Trigger
  phrases: "map embed", "contact map", "Google Maps iframe", "property map",
  "location map", "show a map". Locks map rendering to the sanitizing service —
  never a raw iframe.
---

# Purpose
Maps in this app are rendered from stored/settings values that must never be
trusted as raw HTML. All contact map embeds flow through a single sanitizing
service that extracts and validates a URL and builds a fresh, escaped iframe
server-side. This skill prevents stored-XSS and unwanted external calls.

# When to Use
- Editing the contact page map or the property page location/"what's around" map.
- Changing how the `contact_map_embed` setting is stored or displayed.
- Any request to render a map iframe from user/admin-provided input.

# Rules
- The canonical sanitizer is
  [`MapEmbedService`](app/Services/MapEmbedService.php) (SEC-04). Render contact
  map embeds ONLY via `MapEmbedService::iframe($stored, $title)`. It accepts a
  bare Google Maps embed URL or a legacy `<iframe>` blob, validates against a
  Google Maps allowlist, and returns a brand-new escaped iframe (or nothing).
- NEVER echo a raw iframe or URL from settings, and never `{!! !!}` a stored map
  value directly. `SafeHtmlService` deliberately strips iframes — do not widen it.
- Property location data (`latitude`, `longitude`, `nearby_places`) is
  MANUALLY-entered JSON — there is NO live maps/geocoding provider integration.
  Do not add external maps/geocoding API calls on page render.
- Any real maps-provider work (tiles, geocoding, Places) must be a deliberate,
  cached/async design approved by the user (per AGENTS.md "When to Ask"). No API
  keys in code.

# Workflow
1. Read [`app/Services/MapEmbedService.php`](app/Services/MapEmbedService.php) to
   confirm the accepted input shapes and allowlist.
2. Render the contact map with `MapEmbedService::iframe(...)`; if it returns null,
   render nothing (no fallback raw iframe).
3. For the property map, use the already-passed coordinates/`nearby_places` JSON;
   do not fetch from an external provider on render.
4. Add/extend coverage in
   [`tests/Feature/ContactMapEmbedTest.php`](tests/Feature/ContactMapEmbedTest.php).

# Common Mistakes
- Rendering `{!! $setting->contact_map_embed !!}` (raw stored iframe — stored XSS).
- Widening `SafeHtmlService` to permit iframes.
- Adding a Google Maps/Geoapify API call to the property or contact page render.
- Hardcoding a maps API key in code or Blade.

# Validation
- `php artisan test --filter=ContactMapEmbedTest` passes.
- Confirm the map path calls `MapEmbedService` and no raw iframe/URL is echoed.
- Confirm no external maps/geocoding call was added to a request path.

# Related Files
- [`app/Services/MapEmbedService.php`](app/Services/MapEmbedService.php), [`app/Services/SafeHtmlService.php`](app/Services/SafeHtmlService.php)
- [`resources/views/contact/index.blade.php`](resources/views/contact/index.blade.php), [`resources/views/properties/show.blade.php`](resources/views/properties/show.blade.php)
- [`resources/views/admin/settings/partials/_general.blade.php`](resources/views/admin/settings/partials/_general.blade.php)
- [`tests/Feature/ContactMapEmbedTest.php`](tests/Feature/ContactMapEmbedTest.php)
