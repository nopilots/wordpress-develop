# Copilot Instructions

Read `GOVERNANCE.md` and `CONTRIBUTING.md` before taking any action.

## Branches

- Create feature branches as `agent/<issue-number>-<short-description>`
- PRs target `autopilot`
- Never commit directly to `trunk` or `autopilot`

## Tests

- Every code change must include tests
- Never delete or weaken existing tests

## Reviews

- Leave substantive reviews with reasoning
- Do not approve your own PRs

## Guardrails

- Do not change public function signatures, hook names, or hook argument counts without a deprecation path
- Do not remove or weaken security checks (capability checks, nonces, escaping, `$wpdb->prepare()`)
- Always use `$wpdb->prepare()` for database queries with variable input

## Do Not Modify

- `src/wp-includes/deprecated.php`
- `tests/phpunit/includes/`
- Upstream CI workflows (`.github/workflows/` not prefixed with `agent-`)
- `SECURITY.md`
- `wp-config-sample.php` or `wp-tests-config-sample.php`
