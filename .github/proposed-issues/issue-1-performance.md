# Performance: Optimize `is_special()` element lookup

## Problem Statement

The `WP_HTML_Processor::is_special()` method uses 100+ consecutive OR conditions with string comparisons, creating a performance bottleneck during HTML parsing. This method is called frequently in the parsing hot path to determine if elements require special handling per the HTML5 specification.

**Current implementation** (src/wp-includes/html-api/class-wp-html-processor.php:6550-6657):
```php
return (
    'ADDRESS' === $tag_name ||
    'APPLET' === $tag_name ||
    'AREA' === $tag_name ||
    // ... 100+ more OR conditions
);
```

This approach has O(n) worst-case performance where n is the number of special elements (100+). On average, this means 50+ string comparisons per call.

## Performance Impact

This is a **hot path** that executes:
- On every tag during document parsing
- Multiple times during insertion mode determination
- During scope checking operations
- In adoption agency algorithm iterations

For a 1MB HTML document with ~10,000 tags, this could mean 500,000+ unnecessary string comparisons.

## Proposed Solution

Convert to a hash-based lookup using a static property:

```php
private static $special_elements = [
    'ADDRESS' => true,
    'APPLET' => true,
    // ... all special elements
];

public static function is_special( $tag_name ) {
    return isset( self::$special_elements[ $tag_name ] );
}
```

This reduces lookup to O(1) constant time.

## Files Involved

- **Primary**: `src/wp-includes/html-api/class-wp-html-processor.php:6550-6657`
- **Tests**: `tests/phpunit/tests/html-api/wpHtmlProcessor.php` (add performance regression test)

## Testing Requirements

1. **Functional tests**: Verify all existing tests pass (no behavior change)
2. **Performance benchmark**: Create benchmark comparing old vs new implementation
   - Test with 1KB, 10KB, 100KB, 1MB HTML documents
   - Measure total parsing time
   - Document percentage improvement
3. **Edge cases**: Test with non-standard tag names, null values, empty strings

## Governance Challenge

**This requires benchmarking standards** which are currently undefined in GOVERNANCE.md (line 54: "Performance — whether PRs need benchmarks, what qualifies as a hot path").

This PR should:
1. Establish a benchmark for this specific change
2. Set precedent for what constitutes a "hot path" requiring performance testing
3. Document acceptable performance regression thresholds

## Labels

- `agent`
- `status:triage`
- `type:enhancement`
- `component:html-api`

## Expected Outcome

- 50-90% reduction in `is_special()` execution time
- Measurable improvement in overall HTML parsing for large documents
- Establishes performance testing methodology for future work
