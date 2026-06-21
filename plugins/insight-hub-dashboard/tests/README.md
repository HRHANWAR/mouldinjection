# Material price-check tests

Self-contained tests for the 24-hour material price checking/reference system.
**No WordPress, no database, and no real network calls** — external HTTP is
mocked via `$GLOBALS['ih_http_handler']` (PHP) and stubbed globals (JS).

## PHP — pure logic + providers

Covers: badge mapping, quote-selection order (incl. "never Live unless verified
licensed feed"), unit + currency conversion, normalisation, and provider
source-failure handling.

```bash
php tests/test-material-pricing.php
```

`tests/bootstrap.php` provides the minimal WordPress shims (`get_option`,
`wp_remote_get`, `sanitize_text_field`, …) and loads only the side-effect-free
classes. Exit code `0` = all pass.

## JS — client badge mapping

Mirrors the PHP badge assertions for `badgeForSource()` in
`js/material-pricing.js`.

```bash
node tests/test-badge.js
```

## Notes

- Tests define `IH_PRICE_FEED_URL` so the licensed-feed branch is exercisable;
  the "Live" badge still requires a **verified** item.
- To mock a provider HTTP response in PHP, set
  `$GLOBALS['ih_http_handler'] = function ($url, $args) { return ['code'=>200,'body'=>'...']; };`
  (or return a `WP_Error` to simulate a transport failure).
