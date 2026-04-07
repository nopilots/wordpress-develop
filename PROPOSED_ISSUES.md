# Proposed Issues for Weekly Planning

This document contains 3 well-scoped improvements identified for the WordPress codebase. Each issue should be created with labels `agent` and `status:ready`.

---

## Issue 1: Add proper error logging to image EXIF rotation operations

### Problem

During image EXIF rotation operations, errors are currently silently ignored. There are 5 TODO comments in `src/wp-admin/includes/image.php` indicating missing error logging:

- Line 356: During `wp_read_image_metadata()`
- Line 359: During rotation check
- Line 385: During orientation extraction
- Line 483: During actual rotation
- Line 492: After rotation completion

### Files Involved

- `src/wp-admin/includes/image.php` - Add error logging to image rotation functions

### What Tests Should Cover

- Test that errors are properly logged when image rotation fails
- Test that errors are logged when EXIF data cannot be read
- Test that errors are logged when orientation data is missing or invalid
- Verify error messages provide actionable information for debugging

### Success Criteria

- All TODO comments for error logging are resolved
- Failed image operations are logged using `trigger_error()` or appropriate WordPress error handling
- Error messages include context (file path, operation attempted)
- No breaking changes to existing behavior
- Tests verify error logging occurs in failure scenarios

**Labels:** `agent`, `status:ready`

---

## Issue 2: Expand test coverage for WP_HTML_Decoder character reference handling

### Problem

The `WP_HTML_Decoder` class (463 lines) has relatively limited test coverage (141 lines in `tests/phpunit/tests/html-api/wpHtmlDecoder.php`). The current tests focus on basic functionality but lack comprehensive coverage of:

- Various encoding contexts (data vs attribute)
- Edge cases with malformed character references
- Complex Unicode scenarios
- Boundary conditions and error handling

### Files Involved

- `src/wp-includes/html-api/class-wp-html-decoder.php` - The decoder implementation
- `tests/phpunit/tests/html-api/wpHtmlDecoder.php` - Expand test coverage here

### What Tests Should Cover

- Character reference decoding in different contexts (data, attribute values, etc.)
- Malformed character references (missing semicolons, invalid codes)
- Edge cases: extremely long references, Unicode edge cases, surrogate pairs
- Performance: decoding large amounts of text with many references
- All public methods with various input combinations
- Error conditions and proper fallback behavior

### Success Criteria

- Test file size approximately doubles (from 141 to ~280+ lines)
- Code coverage for `WP_HTML_Decoder` exceeds 90%
- All edge cases identified in exploration are covered
- Tests follow existing WordPress test patterns
- No breaking changes to decoder behavior

**Labels:** `agent`, `status:ready`

---

## Issue 3: Add missing PHPDoc parameter descriptions in WP_Admin_Bar class

### Problem

The `WP_Admin_Bar` class has multiple methods with incomplete PHPDoc. Parameter types are specified but descriptions are missing, reducing IDE support and developer documentation quality.

### Files Involved

- `src/wp-includes/class-wp-admin-bar.php` - Lines with missing @param descriptions:
  - Line 184: `add_menu()` - `$args` parameter
  - Line 195: `remove_menu()` - `$id` parameter
  - Line 209: `add_node()` - `$args` parameter
  - Line 293: `_unset_node()` - `$id` parameter
  - Line 460: `_bind()` - Multiple parameters need descriptions
  - Line 492: `_render_container()` - `$nodes` parameter
  - Line 510: `_render_group()` - `$nodes` parameter
  - Line 542: `_render_item()` - `$node` parameter
  - Line 638: `recursive_render()` - `$id` and `$node` parameters

### What Tests Should Cover

- No new tests required (documentation-only change)
- Existing tests should continue to pass
- PHPDoc linting/validation should pass if available

### Success Criteria

- All @param tags in `WP_Admin_Bar` class include descriptions
- Descriptions follow WordPress inline documentation standards
- Descriptions are clear, concise, and helpful for developers
- No changes to function behavior or signatures
- All existing tests continue to pass

**Labels:** `agent`, `status:ready`

---

## Rationale for Selections

These three improvements were selected based on:

1. **Alignment with WordPress principles:**
   - Security and reliability (error logging)
   - Code quality and maintainability (PHPDoc)
   - Test coverage (HTML decoder)

2. **Follow GOVERNANCE.md guardrails:**
   - No breaking changes to public APIs
   - No weakening of security (strengthening error visibility)
   - All changes are testable and verifiable

3. **Well-scoped and actionable:**
   - Clear file paths and line numbers
   - Specific success criteria
   - Can be completed independently

4. **Consider recent work:**
   - HTML API is actively being developed (recent merges)
   - Image processing is core WordPress functionality
   - Admin bar is widely used by plugins and themes
