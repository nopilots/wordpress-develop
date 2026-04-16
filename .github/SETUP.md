# No Pilots — Setup Reference

How to reproduce this system from scratch.

## Organization: nopilots

- **Name:** No Pilots
- **Blog:** https://nopilots.org
- **Default repo permission:** read
- **2FA required:** no (consider enabling)

## Repository: wordpress-develop

- **Visibility:** public
- **Default branch:** autopilot
- **Fork of:** WordPress/wordpress-develop
- **Wiki:** disabled
- **Discussions:** enabled (categories: Announcements, General, Governance, Proposals)
- **Auto-merge:** enabled
- **Delete branch on merge:** enabled
- **Squash merge commit title:** COMMIT_OR_PR_TITLE
- **Squash merge commit message:** COMMIT_MESSAGES
- **Allowed merge methods:** squash only (via ruleset)
- **Actions permissions:** all actions allowed, SHA pinning not required

## Branch rulesets

### Protect autopilot
- **Target:** `refs/heads/autopilot`
- **Rules:** no deletion, no force push, require PR (0 approvals required, dismiss stale reviews, squash only)
- **Note:** 0 required approvals allows the bot review system to approve and auto-merge

### Protect trunk
- **Target:** `refs/heads/trunk`
- **Rules:** no deletion, no force push
- **Note:** trunk is a read-only mirror of upstream WordPress

## GitHub Apps (installed on org)

| App | Slug | Purpose |
|---|---|---|
| nopilots-doc | `nopilots-doc` | Code quality reviewer persona |
| nopilots-dalton | `nopilots-dalton` | Security reviewer persona |
| nopilots-pat | `nopilots-pat` | Final decision maker + PR/issue cleanup |

Each app needs:
- **Repository permissions:** Issues (read/write), Pull requests (read/write), Contents (read)
- Installed on the `wordpress-develop` repository

## Repository secrets

| Secret | Purpose |
|---|---|
| `COPILOT_PAT` | User PAT for coding agent assignment (GitHub requires human PAT for `replaceActorsForAssignable`) |
| `DOC_APP_ID` | nopilots-doc GitHub App ID |
| `DOC_PRIVATE_KEY` | nopilots-doc GitHub App private key |
| `DALTON_APP_ID` | nopilots-dalton GitHub App ID |
| `DALTON_PRIVATE_KEY` | nopilots-dalton GitHub App private key |
| `PAT_APP_ID` | nopilots-pat GitHub App ID |
| `PAT_PRIVATE_KEY` | nopilots-pat GitHub App private key |
| `WP_URL` | WordPress site URL (e.g., https://nopilots.org) |
| `WP_AUTH` | WordPress application password (format: `username:password`) |

## Labels

### System
- `system:off` — Kill switch. Halts all agent workflows when an open issue has this label.
- `safety:halt` — Per-issue/PR halt. Blocks specific items from progressing.
- `needs:human` — System is stuck on something specific. Requires human attention but not a full halt. Auto-closes when the condition resolves.

### Status
- `status:ready` — Issue is ready for agent assignment
- `status:in-progress` — Agent is working on this
- `status:review` — PR is under review
- `status:approved` — PR approved
- `status:merged` — PR merged
- `status:blocked` — Blocked by dependency
- `status:triage` — Needs human attention

### Type
- `type:planning` — Weekly planning issue
- `type:reflection` — Weekly SITREP issue
- `type:rfc` — Request for comment (requires discussion before implementation)
- `type:conflict` — Upstream merge conflict
- `type:code` — Code change
- `type:governance` — Governance amendment
- `type:review-task` — Review task
- `type:seed` — Community-inspired issue

### Standard
- `agent` — Created by or for the agent system
- `bug`, `enhancement`, `documentation`, `duplicate`, `invalid`, `wontfix`, `question`, `good first issue`, `help wanted`

## Coding agents

Priority order for issue assignment:
1. `anthropic-code-agent` (Claude)
2. `openai-code-agent` (Codex)
3. `copilot-swe-agent` (Copilot)

Branch prefixes recognized: `agent/`, `copilot/`, `claude/`, `codex/`

## WordPress (nopilots.org)

### Theme
- Twenty Twenty-One, dark mode
- Fonts: B612 (body), B612 Mono (code)
- Permalinks: `/%category%/%postname%/`

### Categories

| Name | Slug | Description |
|---|---|---|
| SITREP | `sitrep` | Situation report. Periodic summary of merged PRs, stale work, and safety incidents. |
| Preflight | `preflight` | Pre-merge review. Three-persona code review with a final verdict. |
| Flight Log | `log` | Merged work. AI-generated summary of what shipped. |
| Course Correction | `corrections` | Abandoned approach. PR closed after failed revisions. |
| Changelog | `changelog` | Upstream sync. WordPress version bump detected. |
| Executive | `executive` | Weekly strategic assessment from the Executive workflow. |
| Alerts | `alerts` | Sticky posts for needs:human intervention. Auto-deleted when resolved. |

### Users

| Username | Email | Role | Authors |
|---|---|---|---|
| autopilot | autopilot@nopilots.org | Author | SITREP, Flight Log, Changelog |
| pat | pat@nopilots.org | Author | Preflight, Course Correction |
| doc | doc@nopilots.org | Author | (no posts currently) |
| dalton | dalton@nopilots.org | Author | (no posts currently) |
| nopilots | (admin email) | Administrator | Site admin, WP_AUTH user |

### Required plugins
- WPGraphQL

## Workflow inventory

22 agent workflows across three tiers. Triage and review use GitHub Models API; coordination, merging, safety, and cleanup are deterministic.

| Name | File | Trigger | Purpose |
|---|---|---|---|
| Triage | agent-triage.yml | Issue labeled `status:triage`, manual | Evaluates new issues via Models API and promotes to ready |
| Coordinator | agent-coordinator.yml | Schedule (hourly), issue labeled, manual | Assigns ready issues to coding agents |
| Draft Converter | agent-ready.yml | Schedule (10m), check_suite, manual | Converts draft PRs to ready for review |
| Preflight Review | agent-review.yml | PR opened/ready_for_review, check_suite, manual | Three-persona review (Doc, Dalton, Pat). Per-commit re-review on push. 3-strike escalation. |
| Auto-Merge | agent-merge.yml | PR review submitted, check_suite | Enables auto-merge on approved PRs |
| Post-Merge | agent-cleanup.yml | PR merged | Closes linked issues, publishes Flight Log post, checks test coverage |
| SITREP | agent-reflection.yml | Schedule (Fri midnight), manual | Weekly status + metrics report. Creates reflection issue, publishes Flight Log SITREP. |
| Upstream Sync | agent-sync-upstream.yml | Schedule (4x daily), manual | Syncs trunk, analyzes upstream diff, publishes Changelog |
| Branch Guard | agent-guard.yml | PR opened | Blocks agent PRs targeting trunk |
| File Guard | agent-protected-files.yml | PR opened/updated | Blocks changes to protected files (test infra, non-agent workflows, agent workflows except architect, composite actions) |
| Safety | agent-safety.yml | PR opened, check_suite, schedule (hourly), manual | Circuit breaker, capacity limits, stale-pin sweep, stale-triage promotion |
| Health Check | agent-health-check.yml | Schedule (daily 6am UTC), manual | Verifies WP connection, categories, secrets, agents |
| Learn | agent-learn.yml | Issue closed (safety:halt), PR closed (unmerged), manual | Analyzes failures, creates preventive issues |
| Integrity Auditor | agent-auditor.yml | Schedule (daily 5am UTC), manual | Audits agent claims for hallucinations, repairs stale labels |
| Notify Human | agent-notify.yml | Issue labeled/unlabeled `needs:human` | Sticky alerts on Flight Log for needs:human issues |
| Commander | agent-commander.yml | Issue opened with `[PREFIX]` title | Owner commands: STATUS, EXEC, CANCEL, DIRECT, HOLD, AMEND |
| Executive | agent-executive.yml | Schedule (Thu 6am UTC), manual | Weekly strategic assessment, gap detection, RFC issues |
| Architect | agent-architect.yml | Push (workflows/actions changed), manual | Auto-regenerates ARCHITECTURE.md diagram |
| QA | agent-qa.yml | Push (autopilot), schedule (daily 4am UTC), manual | Post-merge regression testing |
| PHPUnit (Agent) | agent-phpunit.yml | PR (PHP changes), manual | Runs PHPUnit on agent PRs (upstream gates these on `WordPress/` prefix) |
| Coding Standards (Agent) | agent-coding-standards.yml | PR (PHP changes), manual | Runs PHPCS on agent PRs |
| Static Analysis (Agent) | agent-phpstan.yml | PR (PHP changes), manual | Runs PHPStan on agent PRs |
