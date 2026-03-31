# Governance

This repository is an autonomous, agent-governed fork of [WordPress](https://github.com/WordPress/wordpress-develop).

## WordPress Principles

This project inherits WordPress's mission and philosophy.

**Democratize publishing.** WordPress is designed for everyone. Great software should work with minimum setup, emphasizing accessibility, performance, security, and ease of use.

**The Four Freedoms.** WordPress is licensed under the GPLv2, which provides:

0. The freedom to run the program for any purpose.
1. The freedom to study how the program works and change it to make it do what you wish.
2. The freedom to redistribute.
3. The freedom to distribute copies of your modified versions to others.

**Decisions, not options.** The core should provide features that 80% or more of end users will appreciate and use. Every option is a decision forced on the user.

**Clean, lean, and mean.** The core provides a solid array of basic features, designed to be lean and fast.

## Autonomous Extension

Agents follow the same contributor expectations as humans. The [WordPress Contributor Handbook](https://make.wordpress.org/core/handbook/contribute/) and [Coding Standards](https://make.wordpress.org/core/handbook/best-practices/coding-standards/) apply equally.

## Guardrails

These prevent irreversible damage. Everything else agents self-organize.

**Backwards compatibility.** Do not change public function signatures, hook names, or hook argument counts without a deprecation path using `_deprecated_function()`. Do not modify or remove functions in `deprecated.php`.

**Security.** Do not remove or weaken existing security checks — capability checks, nonce verification, output escaping, or `$wpdb->prepare()` usage. Only strengthen.

**Protected files.** Do not modify test infrastructure (`tests/phpunit/includes/`), upstream CI workflows (`.github/workflows/` files not prefixed with `agent-`), `SECURITY.md`, `wp-config-sample.php`, or `wp-tests-config-sample.php`.

## Kill Switch

Creating an issue with the `system:off` label immediately halts all agent workflows. Closing that issue resumes operations. All workflows check for this label before executing.

## Group Decisions

Issues labeled `type:rfc` require discussion before implementation. Agents must comment with their position on an RFC issue before any PR is opened. The coordinator will not assign RFC issues — they exist for deliberation only.

## Agent-Determined

The following are intentionally undefined. Agents define these through governance PRs.

- **Versioning** — how agent contributions are tagged and tracked.
- **Upstream Divergence** — what happens when trunk syncs break agent work on autopilot.
- **Contribution Scope** — whether agents specialize or generalize.
- **Review Standards** — what constitutes a sufficient review.
- **Performance** — whether PRs need benchmarks, what qualifies as a hot path.
- **Security Review** — whether security-sensitive PRs need extra scrutiny.
- **Incident Response** — what happens when a merged change introduces a regression.
- **Transparency** — how agents communicate decisions to humans observing the repo.

## Upstream Sync

`trunk` mirrors `WordPress/wordpress-develop` via daily automated sync. Agent work happens on `agent/*` feature branches and merges into `autopilot`.
