---
name: testing
description: >-
  Use when writing, running, or fixing automated tests. Trigger phrases: "write
  a test", "add a regression test", "run the tests", "fix failing test", "test
  coverage", "feature test", "factory". Locks work to PHPUnit + SQLite in-memory
  feature tests — NOT Pest.
---

# Purpose
Keep tests consistent with this repo's actual setup: PHPUnit feature tests using
an in-memory SQLite database. This skill stops agents from assuming Pest,
MySQL-specific behavior, or deleting tests to make CI green.

# When to Use
- Adding tests for a new feature or a bug fix (regression test).
- Running or debugging the existing suite.
- Adding/adjusting model factories.

# Rules
- The framework is PHPUnit (`^12.5`), NOT Pest. Feature tests live in
  [`tests/Feature/`](tests/Feature) and extend the project `TestCase`.
- Tests run against SQLite in-memory (see [`phpunit.xml`](phpunit.xml)). Do NOT
  write tests that depend on MySQL-specific behavior (raw MySQL functions, JSON
  path operators, engine-specific SQL, etc.).
- Factories live in [`database/factories/`](database/factories):
  `UserFactory`, `PropertyFactory`, `PostFactory`, `BookingFactory`.
  [`UnitFactory`](database/factories/UnitFactory.php) is STALE/legacy — the `Unit`
  model was refactored out to property types; do not use it or add `Unit` tests.
- Business-critical changes REQUIRE tests: pricing, booking creation, voucher
  application, and auth. Never change canonical services without covering tests.
- For a bug fix, write the failing regression test first, then fix the code.
- Do NOT delete or weaken tests to make the suite pass — fix the code or the
  test's intent. Keep [`AccessibilityTest`](tests/Feature/AccessibilityTest.php) green.

# Workflow
1. Locate the existing test that covers the area (e.g.
   [`BookingFlowTest`](tests/Feature/BookingFlowTest.php),
   [`CrudTest`](tests/Feature/CrudTest.php),
   [`BlogTest`](tests/Feature/BlogTest.php)).
2. Add a focused test using existing factories; use `RefreshDatabase` per suite
   convention.
3. Run just that test while iterating, then the full suite.
4. Keep assertions behavior-focused; avoid MySQL-only assumptions.

# Common Mistakes
- Writing Pest-style tests (`it()/test()`) instead of PHPUnit classes.
- Depending on MySQL behavior that breaks under SQLite in-memory.
- Using the stale `UnitFactory` / adding `Unit` model tests.
- Deleting or skipping a failing test instead of fixing the cause.
- Changing pricing/booking/voucher/auth without a covering test.

# Validation
- `php artisan test` runs the full suite green.
- `php artisan test --filter=BookingFlowTest` (or the relevant test) passes.
- New behavior/bug fix has a dedicated test that fails before the fix.

# Related Files
- [`phpunit.xml`](phpunit.xml)
- [`database/factories/UserFactory.php`](database/factories/UserFactory.php), [`database/factories/PropertyFactory.php`](database/factories/PropertyFactory.php), [`database/factories/PostFactory.php`](database/factories/PostFactory.php), [`database/factories/BookingFactory.php`](database/factories/BookingFactory.php)
- [`tests/Feature/BookingFlowTest.php`](tests/Feature/BookingFlowTest.php), [`tests/Feature/CrudTest.php`](tests/Feature/CrudTest.php), [`tests/Feature/BlogTest.php`](tests/Feature/BlogTest.php), [`tests/Feature/AccessibilityTest.php`](tests/Feature/AccessibilityTest.php)
