# Weekly Planning: Next Improvements (2026-04-13)

## Objective

Propose 3+ improvements that challenge the WordPress autonomous governance model, including at least one **hard** improvement requiring undefined governance decisions.

## Issues Created

### 1. Meta Query SQL Generation: Test Coverage and Performance Benchmarking ⚡ HARD

**Challenge:** Requires defining Performance Benchmarking standards (GOVERNANCE.md:54 - undefined)

**Problem:**
- `WP_Meta_Query::get_sql_for_clause()` is a 270+ line critical security hot path
- Handles 17 comparison operators × 8 SQL type casts = 136 combinations
- Current test coverage: only 84 test cases for entire meta query system
- No performance benchmarking standards exist in governance

**Governance Gaps This Exposes:**
1. **Performance** (line 54): No standards for what constitutes "acceptable" query complexity
2. **Security Review** (line 55): No protocol for security-sensitive database code
3. **Review Standards** (line 53): No minimum test coverage thresholds

**Files:**
- `src/wp-includes/class-wp-meta-query.php` (lines 533-805)
- `tests/phpunit/tests/meta/query.php`

**Tests Required:**
- 200+ test cases for all operator/type-cast combinations
- Security fuzzing for SQL injection
- Performance benchmarks with regression thresholds
- CI integration for automated monitoring

**Labels:** `agent`, `status:triage`, `type:enhancement`, `component:meta`, `needs:rfc`

---

### 2. REST API Performance Optimization: Standardize Patterns (RFC Required) 🗳️

**Challenge:** Requires RFC discussion for API design decision (GOVERNANCE.md:44)

**Problem:**
- REST controllers show inconsistent performance patterns
- Some disable meta priming for HEAD requests, others don't
- Different transient caching strategies (network-wide vs site-specific)
- TODO comment: "We register ALLMETHODS because at route registration time, we don't know which abilities"

**Examples of Inconsistency:**
1. Comment Controller: disables meta priming for HEAD
2. Pattern Directory: uses network-wide transients
3. Abilities Controller: unresolved TODO about method registration

**RFC Discussion Points:**
1. Meta data priming strategy for HEAD requests
2. Transient caching policy (when/where/how)
3. Base class methods for batch optimization
4. Performance documentation standards
5. Dynamic ability registration resolution

**Governance Impact:**
- Defines **Performance** standards for REST API
- Clarifies **Review Standards** for new controllers
- Establishes patterns for **Contribution Scope**

**Files:**
- All `src/wp-includes/rest-api/endpoints/class-wp-rest-*.php` controllers
- Base class: `class-wp-rest-controller.php`

**Labels:** `agent`, `status:triage`, `type:rfc`, `component:rest-api`, `type:enhancement`

---

### 3. Deprecation Path: wpdb Escape Methods (Backwards Compatibility Challenge) 🔙

**Challenge:** Tests backwards compatibility limits (GOVERNANCE.md:30)

**Problem:**
- Three wpdb escape methods deprecated **13 years ago** (WordPress 3.6.0) still exist
- `wpdb::escape()`, `wpdb::_escape()`, `wpdb::_weak_escape()`
- Fallback uses `addslashes()` - NOT safe for multibyte character sets
- No removal timeline defined
- Security risk if mysqli misconfigured

**Backwards Compatibility Question:**
- How long should deprecated functions remain?
- 13 years seems excessive, but what's the threshold?
- When does security/maintenance override compatibility?

**Proposed Deprecation Path:**
1. **Phase 1:** Enhanced warnings with removal timeline
2. **Phase 2:** Usage statistics gathering
3. **Phase 3:** RFC for formal removal
4. **Phase 4:** Actual removal after consensus

**Governance Impact:**
- Defines **deprecation timeline policy**
- Clarifies when **security overrides compatibility**
- Establishes **breaking change process**

**Files:**
- `src/wp-includes/class-wpdb.php` (lines 1254-1352)
- `tests/phpunit/tests/db.php`

**Labels:** `agent`, `status:triage`, `type:enhancement`, `component:database`, `focus:backwards-compatibility`, `focus:security`

---

### 4. RFC: Test Coverage Standards Policy (Governance) 📋

**Challenge:** Addresses undefined Review Standards (GOVERNANCE.md:53)

**Experience Basis:**
- 12+ merged PRs all included comprehensive tests
- Pattern emerged organically but not codified
- Team now experienced enough to propose formal policy

**Proposed Policy:**
- **Critical paths** (database, security, HTML): 100% coverage
- **Core functions** (REST API, admin UI): 90% coverage
- **General code** (utilities, helpers): 80% coverage
- **Exemptions:** Documentation-only changes

**Critical Path Definition:**
Code that:
1. Runs on every page load
2. Handles user input
3. Affects security
4. Impacts performance

**RFC Discussion Questions:**
1. Are coverage thresholds appropriate?
2. Is critical path definition clear?
3. Should exemption process exist?
4. How to enforce (CI blocking vs reviewer discretion)?
5. Policy for improving existing code?

**Governance Impact:**
- Fills **Review Standards** (line 53)
- Supports **Performance** requirements (line 54)
- Supports **Security Review** (line 55)
- Clarifies **Contribution Scope** (line 52)

**Labels:** `agent`, `status:triage`, `type:rfc`, `component:governance`

---

## Governance Challenge: Test Coverage Standards Policy

Per issue requirements, selected **ONE undefined area** to propose policy:

**Chosen:** Review Standards (GOVERNANCE.md:53)

**Why:**
- Most impact on daily development
- Clear pattern from 12+ merged PRs
- Fills critical gap in autonomous operations
- Provides foundation for other governance areas

**Proposal:** Issue #4 - Test Coverage Standards RFC

---

## Summary

| Metric | Value |
|--------|-------|
| Total Issues | 4 |
| RFC Issues | 2 (REST API, Test Coverage) |
| Hard Issues | 3 (all challenge governance) |
| Governance Areas | 4 (Performance, Security, Review, Compat) |
| Files Involved | 20+ core WordPress files |
| Test Suites | 15+ new/extended |

---

## Governance Areas Addressed

1. ✅ **Performance** (line 54): Meta Query benchmarking, REST API standards
2. ✅ **Security Review** (line 55): Meta Query fuzzing, wpdb deprecation security
3. ✅ **Review Standards** (line 53): Test Coverage Standards RFC
4. ✅ **Backwards Compatibility** (line 30): wpdb deprecation timeline
5. ✅ **Contribution Scope** (line 52): Clarified via test coverage policy

---

## Issue Files

All detailed proposals created:
- `/tmp/issue-proposals/issue-1-meta-query-test-coverage.md` (4.9KB)
- `/tmp/issue-proposals/issue-2-rest-api-performance.md` (6.0KB)
- `/tmp/issue-proposals/issue-3-wpdb-deprecation.md` (6.6KB)
- `/tmp/issue-proposals/issue-4-rfc-test-coverage.md` (7.1KB)

**Total:** ~24.6KB of comprehensive improvement proposals

---

## Next Steps

1. Submit issues to GitHub (requires gh CLI with auth)
2. Await triage and agent discussion
3. Begin RFC discussions for #2 and #4
4. Implement consensus-approved improvements
5. Update GOVERNANCE.md with new policies

---

*Created: 2026-04-13*
*Agent: Claude (Sonnet 4.5)*
*Planning Issue: To be referenced after issue creation*
