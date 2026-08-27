---
name: nearby-places
description: >-
  Use when working on "nearby places"/"what's around" data for properties, or
  when someone references the Geoapify integration. Trigger phrases: "nearby
  places", "what's around", "points of interest", "POI", "Geoapify", "places
  API", "distance to landmarks". CRITICAL: the Geoapify pipeline is a design
  spec ONLY — nearby places are manually-entered JSON.
---

# Purpose
Prevent agents from treating a design document as an implemented feature. The
Geoapify nearby-places pipeline described in
[`docs/GEOAPIFY-Nearby-Places-Integration.md`](docs/GEOAPIFY-Nearby-Places-Integration.md)
is a SPEC ONLY. In reality, nearby places are manually-entered JSON on
`properties.nearby_places` and rendered on the property page.

# When to Use
- Editing how nearby places are stored, entered, or displayed.
- Any request mentioning Geoapify, a places API, or a POI pipeline.
- Reviewing distance/"what's around" behavior on the property page.

# Rules
- CURRENT REALITY (source of truth): nearby places are a JSON array on
  `properties.nearby_places` (each item has fields like `category`, `name`, and
  optional `lat`/`lng`). They are entered manually in the property admin and
  rendered in [`resources/views/properties/show.blade.php`](resources/views/properties/show.blade.php).
  The controller injects `$nearbyPlacesWithDistance` with a Haversine
  `distance_formatted`; the view groups them by category.
- DO NOT claim any of the following exist — they DO NOT: `NearbyPlacesService`,
  `places` / `property_places` tables, queue jobs for places, or Geoapify config.
- The map on the property page uses coordinates already present in the
  `nearby_places` JSON and the property's own `latitude`/`longitude`. No external
  Places/geocoding call happens on render — keep it that way.
- If asked to IMPLEMENT the Geoapify spec: this is a business/infrastructure
  change requiring user approval (per AGENTS.md "When to Ask"). Then, per the
  spec's own constraints: never call the Places API on every property page
  request; use a queue + cache; dedupe places; persist to DB; handle provider
  failures gracefully; never expose API keys. (Note: the queue is currently
  `sync` with no worker — see the `performance` skill.)

# Workflow
1. Confirm the requirement is about the CURRENT JSON approach, not the spec.
2. For display/entry changes, edit the property admin form and
   [`show.blade.php`](resources/views/properties/show.blade.php) / its controller;
   keep the `nearby_places` JSON shape intact and cast on the model.
3. If implementation of the spec is genuinely requested, STOP and get user
   approval before adding a provider, tables, jobs, or config.

# Common Mistakes
- Claiming the Geoapify pipeline, `NearbyPlacesService`, or `places` tables exist.
- Adding a live Places/geocoding API call to the property page render.
- Silently building the spec without user approval.
- Breaking the `nearby_places` JSON shape the view depends on.

# Validation
- Confirm no new external API call was added to a request path.
- Confirm `nearby_places` remains JSON on `properties` and the view still groups
  by category with distances.
- Property/blog feature tests still pass (`php artisan test`).

# Related Files
- [`docs/GEOAPIFY-Nearby-Places-Integration.md`](docs/GEOAPIFY-Nearby-Places-Integration.md) (DESIGN SPEC ONLY)
- [`resources/views/properties/show.blade.php`](resources/views/properties/show.blade.php)
- [`app/Models/Property.php`](app/Models/Property.php) (`nearby_places` JSON cast)
- [`resources/views/admin/properties/create.blade.php`](resources/views/admin/properties/create.blade.php), [`resources/views/admin/properties/edit.blade.php`](resources/views/admin/properties/edit.blade.php)
