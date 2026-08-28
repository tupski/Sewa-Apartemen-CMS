---
name: maps
description: >-
  Use when working on contact/location maps or map embeds: the contact page map,
  the property "what's around" map, or any settings-driven map iframe. Trigger
  phrases: "map embed", "contact map", "Google Maps iframe", "property map",
  "location map", "show a map". Locks contact map embeds to the sanitizing service
  (never a raw iframe) and the property map to Leaflet + server-passed tile config.
---

# Purpose
Two distinct map surfaces exist and they follow different rules. The CONTACT-page
map is rendered from a stored setting that must never be trusted as raw HTML — it
flows through a single sanitizing service that validates a URL and builds a fresh,
escaped iframe server-side. The PROPERTY-page map is a Leaflet map rendering
Geoapify raster tiles, configured entirely from a server-rendered JSON block. This
skill prevents stored-XSS, leaked API keys, and unwanted external calls on render.

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
- The PROPERTY-page map is implemented: [`resources/views/layouts/frontend.blade.php`](resources/views/layouts/frontend.blade.php)
  loads Leaflet 1.9.4 CSS/JS from CDN with SRI hashes (no npm dependency), and
  [`resources/views/properties/show.blade.php`](resources/views/properties/show.blade.php)
  renders `<div id="property-map">` plus a `<script type="application/json" id="map-data">`
  payload (hardened `json_encode` flags) carrying `center`, `mapKey`, and `markers`.
  `initPropertyMap()` in [`resources/js/app.js`](resources/js/app.js) (bound to
  `turbo:load` + `DOMContentLoaded`) reads that block and builds the Geoapify raster
  tile URL from `mapKey`, falling back to OSM tiles when it is blank. The tile key
  reaches the browser ONLY through the server-rendered payload — NEVER hardcode a key
  in JS or Blade. `GEOAPIFY_MAP_KEY` is browser-exposed by design; it falls back to
  `GEOAPIFY_API_KEY` and is currently blank, so the map serves OSM tiles today.
- Property location data still comes from stored values only: `latitude`/`longitude`,
  the manual `nearby_places` JSON, and the persisted `places`/`property_places` POI
  rows. Do NOT add external maps/tiles/Places API calls on page render — POI fetching
  happens exclusively in [`FetchNearbyPlacesJob`](app/Jobs/FetchNearbyPlacesJob.php)
  via [`GeoapifyService`](app/Services/GeoapifyService.php) (see the `nearby-places`
  skill).
- Geoapify is used for Places (POI) and map tiles ONLY. There is still NO
  geocoding/address-lookup integration — adding one must be a deliberate,
  cached/async design approved by the user (per AGENTS.md "When to Ask").

# Workflow
1. Read [`app/Services/MapEmbedService.php`](app/Services/MapEmbedService.php) to
   confirm the accepted input shapes and allowlist.
2. Render the contact map with `MapEmbedService::iframe(...)`; if it returns null,
   render nothing (no fallback raw iframe).
3. For the property map, use the already-passed coordinates, `$persistentPlaces`, and
   `nearby_places` JSON, and pass tile config through the `#map-data` payload; do not
   fetch from an external provider on render.
4. Add/extend coverage in
   [`tests/Feature/ContactMapEmbedTest.php`](tests/Feature/ContactMapEmbedTest.php)
   (contact embeds) or [`tests/Feature/GeoapifyNearbyPlacesTest.php`](tests/Feature/GeoapifyNearbyPlacesTest.php)
   (property map/POI — it pins zero outbound HTTP requests on the public page).

# Common Mistakes
- Rendering `{!! $setting->contact_map_embed !!}` (raw stored iframe — stored XSS).
- Widening `SafeHtmlService` to permit iframes.
- Routing the contact map around `MapEmbedService` because the property map uses
  Leaflet — the two surfaces are separate; the contact embed rule is unchanged.
- Adding a Google Maps/Geoapify API call to the property or contact page render.
- Hardcoding a maps/tile API key in JS, Blade, or any other code instead of passing it
  through the `#map-data` payload from config.
- Assuming Geoapify gives you geocoding here — it does not.

# Validation
- `php artisan test --filter=ContactMapEmbedTest` and
  `php artisan test --filter=GeoapifyNearbyPlacesTest` pass.
- Confirm the contact map path calls `MapEmbedService` and no raw iframe/URL is echoed.
- Confirm no external maps/tiles/Places call was added to a request path.
- Confirm no API key is literal in JS/Blade — it must come from `#map-data`.

# Related Files
- [`app/Services/MapEmbedService.php`](app/Services/MapEmbedService.php), [`app/Services/SafeHtmlService.php`](app/Services/SafeHtmlService.php)
- [`resources/views/contact/index.blade.php`](resources/views/contact/index.blade.php), [`resources/views/properties/show.blade.php`](resources/views/properties/show.blade.php)
- [`resources/views/layouts/frontend.blade.php`](resources/views/layouts/frontend.blade.php), [`resources/js/app.js`](resources/js/app.js)
- [`app/Services/GeoapifyService.php`](app/Services/GeoapifyService.php), [`app/Jobs/FetchNearbyPlacesJob.php`](app/Jobs/FetchNearbyPlacesJob.php), [`config/services.php`](config/services.php)
- [`resources/views/admin/settings/partials/_general.blade.php`](resources/views/admin/settings/partials/_general.blade.php)
- [`tests/Feature/ContactMapEmbedTest.php`](tests/Feature/ContactMapEmbedTest.php), [`tests/Feature/GeoapifyNearbyPlacesTest.php`](tests/Feature/GeoapifyNearbyPlacesTest.php)
