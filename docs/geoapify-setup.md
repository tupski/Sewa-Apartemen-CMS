# Geoapify Setup — Nearby Places (POI) & Property Map

Operator guide for activating the Geoapify persistent-POI pipeline. Architecture and divergences: [`docs/GEOAPIFY-Nearby-Places-Integration.md`](GEOAPIFY-Nearby-Places-Integration.md).

## Environment variables

Defined in [`config/services.php`](../config/services.php) under `services.geoapify`:

| Variable | Required | Default | Purpose |
|---|---|---|---|
| `GEOAPIFY_API_KEY` | Yes | *(blank)* | Server-side key used by the Places API call in [`app/Services/GeoapifyService.php`](../app/Services/GeoapifyService.php). Blank = nothing syncs. |
| `GEOAPIFY_MAP_KEY` | No | falls back to `GEOAPIFY_API_KEY` | Key used for map raster tiles. **Exposed to the browser.** |
| `GEOAPIFY_RADIUS` | No | `2000` | POI search radius in metres. |
| `GEOAPIFY_MAX_RESULTS` | No | `20` | Overall cap on POIs returned per fetch (not per category). |

**On the map key being public:** the tile key is rendered into the page's `#map-data` JSON payload and is therefore visible in page source — that is unavoidable for browser-side tiles. Mitigate it, don't hide it:

- Restrict the map key by **HTTP referrer** in the Geoapify dashboard so only your domain can use it.
- Use a **separate key** for `GEOAPIFY_MAP_KEY` from the server-side `GEOAPIFY_API_KEY`. If you leave `GEOAPIFY_MAP_KEY` unset it falls back to the server key, which publishes your Places key — acceptable for a quick test, not for production.

## Getting a key

Sign up at Geoapify and create a project in their dashboard; the project page issues the API key and is also where referrer restrictions and usage limits are configured. Copy the key into `.env` — never into tracked code.

## Activation steps

1. Set the key(s) in `.env`:
   ```env
   GEOAPIFY_API_KEY=your_server_key
   GEOAPIFY_MAP_KEY=your_referrer_restricted_key
   ```
2. `php artisan config:clear` (and `php artisan config:cache` again if you cache config in production).
3. Open a property in the admin: **Properties → Edit**.
4. Confirm **latitude** and **longitude** are filled. Without both, the resync button is disabled and a warning shows.
5. Click **Resync POI**.
6. Verify the POI table below the button populates (name, category, distance, address, `geoapify` source badge).
7. Open the public property page and confirm the map renders with markers and the "nearby places" list is grouped by category.

## Queue behaviour

`.env` currently sets `QUEUE_CONNECTION=sync` while [`config/queue.php`](../config/queue.php) defaults to `database`. Under `sync`, [`FetchNearbyPlacesJob`](../app/Jobs/FetchNearbyPlacesJob.php) runs **inline during the resync request** — the request blocks on the HTTP call and the job's retry/backoff (`$tries = 3`, `$backoff = [30, 120, 300]`) never applies.

For async execution with retries:

```env
QUEUE_CONNECTION=database
```
```bash
php artisan queue:work
```

## Cache behaviour

Results are cached for **24 hours** per property under `geoapify_places_{id}`. A cache hit skips the API call entirely; failures are never cached. The **Resync POI** action calls `Cache::forget()` on that key before dispatching, so a resync always re-fetches.

## Cost

One resync = one Places API call for that property. There is **no** automatic fetch on property create or update, and **no** scheduled refresh — API spend is entirely admin-triggered. The flip side: POI data goes stale until someone resyncs.

## Troubleshooting

| Symptom | Cause / check |
|---|---|
| Map renders but has no markers | `GEOAPIFY_MAP_KEY`/`GEOAPIFY_API_KEY` blank — the map silently falls back to OSM tiles and there are no persisted POIs yet. Also check the property has `latitude`/`longitude`. |
| POI table stays empty after resync | Check [`storage/logs/laravel.log`](../storage/logs) for `FetchNearbyPlacesJob` warnings/errors — the job logs and returns rather than throwing. Common causes: missing coordinates, blank key, or the provider returned no features in the radius. |
| `RuntimeException` mentioning the API key | The key is missing or invalid (Geoapify replied 401/403). Verify the key value and that any referrer restriction permits server-side use. |
| Some POIs never appear | Their Geoapify category is unmapped in `GeoapifyService::mapCategory()` and is **intentionally excluded**. Extend that map to support a new POI type. |
| Resync button greyed out | Missing property coordinates or a blank `GEOAPIFY_API_KEY`; the partial shows a yellow warning naming which. |
| Resync request feels slow | Expected under `QUEUE_CONNECTION=sync` — the fetch is inline. Switch to a real driver plus a worker. |
