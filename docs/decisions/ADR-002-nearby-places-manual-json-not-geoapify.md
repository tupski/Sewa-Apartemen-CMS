# ADR-002: Nearby Places Are Manual JSON, Not Geoapify

- **Status**: Accepted
- **Date**: 2026-08-27

## Context

The property detail page shows a "what's around" / nearby-places section (points of interest near a listing). A lengthy design document, [`docs/GEOAPIFY-Nearby-Places-Integration.md`](../GEOAPIFY-Nearby-Places-Integration.md), proposes fetching this data automatically from the Geoapify Places API, storing it in dedicated tables, and refreshing it via a queued job.

That design was never built. In the actual codebase:

- There is **no** `NearbyPlacesService`, `Place` model, `places` table, `property_places` table, or corresponding migration.
- There is **no** queued job (`ShouldQueue`) — in fact the app has no custom queued jobs at all, and `.env` runs the queue driver as `sync`.
- Nearby places are stored as a manually-entered JSON array in the `nearby_places` column on `properties` and rendered directly on the detail page.

The design doc exists as a forward-looking proposal, which makes it easy to mistake for an implemented feature.

## Decision

Nearby places are, and remain, **manually-entered JSON** on `properties.nearby_places`. The Geoapify document is treated as a **design specification only** — not a description of existing behavior. No external Places API call is made on any request path.

## Consequences

- **Positive**: no external API dependency, no API key management, no latency or failure mode added to page render. Editors have full control over the displayed places.
- **Positive**: documentation and skills can state plainly that nearby places are manual, preventing agents from "wiring up" a non-existent integration.
- **Constraint**: nearby-places content is only as good/fresh as what an admin enters by hand.
- **Constraint**: no blocking external API calls may be added to request paths (see [`AGENTS.md` §14](../../AGENTS.md)).
- **If Geoapify is implemented later**, it is a provider/infrastructure change that requires:
  1. Explicit user approval (per [`AGENTS.md` §20](../../AGENTS.md)).
  2. A background/queued mechanism (the queue driver choice must be confirmed — it is currently `sync`).
  3. Caching of results so page render never blocks on the external API.
  4. New models/tables/migrations as the design doc describes, plus tests.
  Until then, do not claim the integration exists.

## References

- [`docs/GEOAPIFY-Nearby-Places-Integration.md`](../GEOAPIFY-Nearby-Places-Integration.md) (design spec only)
- [`docs/domain/property.md`](../domain/property.md)
- [`AGENTS.md` §4, §14, §23](../../AGENTS.md)
- Skill: [`.agents/skills/nearby-places/SKILL.md`](../../.agents/skills/nearby-places/SKILL.md)
