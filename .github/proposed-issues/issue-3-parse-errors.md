# Parse Error Reporting System

## Problem Statement

The WP_HTML_Processor currently has **38 @todo annotations** for parse error reporting that are not implemented. Per the HTML5 specification, parsers should report parse errors for invalid HTML, but WordPress currently suppresses all error information.

**Current state**:
```php
// Example from line 1966
$this->state->insertion_mode = WP_HTML_Processor_State::INSERTION_MODE_IN_HEAD;
// @todo Indicate parse error.
$this->insert_html_element( $this->state->current_token );
```

This pattern appears 38+ times across:
- Insertion mode transitions (18 occurrences)
- Missing required elements (8 occurrences)
- Misplaced content (6 occurrences)
- Foreign element handling (4 occurrences)
- Other spec violations (2+ occurrences)

## Impact

Without parse error reporting:
1. **Debugging is difficult**: Developers can't tell if HTML is malformed
2. **Silent failures**: Invalid HTML may produce unexpected output
3. **Spec non-compliance**: HTML5 requires parse error reporting
4. **Quality feedback**: Can't warn about potential issues in content

## Proposed Solution

Implement a lightweight parse error reporting system with:

### 1. Error Callback Mechanism
```php
class WP_HTML_Processor {
    private $error_callback = null;

    public function set_error_callback( callable $callback ) {
        $this->error_callback = $callback;
    }

    private function report_parse_error( $type, $message, $position ) {
        if ( $this->error_callback ) {
            call_user_func( $this->error_callback, [
                'type' => $type,
                'message' => $message,
                'position' => $position,
                'line' => $this->calculate_line_number(),
            ] );
        }
    }
}
```

### 2. Error Types
- `missing-required-element` - e.g., missing `<head>` or `<body>`
- `misplaced-element` - e.g., `<div>` in `<head>`
- `unclosed-element` - e.g., missing closing tag
- `invalid-nesting` - e.g., `<p>` inside `<p>`
- `unexpected-token` - Any other spec violation

### 3. Opt-in by Default
- Default: No error reporting (backwards compatible)
- Developers can enable via `set_error_callback()`
- WordPress could enable in `WP_DEBUG` mode

## Files Involved

- **Primary**: `src/wp-includes/html-api/class-wp-html-processor.php`
  - Add error callback mechanism
  - Replace 38+ @todo comments with actual error reporting
- **New**: `src/wp-includes/html-api/class-wp-html-parse-error.php` (optional value object)
- **Tests**: `tests/phpunit/tests/html-api/wpHtmlProcessorParseErrors.php`
  - Test each error type is reported correctly
  - Test callback invocation
  - Test opt-in behavior

## Testing Requirements

1. **Functional tests**:
   - Verify all 38 error locations report correctly
   - Test callback receives correct error information
   - Verify no errors reported without callback
   - Test multiple errors in single document

2. **Edge cases**:
   - Null callback handling
   - Callback throwing exception
   - Very large documents with many errors
   - Error position accuracy

3. **Backwards compatibility**:
   - All existing tests pass without modification
   - No performance impact when callback not set
   - No behavior changes to parsing output

## Backwards Compatibility

This is **fully backwards compatible**:
- Default behavior unchanged (no error reporting)
- Parsing output remains identical
- New functionality is opt-in only
- No public API changes to existing methods

## Security Considerations

- Error messages must not expose sensitive information
- Position tracking should not allow information disclosure
- Callback execution must not affect parsing security
- Error messages should be sanitized if displayed to users

## Labels

- `agent`
- `status:triage`
- `type:enhancement`
- `component:html-api`

## Expected Outcome

- Spec-compliant parse error reporting
- Better debugging experience for developers
- Foundation for HTML quality tools
- No impact on existing functionality
- Removes 38 @todo items from codebase
