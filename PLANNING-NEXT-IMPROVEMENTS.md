# Weekly Planning — Next Improvements (2026-04-15)

Proposed GitHub issues to label `agent` and `status:triage` unless noted. RFC items also need the `type:rfc` label.

## 1) Performance: Benchmark and optimize `WP_HTML_Processor::step_in_body`
- **Problem**: `WP_HTML_Processor::step_in_body()` (~1,000 LOC) is the primary hot path for block parsing and sanitization. We have no benchmark baselines, and recent HTML-Processor changes may regress throughput without detection.
- **Files**: `src/wp-includes/html-api/class-wp-html-processor.php`, `tests/html-api/` fixtures, potential perf harness in `tests/performance/`.
- **Tests**: Add Playwright performance suites via `npm run test:performance` to capture ops/ms across representative documents (nested blocks, large posts, malformed HTML). Add PHP unit micro-bench harness targeting `step_in_body()` iterations to watch CPU/memory. Failures should trigger on >10% regression from baseline.
- **RFC?**: Proceed directly, but publish benchmark plan and baselines in the issue since governance lacks a performance standard. Treat as a “hard” governance test item.

## 2) Deprecation: Formalize removal path for legacy `wpdb::escape` methods
- **Problem**: `wpdb::escape()`, `_escape()`, and `_weak_escape()` were deprecated in 3.6 (2013) yet still ship and silently call `addslashes()`, which is unsafe for multibyte input. We need a BC-safe deprecation path and caller migration.
- **Files**: `src/wp-includes/class-wpdb.php` (deprecation notices, internal routing), identify and update any in-repo callers, avoid touching `src/wp-includes/deprecated.php`.
- **Tests**: Extend `tests/phpunit/tests/db/realEscape.php` (and add new coverage if needed) to assert `_deprecated_function()` triggers, correct escaping for ascii/multibyte, and ensure modern callers use `esc_sql()` / `prepare()`. Add integration checks that back-compat shims still work for existing plugins when `_deprecated_function()` is suppressed.
- **RFC?**: Can proceed directly with a documented deprecation schedule and back-compat notes in the issue.

## 3) Security: Harden HTML attribute decoding against double-encoded injection
- **Problem**: `WP_HTML_Decoder::attribute_starts_with()` handles multiple escape forms; edge cases in double-encoded or truncated entities risk bypassing attribute prefix checks, enabling XSS in downstream processors.
- **Files**: `src/wp-includes/html-api/class-wp-html-decoder.php`, related fixtures in `tests/phpunit/tests/html-api/wpHtmlDecoder.php`.
- **Tests**: Add fuzz-style PHPUnit cases covering double-encoded entities, invalid codepoints, mixed UTF-8/HTML entity sequences, and malformed numeric references. Include regression cases that mirror real payloads (e.g., `&#x26;#x6A;` with trailing nulls). Validate both positive matches and safe fallbacks.
- **RFC?**: Proceed directly; no API changes expected, but flag as security-sensitive for review.

## Governance RFC: Define performance governance standards
- **Problem**: Performance governance is Agent-Determined; no shared definition of hot paths, regression thresholds, or required benchmarks. This leaves performance work unreviewable and inconsistent.
- **Proposal (RFC)**: Establish a policy that: (a) defines “hot path” as any code executed on front-end render or REST responses >5% CPU in profiling; (b) requires benchmark baselines for hot paths with a regression gate of <10% runtime increase and <5% memory increase; (c) mandates publishing benchmark scripts/results in PR descriptions; (d) introduces a lightweight approval path for perf-only changes with documented metrics; (e) ties perf CI to `tests/performance/playwright.config.js` and PHP micro-bench harnesses.
- **Labels**: `agent`, `status:triage`, `type:rfc`.
