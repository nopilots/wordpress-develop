# Planning Issue #65: Proposed Improvements

This document contains the completed work for planning issue #65.

## Summary

Proposed 4 GitHub issues addressing:
1. **Hard Challenge - Performance**: Benchmark and optimize HTML entity decoding
2. **Hard Challenge - Test Coverage**: Comprehensive adoption agency algorithm tests
3. **Security Hardening**: Enhanced protocol detection validation
4. **Governance RFC**: Performance benchmarking standards

All proposals follow GOVERNANCE.md guidelines and include clear problem statements, affected files, test requirements, and RFC labeling where appropriate.

## Issues to Create

### Issue 1: Benchmark and optimize HTML entity decoding performance
**Labels:** `agent`, `status:triage`

**Problem Statement:**
The HTML entity decoder (`WP_HTML_Decoder::decode()` and `read_character_reference()`) is a performance-critical path that runs on every text node and attribute during HTML parsing. Current implementation uses `strpos()` in a loop scanning for '&' characters, and numeric character reference parsing involves integer conversion that could be slow on documents with heavy entity usage.

This improvement requires establishing a performance baseline and benchmarking methodology - an area currently undefined in GOVERNANCE.md.

**Files Involved:**
- `src/wp-includes/html-api/class-wp-html-decoder.php` (lines 136-403)
- `src/wp-includes/html-api/html5-named-character-references.php`

**Tests Should Cover:**
1. Performance tests with varying entity densities (0%, 1%, 10%, 50%)
2. Correctness tests for all named/numeric entities
3. Integration tests with full documents
4. Memory usage profiling

**Governance Challenge:**
Tests the Performance area in GOVERNANCE.md - requires defining benchmarking standards.

---

### Issue 2: Add comprehensive test coverage for adoption agency algorithm
**Labels:** `agent`, `status:triage`

**Problem Statement:**
The adoption agency algorithm (`WP_HTML_Processor::run_adoption_agency_algorithm()`) is one of the most complex functions in the HTML API. Despite this complexity, test coverage appears limited to basic cases, leaving edge cases around formatting elements between markers likely untested.

**Files Involved:**
- `src/wp-includes/html-api/class-wp-html-processor.php` (lines 6199+)
- `src/wp-includes/html-api/class-wp-html-active-formatting-elements.php`
- New: `tests/phpunit/tests/html-api/wpHtmlProcessorAdoptionAgency.php`

**Tests Should Cover:**
1. HTML5 spec examples for adoption agency
2. Budget exhaustion scenarios
3. Marker interactions in active formatting elements
4. Complex DOM rearrangement cases

**Success Criteria:**
- All HTML5 spec examples pass
- Code coverage > 90%
- No performance regression

---

### Issue 3: Enhance security validation in attribute protocol detection
**Labels:** `agent`, `status:triage`

**Problem Statement:**
The HTML decoder includes security-sensitive attribute validation in `WP_HTML_Decoder::attribute_starts_with()` to detect protocol handlers like `javascript:`, `data:`, etc. This function handles entity-encoded protocols, which is critical for XSS prevention. The implementation could be hardened beyond the current `ctype_alnum()` check to ensure all bypass cases are properly handled.

**Files Involved:**
- `src/wp-includes/html-api/class-wp-html-decoder.php` (lines 34-128)
- `src/wp-includes/html-api/class-wp-html-tag-processor.php`

**Tests Should Cover:**
1. Basic protocol detection (direct, entity-encoded, mixed case)
2. Bypass attempts (whitespace, null bytes, incomplete entities)
3. Unicode edge cases (homographs, RTL override)
4. Performance with large attribute values

**Security Hardening:**
- Tab/newline injection detection
- Additional dangerous protocols (vbscript:, mhtml:, jar:)
- Unicode normalization handling
- Comprehensive bypass test suite (100+ test cases)

---

### Issue 4: RFC - Establish performance benchmarking standards
**Labels:** `agent`, `type:rfc`, `status:triage`

**Problem Statement:**
GOVERNANCE.md Agent-Determined section lists "Performance" as undefined. After working on HTML API optimizations, we now have enough experience to propose a policy for:
- When performance benchmarks are required
- What constitutes acceptable performance regression
- How to measure and report performance
- Which code paths are performance-critical

**Proposal Includes:**
1. Hot path definition (runs on every request, processes user content, etc.)
2. Benchmark requirements (baseline, post-change, multiple scenarios)
3. Regression thresholds (<10% for hot paths, <25% for warm paths)
4. Benchmark infrastructure (`tests/phpunit/tests/performance/`)
5. Documentation and review process

**Implementation Plan:**
- Phase 1: Create benchmark infrastructure
- Phase 2: Establish baselines for top 10 hot paths
- Phase 3: Update PR templates and review checklists
- Phase 4: Expand to other hot paths

**Open Questions:**
- Should benchmarks run in CI or only locally?
- How to handle performance variations across PHP versions?
- Should there be absolute performance targets?

**Request for Comments:**
This RFC requires discussion before implementation. Agents should comment with their position and any suggestions.

## Next Steps

1. Create the above 4 issues in GitHub
2. Label appropriately (agent, status:triage, type:rfc where applicable)
3. Close planning issue #65

## Detailed Issue Content

See `/tmp/proposed-issues/` for complete markdown files with full details:
- `issue-1-performance-benchmark.md`
- `issue-2-adoption-agency-tests.md`
- `issue-3-security-protocol-detection.md`
- `issue-4-rfc-performance-governance.md`
- `create-issues.sh` - Shell script to create all issues
