---
name: frontend
description: >-
  Use when building or editing frontend UI: Blade views/components, Alpine.js
  interactivity, Hotwired Turbo navigation, Tailwind styling, or Vite assets.
  Trigger phrases: "add a component", "build a page", "make it interactive",
  "modal/dropdown", "Alpine x-data", "Turbo", "add a button/input", "translate
  UI string", "edit app.js". Locks work to the fixed Blade + Alpine + Turbo +
  Tailwind stack — NO Livewire/Vue/React/Inertia.
---

# Purpose
The frontend stack is fixed and there is a mature set of reusable Blade
components. This skill stops agents from introducing a new framework, reinventing
existing components, hardcoding UI strings, or fighting Turbo navigation.

# When to Use
- Adding/editing any Blade view or component.
- Adding client-side interactivity or page transitions.
- Touching [`resources/js/app.js`](resources/js/app.js) or Tailwind styling.

# Rules
- Stack is fixed: Blade + Alpine.js 3 + Hotwired Turbo (Turbo Drive) + Tailwind
  CSS v3 + Vite 8. Do NOT add Livewire, Vue, React, Inertia, jQuery, or any
  component/CSS library.
- Layouts — pick the right one:
  [`layouts/frontend.blade.php`](resources/views/layouts/frontend.blade.php) for
  public pages, [`layouts/admin.blade.php`](resources/views/layouts/admin.blade.php)
  for admin, and [`layouts/app.blade.php`](resources/views/layouts/app.blade.php) /
  [`layouts/guest.blade.php`](resources/views/layouts/guest.blade.php) for the
  Breeze auth scaffold.
- Reuse existing components in [`resources/views/components/`](resources/views/components)
  before building new ones: `text-input`, `password-input`, `money-input`,
  `input-label`, `input-error`, `primary-button`, `secondary-button`,
  `danger-button`, `modal`, `dropdown`, `dropdown-link`, `nav-link`,
  `responsive-nav-link`, `search-input`, `share-modal`, `seo`, `captcha`,
  `analytics`. Don't re-implement buttons/inputs/modals.
- Use Alpine for interactivity via `x-data`/`x-show`/`x-transition`/`x-model`
  (booking form, gallery, modals, dropdowns). Prefer Alpine over vanilla DOM
  scripts or jQuery.
- Turbo Drive handles navigation. Do not force full-page reloads or disable Turbo
  without a concrete reason; register any persistent JS so it survives Turbo visits
  (initialize in [`resources/js/app.js`](resources/js/app.js), not scattered inline
  scripts).
- Keep JS in [`resources/js/app.js`](resources/js/app.js) or small inline Alpine
  directives; avoid standalone `<script>` blocks where the logic can live in app.js.
- Translate all UI strings with `__('...')` using keys in
  [`lang/en.json`](lang/en.json) and [`lang/id.json`](lang/id.json). Never hardcode
  English/Indonesian copy in views; add the key to BOTH files.
- No business logic in Blade — views render, services compute. At most, display
  logic (formatting, iteration). Render money with `<x-money-input>` and escape
  output with `{{ }}`; use `{!! !!}` only for intentionally-sanitized HTML.

# Workflow
1. Check [`resources/views/components/`](resources/views/components) for an existing
   component before creating one.
2. Choose the correct layout for the page context (public vs admin vs auth).
3. Add interactivity with Alpine; keep shared JS in `app.js`.
4. Add any new UI string to both `lang/en.json` and `lang/id.json` and use `__()`.
5. Run `npm run dev`/`build` (Vite) and verify Turbo navigation still works.

# Common Mistakes
- Introducing Vue/React/Livewire/Inertia/jQuery or a CSS/component library.
- Re-implementing an input/button/modal that already exists as a component.
- Hardcoding UI strings instead of `__()` keys in both lang files.
- Disabling Turbo or adding full-reload navigation without reason.
- Putting business logic or price math in a Blade view.

# Validation
- `npm run build` (Vite) succeeds; assets load.
- New strings resolve in both `en` and `id`.
- `php artisan test --filter=AccessibilityTest` still passes for touched pages.

# Related Files
- [`resources/views/layouts/`](resources/views/layouts), [`resources/views/components/`](resources/views/components)
- [`resources/js/app.js`](resources/js/app.js), [`tailwind.config.js`](tailwind.config.js)
- [`lang/en.json`](lang/en.json), [`lang/id.json`](lang/id.json)
- [`resources/views/components/money-input.blade.php`](resources/views/components/money-input.blade.php), [`resources/views/components/modal.blade.php`](resources/views/components/modal.blade.php)
