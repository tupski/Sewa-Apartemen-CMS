# GEOAPIFY Integration Security Audit Report

**Scope:** Geoapify nearby-places integration only (`GeoapifyService`, `FetchNearbyPlacesJob`, `places`/`property_places` models & migrations, admin resync flow, public property map rendering, tests, config, docs).

**Mode:** Read-only static audit. No code modified, nothing committed, no credentials touched, no live Geoapify quota consumed.

**Tooling limitation:** This environment exposes no command-execution tool. `git status`, `git diff`, `git diff --cached`, `gitleaks`, `semgrep`, `composer audit`, and `npm audit` could **not** be executed. Where a conclusion would normally be confirmed by a CLI tool, it is marked *unverified* or *lowered confidence* below. All findings were verified by direct source review.

---

## Executive Summary

The Geoapify integration is **well-scoped and comparatively low-risk**. Its architecture enforces several correct security properties:

- The Geoapify API key is **server-side only** — it is never emitted to the browser, never logged by application code, and never placed in Git-tracked files.
- The only two routes that can trigger a Geoapify request are **admin-only** (`auth` + `verified` + `admin` middleware), so unauthenticated and non-admin users cannot reach the Places API at all.
- The Places API call is confined to a single queued job with fixed, **server-defined parameters** (category allowlist, configured radius/limit). No attacker-controlled string reaches the Geoapify URL query in a way that could change the endpoint, hostname, or protocol.
- The public property page performs **zero outbound HTTP** — POIs are read from the database — and this is pinned by a feature test.
- The browser map key is delivered via a server-rendered JSON block with HTML-entity hex escaping; marker content is escaped before HTML interpolation in [`resources/js/app.js`](resources/js/app.js:42).

The main risks are **operational / abuse-related rather than code-level**:

1. **No rate limiting** on the admin resync endpoint — an admin can hammer it and drain the Geoapify quota.
2. **Cache bypass + possible duplicate writes** on concurrent resyncs (the job is not idempotency-guarded).
3. **A shared key/limit config** that lets a browser-exposed *map* key escalate to *Places API* usage if `GEOAPIFY_MAP_KEY` is unset.
4. **`APP_DEBUG=true`** — a pre-existing, repo-wide issue that would surface raw exception text (including a full Geoapify request URL) to any visitor if the job ran inline and threw.
5. **No authz on the admin `nearby-places` read** beyond role membership, and **no throttle** on the public property page that renders arbitrary persisted `website`/`address` fields — both informational/low.

**Commit recommendation: ⚠️ SAFE TO COMMIT WITH CONDITIONS** — the committed code introduces no new critical or high-severity defect, but the conditions in [Commit Recommendation](#commit-recommendation) must be satisfied before production deployment.

---

## Uncommitted Changes Review

Because CLI git tooling was unavailable, this section is reconstructed from:
- `.git/COMMIT_EDITMSG` (last commit message): `feat(git,properties): add post-update actions and nearby places discovery`
- `.git/logs/HEAD` (ref log): last entry `81ccd42… → 392f61e… commit: feat(git,properties): add post-update actions and nearby places discovery`
- Working-tree evidence for the Geoapify feature set.

### What the latest changes do
The most recent commit bundles the **Geoapify nearby-places discovery feature** (Phase 5/6) with the unrelated Git post-update action feature. The Geoapify subset comprises:

| Area | Files (Geoapify-related) |
|---|---|
| Config | `config/services.php` (`geoapify` block: `key`, `map_key`, `radius`, `max_results`) |
| Service | `app/Services/GeoapifyService.php` |
| Job | `app/Jobs/FetchNearbyPlacesJob.php` |
| Models | `app/Models/Place.php`, `app/Models/PropertyPlace.php` |
| Migrations | `database/migrations/2026_08_28_000001_create_places_table.php`, `2026_08_28_000002_create_property_places_table.php` |
| Routes | `routes/web.php` (admin `nearby-places` GET + `resync-nearby-places` POST) |
| Controller | `app/Http/Controllers/PropertyController.php` (`nearbyPlaces`, `resyncNearbyPlaces`, public-show persistent-POI wiring, `mapData`) |
| Views | `resources/views/admin/properties/_nearby.blade.php`, `resources/views/properties/show.blade.php` (map JSON block + marker/POI rendering) |
| JS | `resources/js/app.js` (`initPropertyMap`, `escapeHtml`) |
| Tests | `tests/Feature/GeoapifyNearbyPlacesTest.php` |

### New attack surfaces introduced
1. **New admin POST endpoint** (`resync-nearby-places`) → new outbound server-side HTTP to `api.geoapify.com` — the app's second outbound-to-third-party path (after media URL import).
2. **New persisted-into-DB data path** — Geoapify-supplied `name`, `address`, `website`, `phone`, `raw_category` flow into `places` and back out through public HTML (via escaped Blade) and the JS map popup.
3. **New browser key injection point** — `map_data` JSON now carries the Geoapify **map** key into page HTML/JS.
4. **New job** with retry/backoff behavior that can multiply upstream requests on failure.

### Security regressions introduced
- **No** new exposed secret: the key stays server-side (see [API Key Security](#geoapify-api-key-security)).
- **No** new auth bypass: both new admin routes sit inside the `['auth','verified','admin']` group ([`routes/web.php`](routes/web.php:79)).
- **Regression risk (Low/Medium):** none of the new Geoapify-persisted strings are run through `SafeHtmlService` before output; they rely on Blade auto-escaping. Today the render paths escape correctly, but the pattern invites future `{!! !!}` mistakes.
- **Regression risk (Operational):** the job's `tries=3` + `backoff` only matter with a real queue worker; with `QUEUE_CONNECTION=sync` a transient Geoapify outage throws **inline inside the admin request**, and a 5xx → `RequestException` is thrown with a body summary that includes the request URL — surfaced to the admin session if `APP_DEBUG=true`.

---

## GEOAPIFY Architecture / Data Flow

```
Admin (auth+verified+admin)
  │  POST admin/properties/{property}/resync-nearby-places   (CSRF protected)
  ▼
PropertyController::resyncNearbyPlaces()
  │  guards: coordinates present  &&  GEOAPIFY_API_KEY configured
  │  Cache::forget("geoapify_places_{id}")     // force fresh
  │  FetchNearbyPlacesJob::dispatch(property)  // queue=sync → runs inline
  ▼
FetchNearbyPlacesJob::handle()
  │  guards: coords present, key configured, service construct
  │  Cache::get("geoapify_places_{id}") → hit? skip API
  ▼
GeoapifyService::fetchNearbyPlaces(lat, lng, radius=config, limit=config)
  │  Http::timeout(...)->get('https://api.geoapify.com/v2/places', query)
  │     filter = circle:{lng},{lat},{radius}   // config radius, NOT user input
  │     categories = fixed allowlist const
  │     limit = config max_results (NOT user input)
  │     apiKey = server-side config
  ▼
Geoapify Places API (api.geoapify.com)
  ▼
Response (JSON)
  │  401/403 → RuntimeException("key invalid or quota exceeded")
  │  non-2xx  → RuntimeException("request failed: {status}")
  │  invalid JSON / missing features → graceful empty
  ▼
Normalize + validate results:
  │  place_id, name, category→NEARBY_CATEGORIES (allowlist), lat/lng, address,
  │  website, phone, raw_category, distance (Haversine)
  ▼
Cache::put("geoapify_places_{id}", $results, 24h)
  ▼
DB transaction:
  │  Place::updateOrCreate(geoapify_place_id)   // dedupe on nullable-unique
  │  PropertyPlace upsert (property_id, place_id, source='geoapify', distance_m)
  │  prune ONLY source='geoapify' pivots; keep 'manual'
  ▼
Public property page (NO outbound HTTP — pinned by test):
  │  PropertyController::show() → $persistentPlaces (source='geoapify', nearest-first)
  ▼
resources/views/properties/show.blade.php
  │  escaped Blade output for name/address/distance
  │  <script type="application/json" id="map-data">{!! json_encode($mapData, JSON_HEX_TAG|AMP|APOS|QUOT) !!}</script>
  ▼
resources/js/app.js → initPropertyMap()
  │  JSON.parse(map-data) → mapKey (browser key) + markers
  │  marker.name/category/distance → escapeHtml() → innerHTML popup
```

**Attacker-controlled data entry points:**
- Property `latitude` / `longitude` (admin form; validated `numeric between:-90,90` / `-180,180` in [`app/Http/Requests/PropertyRequest.php`](app/Http/Requests/PropertyRequest.php:66)) → becomes the circle **center**.
- Admin "Resync POI" click (authenticated + CSRF).
- (Indirect) `places` / `property_places` rows already in the DB — but those can only be written by the job from Geoapify responses, or by an admin.

**Attacker-controlled data exit points:**
- Public property page HTML (escaped).
- JS map popup (`innerHTML`, but every interpolated value passes `escapeHtml()`).
- Geoapify request URL (admin-only, server-side, fixed params).

---

## Attack Surface

### API Key Exposure
- `GEOAPIFY_API_KEY` and `GEOAPIFY_MAP_KEY` are read via `env()` in [`config/services.php`](config/services.php:38) and never leave `config()` except:
  - **Server-side** into the Places API query param (job only).
  - **Browser-side** as `GEOAPIFY_MAP_KEY` (or falling back to `GEOAPIFY_API_KEY`) injected into the `#map-data` JSON for Leaflet tiles.
- No `.env` value is committed: `.env` is gitignored; `.env.example` contains no Geoapify secret. No API key appears in any tracked source file examined.
- The key is never logged by application code (`Log::info/warning` in the job log only property id/name and generic statuses — no URL, no key).
- **Frontend key is intentional (map tiles require a browser key), but the fallback `env('GEOAPIFY_MAP_KEY', env('GEOAPIFY_API_KEY', ''))` means that unless operators set a *separate, restricted* map key, the **Places API key is shipped to the browser**. That is the single most important configuration risk in this integration.

### API Abuse
- Resync endpoint: admin-only, **no throttle**, no per-property cooldown → an admin (or compromised admin session) can generate unlimited Geoapify requests by repeatedly pressing resync.
- Cache bypass: every resync calls `Cache::forget("geoapify_places_{id}")` first, so the 24h cache provides **zero protection** against repeated manual resyncs.
- No queue/job amplification for unauthenticated users (job dispatch is admin-only). With `QUEUE_CONNECTION=sync` the cost is paid inline on the admin request; with a real worker, an admin can enqueue many jobs and fan them out.
- No user-controlled radius/limit/query reach Geoapify (all fixed by config/const).

### SSRF
- The outbound URL is a hardcoded `https://api.geoapify.com/v2/places` const; host/protocol/path cannot be influenced by any input.
- **No redirect-following concern that matters:** `Http::get` uses Guzzle with default `allow_redirects` — a compromised/redirecting `api.geoapify.com` could redirect to another host, but the API key would be in the *query string of the original URL* and is **not** forwarded to a redirect target by standard cURL (query params are part of the URI being redirected, not re-sent). Still, adding `->withOptions(['allow_redirects' => false])` or pinning the host is defense-in-depth. **Not exploitable via user input.**
- No user-supplied URL is ever fetched anywhere in this integration.

### Input Injection
- **SQLi:** All Geoapify-persisted data goes through Eloquent `updateOrCreate`/`updateOrCreate` with bound parameters. Manual `nearby_places` JSON passes through the validated `PropertyRequest` (allowlisted category, numeric distance). No raw SQL concatenation found. **Not vulnerable.**
- **XSS:** Admin table and public property page escape via Blade `{{ }}`. JS map popups escape via `escapeHtml()` ([`resources/js/app.js`](resources/js/app.js:42)). The `#map-data` JSON block uses `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT`, so a malicious `name` cannot break out of the `<script type="application/json">` block. **Not vulnerable on current render paths.**
- **HTML injection:** same as XSS — escaped on render.
- **URL injection:** `website` is stored but is **not rendered as a link** anywhere in the audit (no `<a href="...website">` found). Not currently exploitable; should be `url:`-validated and link-rendered only through an allowlist if ever used.
- **Header injection:** HTTP client does not allow header injection via query params; no header is built from user data. **Not vulnerable.**
- **Log injection:** `Log::info("... property {$property->id} ({$property->name})")` interpolates the admin-controlled property `name` into a log line. Property names are validated `string max:255`; CR/LF are technically allowed by the validation but extremely low-risk (log files, not a terminal). **Informational.**
- **Command injection:** N/A — no shell calls in this integration.

### Response Security
- Responses are structurally validated (`features` present & array) before use; per-field values are cast to strings and category-mapped against `Property::NEARBY_CATEGORIES` allowlist.
- **Data/cache poisoning (Medium):** the raw Geoapify payload is cached verbatim (`Cache::put("geoapify_places_{id}", $results, 86400)`). The cached array is used by the job to upsert rows. A malicious/compromised Geoapify response (e.g., DNS/CA compromise) would persist arbitrary `name`/`address`/`website` into the DB — a stored-XSS vector if a future render path stops escaping. Distance is **recomputed** server-side from lat/lng via Haversine (not trusted from Geoapify), so distance spoofing is not possible.
- No HTTP response caching headers are set by the app for the public property page; browser cache is default.

### Authorization
- `nearby-places` (GET) and `resync-nearby-places` (POST) are inside the admin group → `auth` + `verified` + `admin` → `EnsureUserIsAdmin` (`User::isAdmin()`).
- Any authenticated **admin** can resync **any** property — there is no per-property ACL (consistent with the rest of this admin which has no `Policies/`). Acceptable for a single-admin CMS; noted as informational.
- The public `properties.public.show` route is unauthenticated by design (renders persisted POIs) — that is a **read-only** data exit, not an authorization bypass of the fetch.

---

## Security Findings

### SEC-001 — No rate limiting / cooldown on the admin Geoapify resync endpoint
**Severity:** Medium
**Confidence:** High
**File:** [`routes/web.php`](routes/web.php:125), [`app/Http/Controllers/PropertyController.php`](app/Http/Controllers/PropertyController.php:816)
**Location:** `resyncNearbyPlaces()` — route has no `throttle`; every call performs `Cache::forget()` + dispatch.

**Description:** The resync route carries no `throttle` middleware and no cooldown, and each call deliberately invalidates the 24h cache first, so the cache provides no protection. Every resync = one (or, with retries, several) paid Geoapify Places API calls.

**Attack Scenario:** An admin — or an attacker who has stolen/CSRF'd an admin session — issues `POST /admin/properties/{id}/resync-nearby-places` in a tight loop across many properties. Each request clears the cache and forces a fresh upstream call.

**Impact:** Direct Geoapify quota/credit exhaustion and associated financial cost; degraded admin responsiveness under `sync` queue (each request blocks on the upstream call); possible upstream rate-limit bans.

**Evidence:** [`PropertyController::resyncNearbyPlaces()`](app/Http/Controllers/PropertyController.php:816) calls `Cache::forget("geoapify_places_{$property->id}")` then `FetchNearbyPlacesJob::dispatch($property)` unconditionally; the route at [`routes/web.php`](routes/web.php:125) carries no `throttle`.

**Recommended Fix:** Add a throttle to the route, e.g. `->middleware('throttle:5,10')` (5 resyncs per 10 minutes per admin), or implement a per-property cooldown (refuse a resync if `property_places.updated_at` is newer than e.g. 5 minutes). Consider a server-side cooldown regardless of throttling, since the throttle key is IP-based and shared admins may collide.

**Verification:** Send 6 rapid `POST /admin/properties/{id}/resync-nearby-places` requests as an admin; the 6th should return `429 Too Many Requests` and no `FetchNearbyPlacesJob` should be pushed.

---

### SEC-002 — Cache bypass and concurrent duplicate upstream calls (weak idempotency)
**Severity:** Medium
**Confidence:** High
**File:** [`app/Jobs/FetchNearbyPlacesJob.php`](app/Jobs/FetchNearbyPlacesJob.php:65)
**Location:** `handle()` — cache read/`Cache::forget()` in controller + no lock.

**Description:** The controller always calls `Cache::forget("geoapify_places_{id}")` before dispatching, so a re-sync always ignores the 24h cache. Inside the job, the cache is read **after** the API call (in the flow above, the cache is populated post-fetch), and there is no atomic lock or `Cache::lock()` around the fetch/upsert. Two concurrent resyncs for the same property can both miss the cache and both call the API, and both can write the same `places`/`property_places` rows (mitigated only by the unique `(property_id, place_id)` composite).

**Attack Scenario:** An admin opens several admin tabs and clicks "Resync POI" on the same property, or a script posts several resyncs concurrently → multiple simultaneous paid API calls.

**Impact:** Quota amplification (up to N concurrent calls instead of 1), and wasted work; no data corruption due to the unique composite key, but churn on `places` rows.

**Evidence:** [`PropertyController::resyncNearbyPlaces()`](app/Http/Controllers/PropertyController.php:829) `Cache::forget()` + dispatch; job uses plain `Cache::get`/`Cache::put` with no lock.

**Recommended Fix:** Use an atomic lock, e.g. `Cache::lock("geoapify_sync_{$id}", 120)->get()` around the fetch+persist (with `finally` release), or a `Cache::add()`-style "in-flight" marker so a second concurrent job early-returns.

**Verification:** Fire two concurrent resync requests for the same property with `Http::fake()` and assert the Geoapify endpoint is hit exactly once (extend `test_job_uses_the_cache_and_calls_the_api_only_once` to a concurrent variant).

---

### SEC-003 — Places API key can be exposed to the browser when `GEOAPIFY_MAP_KEY` is unset
**Severity:** Medium
**Confidence:** High (config behavior), Medium (likelihood depends on deployment)
**File:** [`config/services.php`](config/services.php:40), [`resources/views/properties/show.blade.php`](resources/views/properties/show.blade.php:354), [`resources/js/app.js`](resources/js/app.js:1533)
**Location:** `geoapify.map_key` config; `mapData` JSON block; `initPropertyMap()` tile layer.

**Description:** `map_key` falls back to `env('GEOAPIFY_API_KEY', '')`, and the map key is deliberately injected into the browser (`#map-data` → Leaflet tile URL `?apiKey=`). If an operator sets only `GEOAPIFY_API_KEY`, the **Places API key is shipped to every visitor of a property page**. Geoapify keys are often domain-restricted, but an API key intended for server-side Places API use is a broader-privilege secret than a tiles-only key.

**Attack Scenario:** A visitor inspects `#map-data` (or DevTools network tab) and copies the key; if the key is not domain-restricted, they can call the Geoapify Places API directly, exhausting the shared quota or incurring cost under the app's account.

**Impact:** API-credit/quota abuse with the app's own billing account; potential data-access if the same key grants broader endpoints.

**Evidence:** [`config/services.php`](config/services.php:40) `'map_key' => env('GEOAPIFY_MAP_KEY', env('GEOAPIFY_API_KEY', ''))`; [`resources/js/app.js`](resources/js/app.js:1533) builds the tile URL with `apiKey=` + `encodeURIComponent(mapKey)`.

**Recommended Fix:** In production, always set a distinct, domain-restricted `GEOAPIFY_MAP_KEY` (Geoapify allows per-key referrer/domain restrictions). Optionally log a warning when `map_key === key` so operators notice. Consider an env validation helper that surfaces "browser key is not restricted" in the admin.

**Verification:** Deploy with only `GEOAPIFY_API_KEY` set, load a property page, confirm the full Places key appears in `#map-data`; then set a restricted `GEOAPIFY_MAP_KEY` and confirm the two keys differ in the payload.

---

### SEC-004 — `APP_DEBUG=true` can leak request/exception internals on Geoapify failure
**Severity:** Medium (pre-existing, repo-wide; relevant here because of the new outbound call)
**Confidence:** High
**File:** [`.env`](.env:4), [`config/app.php`](config/app.php:42), [`app/Services/GeoapifyService.php`](app/Services/GeoapifyService.php:136)
**Location:** `APP_DEBUG` flag; `GeoapifyService::fetchNearbyPlaces()` exception paths.

**Description:** `.env` sets `APP_DEBUG=true`. With `QUEUE_CONNECTION=sync`, the job runs inline, so any thrown `RuntimeException` (401/403, non-2xx) or a Laravel `RequestException` (5xx, connection error) propagates to the HTTP layer and, in debug mode, renders an Ignition/Whoops page containing the full stack trace and the exception message — which for a `RequestException` includes the request URL (including the `apiKey` query param) and a body summary.

**Attack Scenario:** Geoapify is down or returns an error; an admin clicks resync; the resulting debug page (or the logged exception in `storage/logs/laravel.log`, log level `debug`) contains the Geoapify request URL with the API key. A local/proxied visitor with a debug page reachable could harvest the key.

**Impact:** Credential exposure via error page and logs; this is the same class of issue documented in the repo's own security audit (`APP_DEBUG=true` is a known SEC-critical item in [`docs/security-audit-2026-08-27.md`](docs/security-audit-2026-08-27.md)).

**Evidence:** [`.env`](.env:4) `APP_DEBUG=true`; [`config/logging.php`](config/logging.php:64) `LOG_LEVEL=debug`; `GeoapifyService` throws on 401/403/non-2xx; Laravel's `RequestException::prepareMessage` appends the request URL + body summary.

**Recommended Fix:** Set `APP_DEBUG=false` in production (repo-recognized remediation). Additionally, harden the service to never include the URL/key in exceptions: wrap the HTTP call and throw only generic messages, and set a reasonable `LOG_LEVEL` (e.g. `warning`) in production.

**Verification:** With `APP_DEBUG=false` and a `Http::fake()` 500, hit the resync route and confirm the rendered response contains no URL/key and the log contains no credential material.

---

### SEC-005 — Retry/backoff not effective under `sync` queue; risk of retry storms when a worker is enabled
**Severity:** Low
**Confidence:** High
**File:** [`app/Jobs/FetchNearbyPlacesJob.php`](app/Jobs/FetchNearbyPlacesJob.php:39), [`.env`](.env:50)
**Location:** `$tries=3`, `$backoff=[30,120,300]`; `QUEUE_CONNECTION=sync`.

**Description:** The job defines `tries=3` and backoff, but the current queue driver is `sync`, so a failure throws immediately (no retry, no backoff) and fails the whole admin request. Conversely, if an operator enables a real driver (`database`) and runs `queue:work`, each failed job retries up to 3 times (4 upstream attempts per dispatch, total 5 with the initial attempt's own HTTP-level retry at default `tries=1`... in practice 1 attempt + 3 job re-runs). A burst of resync dispatches during an outage multiplies upstream calls.

**Attack Scenario:** During a Geoapify outage, an admin triggers resyncs on N properties; with a worker, this can produce up to ~4×N upstream attempts plus backoff pressure on the queue.

**Impact:** Amplified paid API calls during outages; admin request blocking under `sync`.

**Evidence:** [`.env`](.env:50) `QUEUE_CONNECTION=sync`; job `tries`/`backoff` declarations; service `throw`s on failure (no catch-and-return).

**Recommended Fix:** Decide deliberately on the queue driver (per repo convention, confirm before relying on background processing). Under `sync`, wrap `handle()` so failures degrade gracefully (log + return) instead of failing the admin request. With a real driver, keep `tries` modest and add `retryUntil`/`failOnTimeout`; consider `->unique()` (requires a cache driver with locks) to prevent duplicate concurrent jobs for the same property.

**Verification:** Feature test asserting `handle()` returns without throwing on a 500/`ConnectionException` (currently `test_job_handles_persistent_api_failure_without_throwing` exists — confirm it covers the connection-exception path, not just HTTP 500).

---

### SEC-006 — Admin `nearby-places` GET lacks per-property authorization (informational)
**Severity:** Low / Informational
**Confidence:** High
**File:** [`app/Http/Controllers/PropertyController.php`](app/Http/Controllers/PropertyController.php:799), [`routes/web.php`](routes/web.php:124)
**Location:** `nearbyPlaces()`, `resyncNearbyPlaces()` — role middleware only.

**Description:** Any authenticated admin can read or resync any property's POIs; there is no ownership/ACL scoping. This matches the rest of this CMS (no `Policies/` directory), so it is not a regression, but it means a low-privileged admin role (the `admin` role is accepted by `EnsureUserIsAdmin` alongside `super-admin`) can trigger paid API calls.

**Attack Scenario:** A storefront manager given the `admin` role uses the resync endpoint to drain quota or overwrite POIs for properties they should not manage.

**Impact:** Quota abuse and unintended POI data changes; low integrity/confidentiality impact.

**Evidence:** [`EnsureUserIsAdmin`](app/Http/Middleware/EnsureUserIsAdmin.php:18) only checks `isAdmin()`; no ownership scoping on property queries.

**Recommended Fix:** If the `admin` role is ever granted to non-super users, add role-based scoping (e.g. only `super-admin` may resync) or a per-property permission check. For a single-admin CMS this can be accepted as-is.

**Verification:** Review role assignments; if multiple admins exist, add a test that a non-super admin cannot resync.

---

### SEC-007 — Geoapify response content is not sanitized via SafeHtmlService (defense-in-depth)
**Severity:** Low
**Confidence:** High
**File:** [`app/Jobs/FetchNearbyPlacesJob.php`](app/Jobs/FetchNearbyPlacesJob.php:65), [`resources/views/properties/show.blade.php`](resources/views/properties/show.blade.php:354)
**Location:** Persisted `name`/`address`/`website`; all output paths.

**Description:** Geoapify-supplied strings are persisted and later rendered. All current render paths escape correctly (Blade `{{ }}`, JS `escapeHtml`, JSON hex flags), so there is no exploitable XSS today. However, the data is treated as trusted-ish at the persistence layer (no `SafeHtmlService`/allowlist sanitization on write), so a single future `{!! !!}` or a new popup that skips `escapeHtml` would turn a compromised Geoapify response into stored XSS.

**Attack Scenario:** (Theoretical) A DNS/CA-compromised Geoapify response, or a future refactor, delivers `<img onerror=...>` in a `name`; a render path without escaping executes it in the admin or public context.

**Impact:** Stored XSS in the worst case; currently latent.

**Evidence:** `Place` stores raw strings; render paths currently escape; no sanitize-on-write.

**Recommended Fix:** Add sanitize-on-write in the job (strip tags / normalize whitespace / length-clamp per column, matching the DB column lengths) or run through `SafeHtmlService` at render. Keep the hex-flag JSON encoding and `escapeHtml()` in the JS popup as-is.

**Verification:** Unit-test that a fixture response with `<script>` in `name`/`address` is stored sanitized and that the property page HTML contains no unescaped tag.

---

### SEC-008 — Unauthenticated public page renders arbitrary persisted POI fields (informational)
**Severity:** Informational
**Confidence:** High
**File:** [`resources/views/properties/show.blade.php`](resources/views/properties/show.blade.php:41)
**Location:** `$nearbyGroups` / marker rendering.

**Description:** The public property page (no auth) renders `places.name`/`address`/`category` and the map markers. This is intentional (read-only display). No rate limit exists on this page, so it can be scraped heavily, but it issues **zero outbound requests**, so scraping costs the app nothing upstream. No authorization concern beyond the fact that any published property's POI data is public.

**Attack Scenario:** None material — at most heavy scraping/DoS of the page itself (mitigated by app/web-server-level controls outside this integration).

**Impact:** None security-relevant for the Geoapify integration.

**Evidence:** Public route renders `$persistentPlaces`; test pins zero outbound HTTP.

**Recommended Fix:** None required. Optionally add `Cache` for the assembled map/popup payload if page load becomes a concern.

**Verification:** N/A.

---

## GEOAPIFY API Key Security

| Question | Answer |
|---|---|
| Is the key exposed to the browser? | **Server-side Places key: No.** A browser key is injected only as `GEOAPIFY_MAP_KEY`; **but if unset it falls back to the Places key** (see SEC-003). |
| Can the key leak via Git? | **No** — `.env` is gitignored, `.env.example` has no secret, and no tracked file contains a key (verified by grep of tracked app/config/views; git-history scan via `gitleaks` was **not** runnable — see note). |
| Can the key leak via logs? | **Not by app code.** Job logs omit the URL/key. Upstream `RequestException` messages (written when a job fails) may include the URL **in the log** — but the key is in the query string, so the URL does carry it; production `LOG_LEVEL` should be non-debug and `APP_DEBUG=false` (SEC-004). |
| Can the key leak via error messages? | Only if `APP_DEBUG=true` renders a thrown exception (SEC-004). |
| Is it server-side only? | Yes for the Places key; the browser key is by design client-side (tiles). |

**Key hygiene verdict:** The design correctly separates the concerns; the one trap is the `map_key` fallback. Configure a distinct restricted `GEOAPIFY_MAP_KEY` and disable debug in production.

---

## API Abuse & Rate Limiting

| Question | Answer |
|---|---|
| Can unauthenticated users trigger Geoapify? | **No.** Both triggering routes require `auth`+`verified`+`admin`. |
| Can one admin generate unlimited requests? | **Yes** — no throttle, no cooldown, cache always bypassed (SEC-001/002). |
| Is rate limiting implemented? | **No** on the resync route. Public booking/status routes are throttled, but not this one. |
| Can caching be bypassed? | **Yes, by design** — every resync calls `Cache::forget()` (SEC-002). |
| Can radius be manipulated? | **No** — radius comes from config, never from the request. |
| Can coordinates be manipulated? | Only via the validated admin property form (`between:-90,90` / `-180,180`); they become the circle center, so an admin could center a query anywhere on the globe — an intentional feature, not a bypass (a valid center is not an abuse vector by itself). |

---

## SSRF Analysis

| Question | Answer |
|---|---|
| Can user input influence hostname / protocol / path? | **No.** The URL is a hardcoded `const API_URL = 'https://api.geoapify.com/v2/places'`. No parameter, query, or path component is derived from user input. |
| Can user input influence query parameters? | Only the **value** of `lat`/`lng` (validated floats) inside the fixed `filter=circle:{lng},{lat},{radius}`; `categories`, `limit`, `apiKey` are all fixed/configured. No injection of `&`, `=`, `?`, or `#` is possible because coordinates are cast to float and radius comes from config. |
| Redirects | Guzzle follows redirects by default; a compromised upstream could redirect, but the API key is carried in the query of the *original* URI and is not re-sent to a redirect target by cURL. Defense-in-depth: set `->withOptions(['allow_redirects' => false])`. |
| SSRF verdict | **Not vulnerable to SSRF.** No user-controlled URL or host anywhere in the integration. |

---

## Input Validation

| Field | Source | Validation | Verdict |
|---|---|---|---|
| `latitude` / `longitude` | Admin form | `numeric`, `between:-90,90` / `-180,180` ([`PropertyRequest`](app/Http/Requests/PropertyRequest.php:66)) | **Validated.** |
| Radius | Config only | `(int) config(...)` | **Not user-controllable.** |
| `nearby_places` JSON (manual) | Admin form | `array`, `name` string max:255, `category` allowlisted, `distance_km` numeric 0–999 ([`PropertyRequest`](app/Http/Requests/PropertyRequest.php:86)) | **Validated.** |
| `place_id`/`name`/`category`/`address`/`website`/`phone` | Geoapify response | Cast to string; category mapped to `NEARBY_CATEGORIES` allowlist; others length-limited by DB column | **Validated for structure/type, not sanitized for HTML** (see SEC-007). |
| Resync route params | Route | `{property}` model-bound; admin-only | **Scoped.** |

No SQLi, no command injection, no header injection found on any of these paths.

---

## Response / Cache Security

| Question | Answer |
|---|---|
| Can API responses cause XSS? | **Not on current paths** — Blade escapes, JS popups use `escapeHtml()`, JSON block uses hex-flag encoding. Latent risk if a future path stops escaping (SEC-007). |
| Can API responses poison application data? | **Yes, partially** — the raw payload is cached verbatim for 24h and persisted into `places` (name/address/website). A compromised Geoapify response writes arbitrary strings to the DB. Distance is recomputed server-side (Haversine), so distance spoofing is not possible. See SEC-007. |
| Can API responses poison the HTTP cache? | No HTTP caching layer for the public page is configured; browser cache only. |
| Are responses validated before being trusted? | Structurally yes (`features` presence/type; per-field casts; category allowlist). Not sanitized for HTML content. |

---

## Authentication & Authorization

- **Resync (POST)** and **nearby-places (GET)** are inside the `['auth','verified','admin']` group → `EnsureUserIsAdmin` requires `isAdmin()` (`super-admin` or `admin` roles).
- **Public property page** is unauthenticated by design and performs zero outbound HTTP; it only *reads* persisted POIs.
- **CSRF:** both admin routes are POST/POST + GET within the web group → CSRF token enforced by the web middleware group on POST.
- **No per-property ACL** (consistent with the whole CMS; no `Policies/`). Informational (SEC-006).
- **No auth bypass found.** Guests/non-admins are rejected with redirect-to-login / 403, and this is covered by `test_resync_route_is_protected_from_guests_and_non_admins` in [`tests/Feature/GeoapifyNearbyPlacesTest.php`](tests/Feature/GeoapifyNearbyPlacesTest.php:597).

---

## Automated Security Checks

CLI tooling was not available in this environment. Completed static equivalents and their status:

| Check | Tool | Status | Result |
|---|---|---|---|
| Secret scanning (working tree) | Manual grep of tracked source for key-like patterns + `.env`/`.env.example` review | ✅ Done | No key in tracked files; `.env` gitignored |
| Secret scanning (git history) | `gitleaks` | ❌ Not runnable | *Unverified* — run `gitleaks detect` locally |
| Static analysis | `semgrep` | ❌ Not runnable | *Unverified* — run `semgrep scan` locally |
| Dependency audit | `composer audit` / `npm audit` | ❌ Not runnable | *Unverified* — run both locally |
| Git diff / status | `git status`, `git diff`, `git diff --cached` | ❌ Not runnable | Reconstructed from `.git` metadata (see Uncommitted Changes Review) |
| PHPUnit security-pinning tests | Source review | ✅ Done | `GeoapifyNearbyPlacesTest` pins: no public outbound HTTP, admin-only resync, cache single-call, missing-key/coords early returns |
| Leaflet SRI | Source review | ✅ Done | Stylesheet + script pinned with `integrity` + `crossorigin` ([`frontend.blade.php`](resources/views/layouts/frontend.blade.php:103)) |

**Recommended local follow-up (non-destructive):**
```bash
git status && git diff && git diff --cached          # confirm working tree
gitleaks detect --source .                            # secret scan incl. history
composer audit                                        # PHP dependency advisories
npm audit                                             # JS dependency advisories
php artisan test --filter=GeoapifyNearbyPlacesTest    # run the pinning suite
```

---

## Recommendations

1. **Rate-limit the resync route** — `throttle:5,10` or a per-property cooldown (SEC-001).
2. **Add an atomic in-flight lock** to the job so concurrent resyncs make at most one upstream call (SEC-002).
3. **Configure a distinct, domain-restricted `GEOAPIFY_MAP_KEY`** and never fall back to the Places key in production (SEC-003).
4. **Set `APP_DEBUG=false` and a non-debug `LOG_LEVEL` in production**; make GeoapifyService exceptions URL/key-free (SEC-004).
5. **Decide the queue driver deliberately**; make `handle()` degrade gracefully under `sync` (SEC-005).
6. **Sanitize Geoapify-persisted strings on write** (strip tags, clamp lengths) as defense-in-depth (SEC-007).
7. **Document role scope** for the `admin` role w.r.t. resync (SEC-006).
8. **Run the automated checks** listed above in a shell-enabled environment before final sign-off.

---

## Final Assessment

The Geoapify integration is **architecturally sound**: admin-only triggering, server-side key handling, fixed request parameters, zero public outbound HTTP, escaped render paths, and a strong feature-test suite that pins the important invariants. No critical or high-severity vulnerability was found in the integration code.

The residual risks are **abuse/operational** (unthrottled resync → quota drain, cache bypass, browser-key fallback, debug-mode error leakage) and are all addressable with configuration and small, additive hardening — none require redesign.

---

### Commit Recommendation

**⚠️ SAFE TO COMMIT WITH CONDITIONS**

**Why:** The changes introduce no new critical or high-severity defect. The two new routes are admin-only and CSRF-protected; the API key is not committed and is not logged; the public page performs no outbound requests; SSRF and injection vectors are closed by construction. The primary blockers are operational conditions that must hold before production:

1. `APP_DEBUG=false` and production `LOG_LEVEL` non-debug (repo-recognized requirement, not new).
2. A distinct domain-restricted `GEOAPIFY_MAP_KEY` set so the Places key is never shipped to browsers.
3. A throttle/cooldown on the resync route (or acceptance of the quota-abuse risk).
4. `gitleaks detect`, `composer audit`, `npm audit`, and a real `git diff` review pass in a shell-enabled environment (not runnable here).

If those conditions are met, the Geoapify integration is safe to commit and ship. If the throttle and key-separation items are skipped, the integration should be considered **DO NOT COMMIT** for production use until they are addressed.
