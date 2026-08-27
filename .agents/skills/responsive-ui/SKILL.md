---
name: responsive-ui
description: >-
  Use when working on responsive/mobile layout and accessibility: making pages
  work on mobile and desktop, fixing horizontal overflow, responsive images,
  touch targets, sticky CTAs, or the property pricing table on small screens.
  Trigger phrases: "make it responsive", "mobile layout", "it overflows on
  mobile", "sticky CTA", "pricing table on mobile", "breakpoints", "accessible".
  Enforces mobile-first Tailwind without breaking desktop or accessibility tests.
---

# Purpose
The UI is mobile-first Tailwind v3 and there is an existing accessibility test
suite. This skill keeps responsive changes from introducing horizontal overflow,
breaking desktop, or regressing accessibility.

# When to Use
- Making a page/component responsive across mobile and desktop.
- Fixing overflow, wrapping, image sizing, touch targets, or sticky CTAs.
- Reworking a wide table (e.g. the pricing table) for small screens.

# Rules
- Mobile-first: style the base (mobile) case, then layer `sm:`/`md:`/`lg:` for
  larger screens. Do not break the desktop layout to fix mobile or vice versa.
- No accidental horizontal overflow: avoid fixed widths wider than the viewport;
  let long text wrap (`break-words`/`min-w-0` in flex children). Prefer responsive
  utilities over inline pixel widths.
- Images must be responsive (`max-w-full h-auto`) and lazy-loaded below the fold
  (`loading="lazy"`) so render is not blocked.
- Touch targets must be usably large on mobile; ensure interactive elements have
  adequate padding/hit area.
- Sticky CTAs (e.g. a fixed booking bar) must not cover content — add matching
  bottom padding/spacing so the sticky element never hides the last content.
- Property pricing is rendered by
  [`resources/views/properties/_pricing-table.blade.php`](resources/views/properties/_pricing-table.blade.php)
  and the booking form
  [`resources/views/properties/_booking-form.blade.php`](resources/views/properties/_booking-form.blade.php).
  On mobile, prefer stacked cards over a horizontal table when a wide table is not
  comfortable — do not force a desktop table into mobile unchanged (horizontal
  scroll is a last resort, not the default).
- Accessibility: keep [`tests/Feature/AccessibilityTest.php`](tests/Feature/AccessibilityTest.php)
  green — preserve semantic heading order, `alt` text on images, and `<label>`s
  associated with inputs. Do not remove headings/labels to save space.
- Stay within the fixed stack (Tailwind v3 utilities); do not add a CSS framework,
  grid library, or custom breakpoint system.

# Workflow
1. Reproduce the layout at a mobile width AND a desktop width before changing.
2. Apply mobile-first utilities; add `sm:`/`md:`/`lg:` overrides for desktop.
3. For wide tables, provide a stacked-card variant on small screens.
4. Verify no horizontal scrollbar appears at common mobile widths.
5. Re-check headings/alt/labels so the accessibility test stays green.

# Common Mistakes
- Fixed pixel widths / non-wrapping text causing horizontal overflow.
- Forcing a desktop pricing table onto mobile unchanged.
- Sticky CTA covering the final content (missing bottom spacing).
- Dropping headings, `alt` text, or `<label>`s to fit small screens.
- Adding a CSS/grid library instead of Tailwind utilities.

# Validation
- `php artisan test --filter=AccessibilityTest` passes.
- Manually check the page at mobile (~375px) and desktop widths — no horizontal
  overflow, sticky CTA does not cover content, images scale.
- `npm run build` succeeds.

# Related Files
- [`resources/views/properties/_pricing-table.blade.php`](resources/views/properties/_pricing-table.blade.php), [`resources/views/properties/_booking-form.blade.php`](resources/views/properties/_booking-form.blade.php)
- [`resources/views/properties/show.blade.php`](resources/views/properties/show.blade.php), [`resources/views/properties/_card.blade.php`](resources/views/properties/_card.blade.php)
- [`tailwind.config.js`](tailwind.config.js), [`tests/Feature/AccessibilityTest.php`](tests/Feature/AccessibilityTest.php)
