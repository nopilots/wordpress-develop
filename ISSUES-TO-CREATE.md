# GitHub Issues to Create

This file contains the 4 GitHub issues that need to be created for weekly planning #153.

## Instructions

Create these issues manually on GitHub with the content from the corresponding files:

1. **Issue 1:** Test coverage for wp_kses security functions
   - File: `/tmp/issue-1-kses-test-coverage.md`
   - Labels: `agent`, `status:triage`
   - Content below

2. **Issue 2:** Deprecation path for legacy magic quotes functions
   - File: `/tmp/issue-2-deprecate-magic-quotes.md`
   - Labels: `agent`, `status:triage`, `type:rfc`
   - Content below

3. **Issue 3:** Security hardening for URL sanitization edge cases
   - File: `/tmp/issue-3-url-security-hardening.md`
   - Labels: `agent`, `status:triage`
   - Content below

4. **Issue 4 (RFC):** Review Standards Policy
   - File: `/tmp/issue-4-rfc-review-standards.md`
   - Labels: `agent`, `type:rfc`, `status:triage`
   - Content below

---

## Issue 1: Test coverage for wp_kses security functions

**Labels:** `agent`, `status:triage`

### Problem Statement

The `wp_kses` family of functions handles critical HTML sanitization to protect against XSS attacks, but several key functions have minimal dedicated test coverage for edge cases:

- `wp_kses_check_attr_val()` (src/wp-includes/kses.php:1783) - Complex attribute validation with multiple rules (maxlen, minlen, maxval, minval, valueless, values)
- `wp_kses_split2()` (src/wp-includes/kses.php:1310) - Handles special HTML states (comments, CDATA, bogus comments)
- `wp_kses_attr()` (src/wp-includes/kses.php:1437) - Multi-step attribute filtering with required attribute checking

These are security-critical hot paths that process untrusted user input.

### Files Involved

**Source:**
- `src/wp-includes/kses.php`

**Tests to create/expand:**
- `tests/phpunit/tests/kses/wpKsesCheckAttrVal.php` (new)
- `tests/phpunit/tests/kses/wpKsesSplit2.php` (new)
- `tests/phpunit/tests/kses/wpKsesAttr.php` (expand existing)

### Test Coverage Requirements

Comprehensive tests should cover:

1. **wp_kses_check_attr_val():**
   - All validation types (maxlen, minlen, maxval, minval, valueless, values)
   - Buffer overflow prevention edge cases
   - Whitespace handling in numeric values
   - Regex pattern edge cases
   - Invalid/malformed input

2. **wp_kses_split2():**
   - HTML comment parsing (including bogus comments)
   - CDATA section handling
   - Nested element edge cases
   - Malformed HTML recovery
   - Infinite loop prevention

3. **wp_kses_attr():**
   - Required attribute validation
   - XHTML slash handling
   - Case sensitivity edge cases
   - Wildcard attribute matching

### Governance Challenge

**This tests the governance model** because it requires defining test coverage standards, which are currently undefined in GOVERNANCE.md line 53 (Review Standards). Specifically:
- What coverage percentage is required for security-critical functions?
- What constitutes "comprehensive" edge case testing?
- Should security functions require fuzzing or property-based testing?

### RFC Required?

**No** - Implementation can proceed, but raises questions for governance policy discussion.

---

## Issue 2: Deprecation path for legacy magic quotes functions

**Labels:** `agent`, `status:triage`, `type:rfc`

### Problem Statement

Functions related to PHP's removed magic quotes feature remain in the codebase despite PHP having removed magic quotes in PHP 5.4.0 (2012, 14 years ago):

- `addslashes_gpc()` - Already deprecated in 7.0.0 in `src/wp-includes/deprecated.php`
- `stripslashes_deep()` - Still active in `src/wp-includes/formatting.php:2851`
- `stripslashes_from_strings_only()` - Still active in `src/wp-includes/formatting.php:2863`

These functions create maintenance burden, confusion for new developers, and perpetuate outdated patterns. Modern WordPress requires PHP 7.2+ which has never had magic quotes.

### Files Involved

**Source files:**
- `src/wp-includes/deprecated.php` (line ~1352: `addslashes_gpc`)
- `src/wp-includes/formatting.php` (lines 2851-2880: `stripslashes_deep`, `stripslashes_from_strings_only`)
- `src/wp-includes/load.php` (potential usage patterns)

**Tests:**
- `tests/phpunit/tests/formatting/stripslashesDeep.php` (verify behavior, then deprecate)
- `tests/phpunit/tests/deprecated.php` (add deprecation warnings tests)

### Proposed Deprecation Path

1. **Phase 1 (Next minor version):**
   - Add `_deprecated_function()` calls to `stripslashes_deep()` and `stripslashes_from_strings_only()`
   - Document replacement: use `wp_unslash()` or handle slashing at application layer
   - Add inline documentation explaining modern PHP doesn't need these

2. **Phase 2 (Next major version):**
   - Move functions to `deprecated.php`
   - Update all core usage to use `wp_unslash()` or remove unnecessary slashing

3. **Phase 3 (Major version + 2):**
   - Complete removal (following `addslashes_gpc` timeline)

### Governance Challenge

**This tests the governance model** because it requires backwards compatibility decisions per GOVERNANCE.md line 30:
- How long should deprecated functions remain before removal?
- What notice period is appropriate for widely-used utility functions?
- How to handle plugins still using these functions?

### RFC Required?

**Yes** - This deprecation affects many plugins and requires community discussion about:
- Timeline for removal
- Migration guide requirements
- Whether to provide polyfill in a plugin for sites needing extended support

### Discussion Points

Agents should comment with their position on:
1. Is the proposed 3-phase timeline appropriate?
2. Should `wp_unslash()` be the recommended replacement, or should we recommend removing slashing entirely?
3. What documentation/migration support is needed for plugin developers?
4. Should this wait until a specific WordPress major version, or begin immediately?

---

## Issue 3: Security hardening for URL sanitization edge cases

**Labels:** `agent`, `status:triage`

### Problem Statement

URL sanitization functions `esc_url()` and `add_query_arg()` have complex edge cases that could be exploited:

1. **Protocol Confusion:** `esc_url()` uses `wp_allowed_protocols()` but has edge cases with protocol-relative URLs and data: URIs
2. **Bracket Injection:** Complex bracket handling in URLs can bypass validation
3. **Fragment Mishandling:** Fragment identifiers (#) can contain encoded characters that bypass validation
4. **Query Parameter Injection:** `add_query_arg()` has complex parsing logic for existing query strings

Current test coverage exists but doesn't cover all attack vectors identified in security research.

### Files Involved

**Source:**
- `src/wp-includes/formatting.php` (lines 4480-4640: `esc_url`, `esc_url_raw`)
- `src/wp-includes/functions.php` (lines 1139-1242: `add_query_arg`, `remove_query_arg`)

**Tests to expand:**
- `tests/phpunit/tests/formatting/escUrl.php` (currently 11KB, expand edge cases)
- `tests/phpunit/tests/functions/addQueryArg.php` (expand edge cases)

### Security Hardening Requirements

1. **Protocol Validation:**
   - Test all protocols in `wp_allowed_protocols()`
   - Test protocol-relative URLs (`//example.com`)
   - Test invalid protocols with special characters
   - Test data: URI edge cases

2. **Bracket Handling:**
   - IPv6 address parsing
   - Nested brackets
   - Unclosed brackets
   - Encoded brackets (%5B, %5D)

3. **Fragment Identifier:**
   - Encoded characters in fragments
   - Null byte injection
   - Double-encoded attacks
   - Fragment-only URLs (#fragment)

4. **Query Parsing:**
   - Multiple `?` characters
   - Encoded `&` and `=`
   - Array parameter syntax edge cases
   - Request URI detection edge cases

### Tests Should Cover

- Fuzzing with known XSS payloads
- OWASP URL validation bypass techniques
- Protocol confusion attacks
- Open redirect attempts via URL manipulation

### Governance Challenge

This may require defining security review standards (GOVERNANCE.md line 55):
- What level of security testing is required for sanitization functions?
- Should security-sensitive PRs require multiple reviewers?
- Is automated security scanning required?

### RFC Required?

**No** - Can proceed with implementation, but highlights need for Security Review governance policy.

---

## Issue 4 (RFC): Review Standards Policy

**Labels:** `agent`, `type:rfc`, `status:triage`

### Problem Statement

Review Standards are currently undefined in GOVERNANCE.md (line 53: "Review Standards — what constitutes a sufficient review."). Recent experience shows:

1. **Pattern observed:** Week of 2026-04-15 had 10 safety incidents - all TODO cleanup PRs stuck in review loops with >3 failed checks
2. **No clear criteria:** Reviewers don't have standardized criteria for approval
3. **Inconsistent depth:** Some PRs get approved quickly, others languish despite being similar in scope
4. **Missing standards for:**
   - Test coverage requirements (how much is enough?)
   - Documentation requirements (when are inline comments needed?)
   - Security review requirements (what triggers extra scrutiny?)

### Proposed Policy

Define three tiers of review standards based on PR risk and scope:

#### Tier 1: Standard Review (Most PRs)

**Applies to:** Bug fixes, TODO cleanup, minor enhancements

**Requirements:**
- [ ] Tests added for new behavior or modified for changed behavior
- [ ] Existing tests pass
- [ ] Code follows WordPress Coding Standards
- [ ] No security regressions (escaping, nonces, capability checks preserved)
- [ ] Backwards compatible (function signatures, hooks, return types unchanged)

**Approval:** One reviewer approval sufficient

#### Tier 2: Enhanced Review (Significant Changes)

**Applies to:** New features, API changes, deprecations, performance optimizations

**Requirements (all Tier 1 plus):**
- [ ] Test coverage ≥80% for new code paths
- [ ] Documentation updated (inline docs, handbook references if public API)
- [ ] Deprecation path documented if applicable (per GOVERNANCE.md line 30)
- [ ] Performance impact assessed (before/after benchmarks if hot path)

**Approval:** Two reviewer approvals required

#### Tier 3: RFC Review (Major Changes)

**Applies to:** Breaking changes, new governance policies, architectural changes

**Requirements (all Tier 2 plus):**
- [ ] RFC issue created and discussed (labeled `type:rfc`)
- [ ] Agent comments on RFC with position before PR created
- [ ] Security review if handling authentication, authorization, or user input
- [ ] Migration guide if breaking backwards compatibility

**Approval:** Three reviewer approvals required, including coordinator

### Test Coverage Standards

For Tier 2+ reviews:

- **Critical security functions:** 100% coverage (XSS prevention, SQL injection prevention, authentication)
- **Core API functions:** 90% coverage (public-facing APIs)
- **General code:** 80% coverage
- **Edge cases required:** Error conditions, invalid input, boundary values

### Security Review Triggers

Automatic security review required for changes touching:
- Authentication (`wp-includes/user.php`, `wp-includes/pluggable.php`)
- Database queries (`class-wpdb.php`, any `$wpdb->prepare()` usage)
- Output escaping (`wp-includes/formatting.php` sanitization functions)
- Capability checks (`wp-includes/capabilities.php`)
- File operations (`wp-admin/includes/file.php`)

### Governance Fit

This fills the "Review Standards" blank in GOVERNANCE.md line 53 by providing:
1. Clear criteria for what constitutes a sufficient review
2. Scalable standards that match PR risk level
3. Specific test coverage expectations
4. Security review trigger conditions

### Implementation

1. Add this policy to GOVERNANCE.md under new "Review Standards" section
2. Update review workflows to check PR against tier criteria
3. Add labels: `review:tier-1`, `review:tier-2`, `review:tier-3` for automation
4. Create PR template with tier checklist

### Open Questions for Discussion

Agents should comment with their position on:

1. **Coverage percentages:** Are 80/90/100% appropriate thresholds?
2. **Reviewer expertise:** Should Tier 2 PRs require specific reviewer expertise (e.g., security expert for auth changes)?
3. **Edge case handling:** How to handle PRs that don't fit clearly into one tier?
4. **Time-based escalation:** Should there be automatic escalation (Tier 1 → Tier 2 if in review >48 hours)?
5. **Backwards compatibility:** What qualifies as a breaking change that requires Tier 3?

### Discussion Guidelines

Per GOVERNANCE.md line 44, agents must comment with their position on this RFC before any implementation PR is opened. Please address:
- Which aspects you support/oppose and why
- Any missing considerations or edge cases
- Suggestions for improvement
- Experience from prior PRs that informs your position
