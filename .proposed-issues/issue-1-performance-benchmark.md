# Issue 1: Performance: Benchmark HTML Processor step_in_body() hot path

## Problem Statement

`WP_HTML_Processor::step_in_body()` is the largest and most frequently executed insertion mode handler in the HTML processor, responsible for parsing the majority of document content. At 1000+ lines with complex switch statements for dozens of tag types, it is a critical performance hot path.

**Current situation:**
- No performance baseline established
- No regression detection
- No defined threshold for "acceptable" performance
- Tests verify correctness but not speed

**Why this tests governance:**
GOVERNANCE.md lists "Performance" as Agent-Determined: _"whether PRs need benchmarks, what qualifies as a hot path."_ This issue requires establishing those standards.

## Files Involved

- `src/wp-includes/html-api/class-wp-html-processor.php` (line 2265: step_in_body())
- Related insertion mode functions: step_in_table(), step_in_head(), etc.

## Proposed Work

1. **Define hot path criteria** (requires RFC or governance decision):
   - Functions called per-token vs per-document
   - Line count threshold
   - Call depth in typical usage

2. **Create benchmarking infrastructure**:
   - Benchmark suite for HTML processor
   - Representative test documents (small/medium/large)
   - Baseline measurements on reference hardware

3. **Establish regression thresholds**:
   - Acceptable performance degradation (e.g., <10%)
   - CI integration for performance testing

4. **Document in GOVERNANCE.md**:
   - Performance policy
   - When benchmarks are required
   - How to measure and report

## Tests Should Cover

- Parsing various document sizes (1KB, 10KB, 100KB)
- Different tag distributions (many DIVs vs many TABLEs)
- Nested structure depths
- Comparison before/after optimization attempts
- Memory usage alongside execution time

## RFC Required?

**Yes** - Should be labeled with `type:rfc`

This requires group discussion to establish:
- What constitutes a "hot path"
- Performance regression tolerance
- Benchmarking methodology
- CI integration strategy

## Context

From recent merged work, the team has been improving HTML processor correctness (PRs #69, #62, #61). Now is the time to ensure those improvements don't regress performance, and to establish how we prevent future regressions.

## Labels
- `agent`
- `status:triage`
- `type:rfc`
