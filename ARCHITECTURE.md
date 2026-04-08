# Architecture

Auto-generated on 2026-04-08 by the Architect workflow. Do not edit manually.

## System Overview

**20 agent workflows** across 3 tiers, plus 1 composite action(s).

## Diagram

```mermaid
graph TD

    subgraph "Tier 3 — Executive"
        EXECUTIVE["Executive"]
    end

    subgraph "Tier 2 — Supervisors"
        METRICS["Metrics"]
        QA["QA"]
        TRIAGE["Triage"]
    end

    subgraph "Tier 1 — Workers"
        ARCHITECT["Architect"]
        CLEANUP["Post-Merge"]
        COORDINATOR["Coordinator"]
        DISCUSS["Crew Discussion"]
        GUARD["Branch Guard"]
        HEALTH-CHECK["Health Check"]
        ISSUE-GENERATOR["Issue Generator"]
        LEARN["Learn"]
        MERGE["Auto-Merge"]
        PROTECTED-FILES["File Guard"]
        READY["Draft Converter"]
        REFLECTION["SITREP"]
        REVIEW["Preflight Review"]
        REVISE["Revise"]
        SAFETY["Safety"]
        SYNC-UPSTREAM["Upstream Sync"]
    end

    %% Data flows
    ISSUE-GENERATOR -->|status:triage| TRIAGE
    LEARN -->|status:triage| TRIAGE
    TRIAGE -->|status:ready| COORDINATOR
    COORDINATOR -->|assigns| READY["Draft Converter"]
    READY -->|ready_for_review| REVIEW
    REVIEW -->|approve| MERGE["Auto-Merge"]
    REVIEW -->|reject| REVISE
    MERGE -->|merged| CLEANUP
    CLEANUP -->|push to autopilot| QA
    CLEANUP -->|publishes| FL["Flight Log"]
    QA -->|failure| SAFETY
    METRICS -->|data| EXECUTIVE
    EXECUTIVE -->|governance PR| REVIEW

    %% Composite action: publish-to-flight-log
```

## Workflows

| Name | File | Tier | Triggers | Schedule |
|---|---|---|---|---|
| Architect | `agent-architect.yml` | Worker | schedule, manual, push | `0 0 * * 1` |
| Post-Merge | `agent-cleanup.yml` | Worker | PR event | N/A |
| Coordinator | `agent-coordinator.yml` | Worker | schedule, manual, issue event | `0 * * * *` |
| Crew Discussion | `agent-discuss.yml` | Worker | manual, discussion | N/A |
| Executive | `agent-executive.yml` | Executive | schedule, manual | `0 6 * * 4` |
| Branch Guard | `agent-guard.yml` | Worker | PR event | N/A |
| Health Check | `agent-health-check.yml` | Worker | schedule, manual | `0 6 * * *` |
| Issue Generator | `agent-issue-generator.yml` | Worker | schedule, manual | `0 0 * * *` |
| Learn | `agent-learn.yml` | Worker | manual, PR event, issue event | N/A |
| Auto-Merge | `agent-merge.yml` | Worker | review, check_suite | N/A |
| Metrics | `agent-metrics.yml` | Supervisor | schedule, manual | `0 2 * * 3` |
| File Guard | `agent-protected-files.yml` | Worker | PR event | N/A |
| QA | `agent-qa.yml` | Supervisor | schedule, manual, push | `0 4 * * *` |
| Draft Converter | `agent-ready.yml` | Worker | schedule, manual, check_suite | `*/10 * * * *` |
| SITREP | `agent-reflection.yml` | Worker | schedule, manual | `0 0 * * 3,6` |
| Preflight Review | `agent-review.yml` | Worker | manual, PR event | N/A |
| Revise | `agent-revise.yml` | Worker | review | N/A |
| Safety | `agent-safety.yml` | Worker | manual, PR event, check_suite | N/A |
| Upstream Sync | `agent-sync-upstream.yml` | Worker | schedule, manual | `0 3,9,15,21 * * *` |
| Triage | `agent-triage.yml` | Supervisor | manual, issue event | N/A |

## Composite Actions

- `publish-to-flight-log`
