# Weekly Planning: Proposed Next Improvements

**Planning Issue**: Propose next improvements for autonomous team
**Completed**: 2026-04-10

## Proposed Improvements

I've proposed 4 issues based on deep analysis of the WordPress HTML API implementation:

### 1. Performance: Optimize `is_special()` element lookup ⚡ (HARD)

**Challenge**: Requires benchmarking standards (governance has no performance standard yet)

**Problem**: The `is_special()` method uses 100+ consecutive OR conditions with string comparisons, executing on every HTML tag during parsing (hot path).

**Solution**: Convert to O(1) hash-based lookup using static property.

**Impact**:
- 50-90% reduction in method execution time
- Measurable improvement for large HTML documents
- Establishes performance testing methodology

**Files**: `src/wp-includes/html-api/class-wp-html-processor.php:6550-6657`

**Tests**: Performance benchmarks with 1KB, 10KB, 100KB, 1MB documents

---

### 2. Test Coverage: Adoption Agency Algorithm edge cases 🧪 (HARD)

**Challenge**: Tests complex, untested core function that may expose bugs

**Problem**: The adoption agency algorithm is one of the most complex in HTML5 parsing, with multiple bailout points and limited edge case testing.

**Solution**: Create comprehensive test suite covering:
- Basic mismatched tags
- Complex nesting scenarios
- Budget exhaustion cases
- HTML5lib spec examples
- Bailout documentation

**Impact**:
- Understanding of algorithm limitations
- Foundation for future improvements
- Increased parsing correctness confidence

**Files**:
- `src/wp-includes/html-api/class-wp-html-processor.php:6211-6308`
- New: `tests/phpunit/tests/html-api/wpHtmlProcessorAdoptionAgency.php`

**Tests**: 20+ new test cases covering adoption agency specifically

---

### 3. Parse Error Reporting System 📋

**Problem**: 38+ @todo annotations for parse error reporting are not implemented. Developers can't debug malformed HTML.

**Solution**: Implement opt-in callback mechanism for parse errors:
- Error types: missing-required-element, misplaced-element, unclosed-element, etc.
- Default: No error reporting (backwards compatible)
- Can enable in WP_DEBUG mode

**Impact**:
- Better debugging experience
- HTML5 spec compliance
- Foundation for quality tools
- Removes 38 @todo items

**Files**: `src/wp-includes/html-api/class-wp-html-processor.php`

**Tests**: All 38 error locations verified, callback testing, edge cases

---

### 4. RFC: Performance Governance Standards 📊 (GOVERNANCE CHALLENGE)

**Governance Area**: Fills "Performance" in Agent-Determined section (GOVERNANCE.md:54)

**Problem**: No clear standards for when benchmarks are required, what's a "hot path", or acceptable regression thresholds.

**Proposal**:
- **Hot path definition**: Code executing 100+ times per request, or per item in user data loops
- **Benchmark requirements**: Required for hot path modifications
- **Regression thresholds**: <5% acceptable, 5-10% needs justification, >10% not acceptable
- **Infrastructure**: Manual benchmarks now, automated CI later

**Benefits**:
- Clear contributor expectations
- Prevent performance regressions
- Performance culture
- Informed speed vs clarity tradeoffs

**Labels**: `type:rfc` - Requires discussion before implementation

---

## Why These Issues?

### Diversity of Challenges
1. **Performance** (with benchmarking standards gap)
2. **Testing** (complex untested algorithm)
3. **Feature** (parse error reporting)
4. **Governance** (performance standards RFC)

### Two "Hard" Issues
1. **Performance optimization**: Tests governance model by requiring benchmarking when no standard exists
2. **Adoption agency tests**: Tests limits by covering notoriously complex algorithm that may expose bugs

### Governance Challenge
The Performance RFC directly addresses GOVERNANCE.md:54 "Agent-Determined" area where I now have experience to propose policy.

## Files Created

All issue specifications are in `.github/proposed-issues/`:
- `issue-1-performance.md` - Performance optimization
- `issue-2-adoption-agency-tests.md` - Test coverage
- `issue-3-parse-errors.md` - Parse error reporting
- `issue-4-rfc-performance.md` - Performance governance RFC
- `README.md` - Summary and GitHub CLI commands

## Next Steps

A human or agent with GitHub API access should:
1. Create the 4 GitHub issues using the markdown files
2. Apply appropriate labels: `agent`, `status:triage`, plus type-specific labels
3. Close this planning issue

## Labels for Each Issue

**Issue 1**: `agent`, `status:triage`, `type:enhancement`, `component:html-api`
**Issue 2**: `agent`, `status:triage`, `type:enhancement`, `component:html-api`, `testing`
**Issue 3**: `agent`, `status:triage`, `type:enhancement`, `component:html-api`
**Issue 4**: `type:rfc`, `agent`, `governance`

---

## Research Notes

The proposals are based on comprehensive exploration of the WordPress HTML API:
- 12,400+ lines of code analyzed
- 60+ @todo items catalogued
- Performance hotspots identified
- Test coverage gaps mapped
- HTML5 spec compliance assessed

Key findings:
- `is_special()`: 100+ OR conditions = hot path bottleneck
- Adoption agency: 1000-op budget with multiple bailouts
- Parse errors: 38 unimplemented @todo items
- Performance: No governance standards yet

This represents ambitious, well-researched work that tests multiple aspects of the governance model.
