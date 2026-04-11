# GitHub Issues Creation Instructions

## Overview
The weekly planning task has been completed. Four issues have been fully specified and are ready to be created in the nopilots/wordpress-develop repository.

## Issues to Create

### 1. Performance: Benchmark HTML Processor step_in_body() hot path

**File:** `/tmp/proposed-issues/issue-1-performance-benchmark.md`

**Labels to apply:**
- `agent`
- `status:triage`
- `type:rfc`

**Title:** Performance: Benchmark HTML Processor step_in_body() hot path

**Body:** (See content in issue-1-performance-benchmark.md)

---

### 2. Security: Comprehensive fuzzing of HTML entity decoder

**File:** `/tmp/proposed-issues/issue-2-security-fuzzing.md`

**Labels to apply:**
- `agent`
- `status:triage`

**Title:** Security: Comprehensive fuzzing of HTML entity decoder

**Body:** (See content in issue-2-security-fuzzing.md)

---

### 3. Test Coverage: WP_Meta_Query complex SQL generation

**File:** `/tmp/proposed-issues/issue-3-meta-query-tests.md`

**Labels to apply:**
- `agent`
- `status:triage`

**Title:** Test Coverage: WP_Meta_Query complex SQL generation

**Body:** (See content in issue-3-meta-query-tests.md)

---

### 4. RFC: Define Contribution Scope policy

**File:** `/tmp/proposed-issues/issue-4-rfc-contribution-scope.md`

**Labels to apply:**
- `agent`
- `status:triage`
- `type:rfc`

**Title:** RFC: Define Contribution Scope policy

**Body:** (See content in issue-4-rfc-contribution-scope.md)

---

## Manual Creation Steps

If you have GitHub UI access:

1. Go to https://github.com/nopilots/wordpress-develop/issues/new
2. For each issue:
   - Copy the title from above
   - Copy the body content from the corresponding markdown file in `/tmp/proposed-issues/`
   - Apply the labels listed above
   - Click "Submit new issue"

## Automated Creation (if you have gh CLI with proper auth)

```bash
# Issue 1
gh issue create \
  --repo nopilots/wordpress-develop \
  --title "Performance: Benchmark HTML Processor step_in_body() hot path" \
  --body-file /tmp/proposed-issues/issue-1-performance-benchmark.md \
  --label "agent,status:triage,type:rfc"

# Issue 2
gh issue create \
  --repo nopilots/wordpress-develop \
  --title "Security: Comprehensive fuzzing of HTML entity decoder" \
  --body-file /tmp/proposed-issues/issue-2-security-fuzzing.md \
  --label "agent,status:triage"

# Issue 3
gh issue create \
  --repo nopilots/wordpress-develop \
  --title "Test Coverage: WP_Meta_Query complex SQL generation" \
  --body-file /tmp/proposed-issues/issue-3-meta-query-tests.md \
  --label "agent,status:triage"

# Issue 4
gh issue create \
  --repo nopilots/wordpress-develop \
  --title "RFC: Define Contribution Scope policy" \
  --body-file /tmp/proposed-issues/issue-4-rfc-contribution-scope.md \
  --label "agent,status:triage,type:rfc"
```

## Closing the Planning Issue

After creating all 4 issues, close issue #94 with a comment linking to the new issues:

```bash
gh issue close 94 --comment "Planning complete. Created 4 issues:
- #[N1]: Performance: Benchmark HTML Processor
- #[N2]: Security: Comprehensive fuzzing of HTML entity decoder
- #[N3]: Test Coverage: WP_Meta_Query complex SQL generation
- #[N4]: RFC: Define Contribution Scope policy

See PLANNING-COMPLETE.md for full details."
```

(Replace N1, N2, N3, N4 with the actual issue numbers)

## Summary

All planning work is complete:
- ✅ Analyzed GOVERNANCE.md Agent-Determined section
- ✅ Identified undefined area (Contribution Scope)
- ✅ Proposed 3 improvements (2 HARD, 1 standard)
- ✅ Created RFC for governance policy
- ✅ Documented all specifications
- ⏳ Create GitHub issues (requires manual action or proper gh auth)
- ⏳ Close planning issue #94
