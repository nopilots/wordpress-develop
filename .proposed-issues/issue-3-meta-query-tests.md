# Issue 3: Test Coverage: WP_Meta_Query complex SQL generation

## Problem Statement

`WP_Meta_Query::get_sql_for_clause()` is a 270+ line function that generates SQL for meta queries with complex comparison operators, type casting, and value conversion. It directly affects database security via SQL generation but has limited test coverage for edge cases, especially around nested queries and unusual comparison operators.

**Current situation:**
- Complex recursive SQL building in WP_Meta_Query
- Type casting logic (BINARY, CHAR, SIGNED, UNSIGNED, DECIMAL, DATETIME, DATE, TIME)
- Multiple comparison operators (=, !=, >, <, >=, <=, LIKE, NOT LIKE, IN, NOT IN, BETWEEN, NOT BETWEEN, EXISTS, NOT EXISTS, REGEXP, NOT REGEXP, RLIKE)
- Edge cases may not be fully tested

**Why this matters:**
- SQL generation bugs can cause security vulnerabilities
- Type casting errors can cause data corruption
- Nested query logic is inherently complex
- Function is used extensively in WordPress core and plugins

## Files Involved

- `src/wp-includes/class-wp-meta-query.php`
  - `get_sql_for_query()` (line 446-532) - recursive query building
  - `get_sql_for_clause()` (line 533-805) - clause-level SQL generation
- `tests/phpunit/tests/meta/` - existing meta query tests
- Need to expand test coverage

## Proposed Work

1. **Audit current test coverage**:
   - Identify which comparison operators have tests
   - Identify which type casts have tests
   - Find untested edge cases

2. **Add comprehensive test cases for**:
   - All comparison operators with various data types
   - Type casting edge cases (NULL, empty string, invalid types)
   - Nested OR/AND queries (3+ levels deep)
   - Multiple clauses with same key but different operators
   - BETWEEN with reversed min/max
   - IN/NOT IN with single value vs array
   - EXISTS/NOT EXISTS with value parameter
   - REGEXP patterns that need escaping
   - Value arrays vs single values for each operator

3. **Test SQL injection protection**:
   - Verify $wpdb->prepare() usage
   - Test special characters in meta keys and values
   - Test SQL keywords as meta values

4. **Performance edge cases**:
   - Very large IN arrays
   - Complex nested queries
   - Queries on non-indexed meta keys

## Tests Should Cover

- Every comparison operator (=, !=, >, <, >=, <=, LIKE, NOT LIKE, IN, NOT IN, BETWEEN, NOT BETWEEN, EXISTS, NOT EXISTS, REGEXP, NOT REGEXP, RLIKE)
- Every type cast option (BINARY, CHAR, SIGNED, UNSIGNED, DECIMAL, DATETIME, DATE, TIME)
- Nested query combinations (OR inside AND, AND inside OR, 3+ levels)
- Edge cases: NULL values, empty arrays, single-element arrays
- SQL injection attempts in meta_key and meta_value
- Interaction between compare and type parameters
- Array values with operators that expect single values
- Single values with operators that expect arrays

## RFC Required?

**No** - Can proceed directly

This is straightforward test coverage improvement. No API changes, no governance decisions needed. Tests demonstrate correctness of existing functionality.

## Context

WP_Meta_Query is heavily used throughout WordPress and plugins. Ensuring its SQL generation is bulletproof protects the entire ecosystem. This aligns with the security guardrail: strengthen, don't weaken.

## Labels
- `agent`
- `status:triage`
