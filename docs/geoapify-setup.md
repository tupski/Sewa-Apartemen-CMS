# Geoapify Setup — Nearby Places (POI) & Property Map

Operator guide for the Geoapify persistent-POI pipeline. Architecture and divergences: [`docs/GEOAPIFY-Nearby-Places-Integration.md`](GEOAPIFY-Nearby-Places-Integration.md).

## Configuring the API key (preferred: from the CMS)

**Settings → Integrations → Geoapify — Nearby Places (POI)**

| Field | Required | Purpose |
|---|---|---|
| **Geoapify API Key** | Yes | Server-side key used for the Places search and the Route Matrix walking-time calculation. Never rendered in HTML, JS, or API responses. |
| **Geoapify Map Key** | No | Browser-facing key for the raster map tiles on the property page. **Visible to visitors** — use a separate key restricted by HTTP referrer. Falls back to the API key when blank. |

The form is a password field that is always submitted empty: the stored value is never displayed again, and **submitting the field blank keeps the existing key** (clearing it requires entering a new value, or editing the `settings` row directly).

## Configuring via .env (fallback)

Settings take precedence; the env values are only used when no setting is stored. Defined in [`config/services.php`](../config/services.php) under `services.geoapify`:

| Variable | Required | Default | Purpose |
|---|---|---|---|
| `GEOAPIFY_API_KEY` | fallback | *(blank)* | Server-side Places + Route Matrix key. |
| `GEOAPIFY_MAP_KEY` | No | falls back to `GEOAPIFY_API_KEY` | Map raster tile key. **Exposed to the browser.** |
| `GEOAPIFY_RADIUS` | No | `2000` | Candidate search radius in metres. |
| `GEOAPIFY_MAX_RESULTS` | No | `20` | Per-group cap on candidate POIs. |

**On the map key being public:** the tile key is rendered into the page's `#map-data` JSON payload and is therefore visible in page source — that is unavoidable for browser-side tiles. Mitigate it, don't hide it:

- Restrict the map key by **HTTP referrer** in the Geoapify dashboard so only your domain can use it.
- Use a **separate key** for the Map Key from the server-side API Key. If the Map Key is blank it falls back to the API key, which publishes your Places key — acceptable for a quick test, not for production. The admin property screen warns when the two are identical.

## Getting a key

Sign up at Geoapify and create a project in their dashboard; the project page issues the API key and is also where referrer restrictions and usage limits are configured. Paste it into **Settings → Integrations** — never into tracked code.

## Activation steps

1. Save the API key in **Settings → Integrations**. The Geoapify POI block on the property form then shows the badge **Geoapify API: configured**.
2. Open a property: **Properties → Edit**. Confirm **latitude** and **longitude** are filled (use the map or the address search). Without both, the sync button is disabled and a warning explains why.
3. Click **Sync Nearby POI**. The button shows staged progress (searching → walking distance → saving) and the result banner reports the exact count.
4. Verify the POI table below the button populates, grouped into **Mall/Shopping**, **Hospital/Health**, and **Transportation**, each row showing the walking time, walking distance, and address.
5. Open the public property page and confirm the map renders with markers and the "nearby places" list is grouped by category.

## What gets synced

Three groups, using verified Geoapify category identifiers:

| Group | Geoapify categories |
|---|---|
| Mall/Shopping | `commercial.shopping_mall`, `commercial.department_store`, `commercial.marketplace` |
| Hospital/Health | `healthcare.hospital` |
| Transportation | `public_transport.train`, `public_transport.subway`, `public_transport.light_rail`, `public_transport.monorail`, `public_transport.tram`, `public_transport.bus`, `public_transport.ferry` |

Anything else (restaurants, parks, platform nodes, …) is intentionally excluded — `GeoapifyService::mapCategory()` returns `null` for unmapped categories and the POI is dropped.

## The 10-minute walking rule

Only POIs reachable in **≤ 600 seconds of actual walking** are persisted. This is a **routed travel time, not a distance**:

1. Each group is searched within `GEOAPIFY_RADIUS` (default 2 km) to collect candidates.
2. Candidates are deduplicated by `geoapify_place_id`.
3. A single **Route Matrix** request (`POST https://api.geoapify.com/v1/routematrix`, `mode=walk`) measures the walking route from the property to every candidate (batched at 1000 targets per request).
4. Candidates whose routed `time` exceeds 600 s — or that the router cannot reach — are discarded.
5. The survivors are persisted with both the straight-line distance (`distance_m`) and the walking metrics (`walking_distance_m`, `walking_duration_s`).

## Queue behaviour

The admin sync runs the job **inline** via `FetchNearbyPlacesJob::dispatchSync()` so the POIs are fetched, persisted, and re-rendered in the same request — the database is the source of truth the moment the request returns. This is driver-independent: a `database` queue connection does not defer the work.

Consequence: the request blocks on up to four Geoapify calls (3 Places + 1 Route Matrix). The job's `$tries`/`$backoff` therefore do not apply to the admin-triggered path. Do not move this back onto a background worker without also changing the admin endpoint, which expects a result to report.

## Cache behaviour

Results are cached for **24 hours** per property under `geoapify_places_{id}` (the walk-filtered payload). A cache hit skips both the Places calls and the Route Matrix call. Failures are never cached, and a partial result (one group failed) is never cached either. **Sync Nearby POI** calls `Cache::forget()` on that key first, so a sync always re-fetches.

The structured outcome of the last run is cached separately under `geoapify_sync_result_{id}` (5 minutes) so the admin request can read back counts and per-group status.

## Cost

One full sync = up to 4 Geoapify calls (3 Places + 1 Route Matrix, which is billed by source×target combinations). There is **no** automatic fetch on property create or update, and **no** scheduled refresh — API spend is entirely admin-triggered. The resync route is throttled to 5 attempts per 10 minutes.

## Troubleshooting

| Symptom | Cause / check |
|---|---|
| Sync button greyed out | Missing property coordinates or no API key; the block names which one and links to Settings → Integrations. |
| "Save the property after changing the map pin, then resync POIs." | The pin was moved but the form was not saved; the coordinates sent to Geoapify would be stale. |
| Banner: "Unable to calculate walking times" | The Route Matrix call failed (key/quota/network). Nothing is persisted and existing rows are left untouched. |
| Banner: "…Failed category: Transportation." | A partial sync: the named group's search failed, the other groups were saved. The failed group keeps its previously synced rows. |
| Banner: "No nearby places found within a 10-minute walk" | The searches succeeded but nothing survived the walking-time filter. Widen `GEOAPIFY_RADIUS` or check the property coordinates. |
| POI table stays empty after sync | Check [`storage/logs/laravel.log`](../storage/logs) for `FetchNearbyPlacesJob` entries — the job logs per-group failures instead of throwing. |
| `RuntimeException` mentioning the API key | The key is missing or invalid (Geoapify replied 401/403). Verify the key value and that any referrer restriction permits server-side use. |
| Some POIs never appear | Their Geoapify category is not one of the three synced groups, or their walking time exceeds 10 minutes. |
| Map renders but has no markers | No persisted POIs yet, or `GEOAPIFY_MAP_KEY`/Map Key blank (the map silently falls back to OSM tiles). |
| Sync request feels slow | Expected — the sync runs inline (up to 4 upstream calls). |
