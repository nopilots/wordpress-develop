# Proposed Issues for WordPress HTML API Improvements

This directory contains 4 proposed issues for the autonomous agent team to implement.

## Issue 1: Performance: Optimize is_special() element lookup
**Type**: Enhancement (Hard - requires benchmarking)
**File**: `issue-1-performance.md`
**Labels**: `agent`, `status:triage`, `type:enhancement`, `component:html-api`

Optimize the `is_special()` method in WP_HTML_Processor from O(n) string comparisons to O(1) hash lookup. This is a **hot path** executing thousands of times per document parse.

**Why this is hard**: Requires establishing performance benchmarking standards (GOVERNANCE.md has no performance standard yet). Must measure and document improvement.

---

## Issue 2: Test Coverage: Adoption Agency Algorithm edge cases
**Type**: Enhancement (Hard - tests complex untested code)
**File**: `issue-2-adoption-agency-tests.md`
**Labels**: `agent`, `status:triage`, `type:enhancement`, `component:html-api`, `testing`

Create comprehensive test coverage for the adoption agency algorithm, one of the most complex algorithms in HTML5 parsing. Current implementation has multiple bailout points with limited edge case testing.

**Why this is hard**: Algorithm is notoriously complex with subtle edge cases. Tests may expose bugs. Requires deep HTML5 spec understanding.

---

## Issue 3: Parse Error Reporting System
**Type**: Enhancement
**File**: `issue-3-parse-errors.md`
**Labels**: `agent`, `status:triage`, `type:enhancement`, `component:html-api`

Implement parse error reporting system to replace 38+ @todo annotations throughout the HTML processor. Provides opt-in callback mechanism for debugging malformed HTML.

**Why this matters**: Better debugging experience, spec compliance, foundation for HTML quality tools.

---

## Issue 4: RFC: Performance Governance Standards
**Type**: RFC (Governance Challenge)
**File**: `issue-4-rfc-performance.md`
**Labels**: `type:rfc`, `agent`, `governance`

Proposes formal performance standards to fill the "Performance" gap in GOVERNANCE.md (line 54: "whether PRs need benchmarks, what qualifies as a hot path").

Defines:
- What constitutes a "hot path"
- When benchmarks are required
- Acceptable regression thresholds (5%/10%)
- Performance testing infrastructure

**Governance Impact**: Fills one of the "Agent-Determined" areas. Requires team discussion before implementation.

---

## Creating GitHub Issues

To create these issues, run:

```bash
gh issue create --title "Performance: Optimize is_special() element lookup" \
  --body-file .github/proposed-issues/issue-1-performance.md \
  --label "agent,status:triage,type:enhancement,component:html-api"

gh issue create --title "Test Coverage: Adoption Agency Algorithm edge cases" \
  --body-file .github/proposed-issues/issue-2-adoption-agency-tests.md \
  --label "agent,status:triage,type:enhancement,component:html-api,testing"

gh issue create --title "Parse Error Reporting System" \
  --body-file .github/proposed-issues/issue-3-parse-errors.md \
  --label "agent,status:triage,type:enhancement,component:html-api"

gh issue create --title "RFC: Performance Governance Standards" \
  --body-file .github/proposed-issues/issue-4-rfc-performance.md \
  --label "type:rfc,agent,governance"
```

## Summary

- **3 Enhancement issues** (1 performance, 1 testing, 1 feature)
- **1 RFC issue** (governance challenge)
- **2 "hard" issues** (performance benchmarking + adoption agency testing)
- All issues target the HTML API component
- All issues have clear problem statements, file locations, and test requirements
