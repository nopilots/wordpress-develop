# Proposed WordPress Core Improvements

This document contains 3 well-scoped improvements identified for the WordPress core codebase.

## Issue 1: Add comprehensive edge case tests for wp_cache_flush_group()

**Type**: Test Coverage Enhancement
**Files**: `src/wp-includes/cache.php`, `tests/phpunit/tests/cache.php`
**Priority**: Medium
**Labels**: `agent`, `status:ready`

### Description

The `wp_cache_flush_group()` function, added in WordPress 6.1.0, allows flushing an entire cache group. While a basic test exists in `tests/phpunit/tests/cache.php`, it primarily tests the happy path and behavior with external object cache.

This issue proposes adding comprehensive edge case tests to improve confidence in the function's reliability.

### Problem

The current test at `tests/phpunit/tests/cache.php:206` covers:
- Basic functionality with internal cache
- Behavior with external object cache (expects incorrect usage notice)

Missing test coverage:
- Edge cases: empty group names, special characters, non-string input
- Multiple items in a group being flushed
- Verification that other groups remain untouched after selective flush
- Integration with global/non-persistent groups
- Return value consistency across different scenarios

### Files Involved

- **Source**: `src/wp-includes/cache.php` (lines 290-302: `wp_cache_flush_group()`)
- **Tests**: `tests/phpunit/tests/cache.php` (expand existing test or add new test methods)

### Proposed Tests

1. **Empty/Invalid Input**: Test behavior with empty string, null, non-string input
2. **Special Characters**: Test group names with special characters, Unicode
3. **Multiple Items**: Add multiple items to group, verify all are flushed
4. **Group Isolation**: Verify flushing one group doesn't affect others
5. **Global Groups**: Test behavior with global cache groups
6. **Non-persistent Groups**: Test behavior with non-persistent groups
7. **Return Values**: Verify consistent boolean return values

### Success Criteria

- New tests pass with internal cache
- Tests properly handle external object cache scenarios
- No existing tests are broken
- Test coverage for `wp_cache_flush_group()` increases
- Edge cases are documented in test method doc blocks

### Alignment with WordPress Principles

✅ **Backwards Compatibility**: Only adds tests, no code changes
✅ **Test Coverage**: Improves reliability and confidence
✅ **Code Quality**: Better test coverage helps prevent regressions
✅ **Follows GOVERNANCE.md**: No changes to protected files, strengthens testing

---

## Issue 2: Enhance documentation for wp_prime_option_caches()

**Type**: Documentation Enhancement
**Files**: `src/wp-includes/option.php`
**Priority**: Low
**Labels**: `agent`, `status:ready`

### Description

The `wp_prime_option_caches()` function loads multiple options into the cache in a single database query for performance optimization. However, the function's PHPDoc is missing complete documentation about its return behavior and side effects.

This issue proposes enhancing the function's documentation to improve developer understanding and usage.

### Problem

Current documentation at `src/wp-includes/option.php:270`:
- Has `@param` for the options array
- Missing `@return` tag (function returns void)
- Doesn't document side effects (cache population, database queries)
- Doesn't explain when the function bails early
- No examples of typical usage patterns

This makes it harder for developers to:
- Understand when to use this function vs `get_option()`
- Know what performance benefits they're getting
- Understand the function's behavior with already-cached options

### Files Involved

- **Source**: `src/wp-includes/option.php` (lines 263-347: `wp_prime_option_caches()` and related functions)
- **Tests**: `tests/phpunit/tests/option/` (may benefit from documentation examples)

### Proposed Documentation Enhancements

1. Add `@return void` tag to clarify the function doesn't return a value
2. Add description of side effects:
   - Populates object cache for uncached options
   - Makes database query only for options not in cache
   - Bails early if all options already cached
3. Add usage example in function description
4. Document relationship with `wp_load_alloptions()` and cache layers
5. Clarify performance benefits (reduces N queries to 1 query)

### Example Documentation Addition

```php
/**
 * Loads multiple option values into the cache with a single database query.
 *
 * This function is useful for performance optimization when you need to access
 * multiple options. Instead of triggering separate database queries for each
 * get_option() call, this function loads them all at once.
 *
 * Example usage:
 *     wp_prime_option_caches( array( 'blogname', 'blogdescription', 'posts_per_page' ) );
 *     $name = get_option( 'blogname' );        // Served from cache
 *     $desc = get_option( 'blogdescription' ); // Served from cache
 *
 * The function intelligently skips options that are:
 * - Already in the alloptions cache
 * - Already individually cached
 * - Known to not exist (in notoptions cache)
 *
 * @since 6.4.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string[] $options An array of option names to be loaded.
 * @return void
 */
```

### Success Criteria

- PHPDoc includes `@return void` tag
- Documentation describes performance benefits
- Side effects are clearly documented
- Usage example provided
- No code behavior changes
- Maintains consistency with WordPress inline documentation standards

### Alignment with WordPress Principles

✅ **Backwards Compatibility**: Documentation only, no code changes
✅ **Code Quality**: Better documentation helps developers use the API correctly
✅ **Follows GOVERNANCE.md**: No changes to protected files or function signatures
✅ **Clean and Lean**: Helps developers write more efficient code

---

## Issue 3: Expand test coverage for sanitize_sql_orderby()

**Type**: Test Coverage Enhancement + Security Hardening
**Files**: `src/wp-includes/formatting.php`, `tests/phpunit/tests/formatting/sanitizeOrderby.php`
**Priority**: High (Security-related)
**Labels**: `agent`, `status:ready`

### Description

The `sanitize_sql_orderby()` function validates and sanitizes ORDER BY clauses for SQL queries to prevent SQL injection. However, its test coverage is minimal, and there may be edge cases that aren't thoroughly validated.

This issue proposes expanding test coverage for `sanitize_sql_orderby()` to ensure robust protection against malicious input.

### Problem

Current situation:
- Function at `src/wp-includes/formatting.php:2415`
- Basic tests exist at `tests/phpunit/tests/formatting/sanitizeOrderby.php`
- Limited edge case coverage for complex ORDER BY patterns
- Important security function with minimal comprehensive testing

The function is critical for security because:
- It's used to validate user-controlled ORDER BY clauses
- Malformed input could lead to SQL errors or injection
- It's a last line of defense for query ordering

Missing test coverage:
- Complex multi-column sorting with mixed ASC/DESC
- Edge cases with whitespace and special characters
- Unicode and international characters in column names
- Very long ORDER BY strings
- Malicious patterns that should be rejected
- Table-qualified column names (e.g., `posts.post_date`)

### Files Involved

- **Source**: `src/wp-includes/formatting.php` (lines 2415-2434: `sanitize_sql_orderby()`)
- **Tests**: `tests/phpunit/tests/formatting/sanitizeOrderby.php` (expand existing tests)

### Proposed Test Additions

1. **Complex Valid Patterns**:
   - Multi-column: `column1 ASC, column2 DESC, column3`
   - Table-qualified: `wp_posts.post_date DESC`
   - Multiple spaces: `column1    ASC`

2. **Edge Cases**:
   - Empty string input
   - Only whitespace
   - Very long valid ORDER BY strings
   - Unicode column names (if supported)

3. **Security Tests** (should return false):
   - SQL injection attempts: `post_title; DROP TABLE posts--`
   - Subqueries: `(SELECT ...)`
   - UNION attempts
   - Comment injection: `column1 -- malicious comment`
   - NULL bytes and control characters

4. **Boundary Cases**:
   - Single character column names
   - Maximum length valid strings
   - Mixed case ASC/DESC/asc/desc

### Success Criteria

- Comprehensive test coverage for valid ORDER BY patterns
- Security tests verify malicious patterns are rejected
- Edge cases are properly handled
- No existing tests are broken
- Test documentation explains what each case validates
- All tests pass

### Alignment with WordPress Principles

✅ **Security**: Strengthens testing of security-critical function
✅ **Backwards Compatibility**: Only adds tests, no code changes
✅ **Test Coverage**: Prevents regressions in SQL sanitization
✅ **Follows GOVERNANCE.md**: Does not weaken security, strengthens it
✅ **Code Quality**: More comprehensive testing improves reliability

---

## Summary

These 3 improvements focus on:

1. **Test Coverage** - Expanding tests for `wp_cache_flush_group()` to catch edge cases
2. **Documentation** - Improving PHPDoc for `wp_prime_option_caches()` to help developers
3. **Security** - Strengthening test coverage for the security-critical `sanitize_sql_orderby()` function

All improvements:
- Maintain backwards compatibility
- Follow WordPress coding standards
- Align with GOVERNANCE.md guardrails
- Can be verified with tests (except #2 which is documentation-only)
- Are well-scoped and focused on specific areas
