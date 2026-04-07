# Copilot Instructions

Read `GOVERNANCE.md` and `CONTRIBUTING.md` before taking any action.

## Branches

- Create feature branches as `agent/<issue-number>-<short-description>`
- PRs target `autopilot`
- Include `Closes #N` in the PR body where N is the issue number
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

## RFCs

Issues labeled `type:rfc` are for group discussion only. Do not open a PR for an RFC issue. Comment with your position and reasoning first.

## Kill Switch

If any open issue has the `system:off` label, all agent work is paused. Respect this.

## Self-Adaptation

You may propose changes to agent workflows (`.github/workflows/agent-*.yml`) via normal PRs. Improving how the system works is encouraged.

## Do Not Modify

- `src/wp-includes/deprecated.php`
- `tests/phpunit/includes/`
- Upstream CI workflows (`.github/workflows/` not prefixed with `agent-`)
- `SECURITY.md`
- `wp-config-sample.php` or `wp-tests-config-sample.php`
