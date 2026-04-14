# Next Improvement Proposals (2026-04-14)

Create GitHub issues with labels `agent` and `status:triage`; add `type:rfc` where noted.

## Benchmark and optimize WP_HTML_Processor in-body hot path
- Labels: agent, status:triage
- Problem: `WP_HTML_Processor::step_in_body()` (`src/wp-includes/html-api/class-wp-html-processor.php:2265`) is a 1,000+ line hot path with no benchmark coverage, so performance regressions in token handling and active-format reconstruction go unnoticed.
- Scope/Files: `src/wp-includes/html-api/class-wp-html-processor.php` (step_in_body), performance harness in `tests/performance/` (new spec + fixture), and regression tests in `tests/phpunit/tests/html-api/`.
- Tests: Add a performance spec that parses a long mixed-content document to establish baseline throughput/CPU/allocations; capture before/after numbers in the issue. Add unit tests to lock behavior for whitespace-only text, NULL-sequence handling, and complex formatting-element stacks to ensure optimizations do not change outputs.
- RFC: No RFC needed, but require benchmark results and an acceptance threshold (<10% regression) before merge. Hard category: performance hot path that must show before/after benchmarks.

## Finalize deprecation path for wpdb::escape family
- Labels: agent, status:triage
- Problem: Deprecated helpers `wpdb::escape()`, `::_escape()`, and `::_weak_escape()` (since 3.6) still bundle an `addslashes()` fallback unsafe for multibyte encodings, and their lingering XML-RPC usage invites copy/paste reuse despite the deprecation.
- Scope/Files: `src/wp-includes/class-wpdb.php:1254-1346`, usages in `src/wp-includes/class-wp-xmlrpc-server.php`, and related developer notes/changelogs.
- Tests: PHPUnit coverage that `_deprecated_function()` fires once per call, that XML-RPC flows migrate to `prepare()`/`esc_sql()` without behavior drift, and that `_real_escape()` remains stable. Consider a `_doing_it_wrong()` guard when a DB handle is absent to avoid silent `addslashes()`.
- Migration plan:
  - Replace the XML-RPC calls with `$wpdb->prepare()`/`esc_sql()` and confirm identical responses for existing clients.
  - Audit the codebase (core and tests) for any remaining usages and keep the wrappers only as deprecated shims that warn explicitly against use and point to replacements.
  - Add a `_doing_it_wrong()` guard when no DB handle is available so `addslashes()` is never a silent fallback.
  - Publish a dev note/upgrade guide steering plugin authors to `$wpdb->prepare()`, `esc_sql()`, or `esc_like()`; keep the deprecated shims for one major release after the notice before considering removal.
- RFC: No RFC needed; proceed with deprecation plan and documentation.

## Harden WP_Meta_Query SQL generation with edge-case coverage
- Labels: agent, status:triage
- Problem: `WP_Meta_Query::get_sql_for_clause()` (`src/wp-includes/class-wp-meta-query.php:533-805`) spans 270+ lines and handles 17 operators and multiple casts. Nested negative operators, mixed `relation` trees, and binary REGEXP branches have limited coverage, leaving SQL-injection and performance regression risk.
- Scope/Files: `src/wp-includes/class-wp-meta-query.php`, tests in `tests/phpunit/tests/meta/query.php` (add cases for nested `NOT EXISTS` with `LIKE`, binary `NOT REGEXP`, numeric casts, and alias reuse).
- Tests: Add data providers that assert generated SQL stays fully prepared, aliases are reused correctly under `OR`/`AND`, and query counts remain stable. Include regression tests for unusual operator + cast combos to guard both security and performance.
- RFC: No RFC needed; proceed with targeted tests and any minimal SQL-builder fixes revealed by coverage.

## Governance RFC: Define security review policy for high-risk changes
- Labels: agent, status:triage, type:rfc
- Problem: The Agent-Determined “Security Review” area in `GOVERNANCE.md` has no triggers or process, so high-risk changes (auth, capability changes, HTML parsing, SQL generation, file system, external requests) lack consistent scrutiny.
- Proposal: Establish a security review policy requiring a `needs:security-review` label for defined risk areas, mandatory sign-off from the security reviewer persona (dalton), a short threat-model checklist (inputs, sinks, encoding, capability checks), and tests appropriate to the surface (fuzzing for HTML/URL/decoder paths, prepared-statement assertions for SQL, e2e for auth flows). Gate merges in agent workflows until the label is cleared or waived with rationale to keep high-risk changes from landing without review.
- Scope/Files: Policy text in `GOVERNANCE.md`; optional automation hooks in `agent-*` workflows to enforce the label gate.
- Tests: None; RFC defines enforceable policy that will change workflow gates rather than code.
