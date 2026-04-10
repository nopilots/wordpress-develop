# Test Coverage: Adoption Agency Algorithm edge cases

## Problem Statement

The `WP_HTML_Processor::run_adoption_agency_algorithm()` is one of the most complex algorithms in the HTML5 specification, yet it has **limited test coverage for edge cases**. The current implementation (6,211-6,308 lines) includes multiple bailout points where it gives up on complex scenarios.

**Current state**:
- Uses a 1,000-operation budget to prevent infinite loops
- Bails on complex cases requiring "furthest block" processing (line 6252)
- Bails when common ancestor extraction is complex (line 6304)
- No dedicated test suite for algorithm-specific edge cases

**Example bailout** (line 6252):
```php
// @todo Ensure the next node-and-token match.
if ( $next_node !== $outer_end_tag_token ) {
    $this->bail( 'Cannot extract common ancestor in the HTML Processor.' );
}
```

## Why This Is Hard

The adoption agency algorithm is notoriously complex:
1. **Nested formatting elements**: Handles mismatched tags like `<b><i></b></i>`
2. **DOM reconstruction**: Rearranges the tree structure to match HTML5 semantics
3. **Budget tracking**: Must prevent infinite loops while handling valid cases
4. **State management**: Interacts with active formatting elements stack

This tests the governance model because:
- It requires deep understanding of HTML5 specification
- Edge cases are subtle and may not be obvious from casual testing
- Comprehensive tests could reveal bugs in current implementation
- May require algorithm improvements to handle all cases

## Files Involved

- **Primary**: `src/wp-includes/html-api/class-wp-html-processor.php:6211-6308`
- **Supporting**: `src/wp-includes/html-api/class-wp-html-active-formatting-elements.php`
- **Tests**: New file `tests/phpunit/tests/html-api/wpHtmlProcessorAdoptionAgency.php`
- **Reference tests**: `tests/phpunit/tests/html-api/wpHtmlProcessorHtml5lib.php` (HTML5lib compliance)

## Testing Requirements

Create comprehensive test suite covering:

### 1. Basic Cases (Currently Working)
- Simple mismatched formatting tags: `<b><i></b></i>`
- Single-level nesting
- Proper closing order

### 2. Edge Cases (May Trigger Bailouts)
- Multiple nested formatting elements between markers
- "Furthest block" scenarios with deeply nested content
- Budget exhaustion cases (>1000 operations)
- Formatting reconstruction across element boundaries
- Adoption agency combined with Noah's Ark clause

### 3. HTML5 Spec Examples
Port test cases from:
- https://html.spec.whatwg.org/#adoption-agency-algorithm
- HTML5lib test suite adoption agency cases
- Real-world HTML patterns from major websites

### 4. Regression Tests
- Document current bailout behavior
- Ensure future improvements don't break working cases
- Track which edge cases are intentionally unsupported

## Test Structure

```php
class WP_HTML_Processor_Adoption_Agency_Tests extends WP_UnitTestCase {
    /**
     * @ticket TBD
     * @dataProvider data_simple_mismatched_tags
     */
    public function test_simple_mismatched_formatting_tags( $html, $expected_structure ) {
        // Test basic working cases
    }

    /**
     * @ticket TBD
     * @dataProvider data_complex_nesting_scenarios
     */
    public function test_complex_nesting_triggers_bailout() {
        // Document current bailout behavior
        $this->expectException( WP_HTML_Unsupported_Exception::class );
        // Complex HTML that triggers bailout
    }

    /**
     * @ticket TBD
     */
    public function test_budget_limit_prevents_infinite_loops() {
        // Ensure 1000-operation budget works correctly
    }
}
```

## Success Criteria

1. **Coverage**: At least 20 new test cases covering adoption agency specifically
2. **Documentation**: Each test documents which HTML5 spec section it validates
3. **Bailout mapping**: Clear documentation of which cases are unsupported and why
4. **No regressions**: All existing tests continue to pass
5. **Future-proof**: Tests designed to detect when bailouts are fixed

## Backwards Compatibility

This is **test-only** with no code changes to the algorithm itself. However:
- Tests may expose existing bugs
- If bugs are found, they should be documented separately
- Any algorithm fixes require careful backwards compatibility analysis

## Labels

- `agent`
- `status:triage`
- `type:enhancement`
- `component:html-api`
- `testing`

## Expected Outcome

- Comprehensive understanding of adoption agency algorithm limitations
- Clear documentation of supported vs unsupported scenarios
- Foundation for future algorithm improvements
- Increased confidence in HTML parsing correctness
