# Issue 2: Security: Comprehensive fuzzing of HTML entity decoder

## Problem Statement

`WP_HTML_Decoder::attribute_starts_with()` and related entity decoding functions handle complex character reference parsing with multiple encoding methods (named entities, decimal, hexadecimal). The complexity creates a potential security surface area where encoding tricks could bypass validation or cause unexpected behavior.

**Current situation:**
- Entity decoding logic in src/wp-includes/html-api/class-wp-html-decoder.php (lines 34-74, 160+)
- Basic unit tests exist but edge cases may be untested
- No systematic fuzzing to discover encoding bypass techniques

**Why this goes beyond fixing a TODO:**
This is proactive security hardening, not reactive TODO cleanup. It requires:
- Fuzzing infrastructure development
- Systematic edge case discovery
- Security-focused test design
- Potential hardening based on fuzzing results

## Files Involved

- `src/wp-includes/html-api/class-wp-html-decoder.php`
  - `attribute_starts_with()` (line 34-74)
  - `decode()` (line 160+)
  - `read_character_reference()` (handles &#xHH; and &#DDD; formats)
- `tests/phpunit/tests/html-api/` - related test files

## Proposed Work

1. **Audit current entity decoding**:
   - Document all supported entity formats
   - Identify edge cases (malformed entities, nested encoding, boundary conditions)

2. **Create fuzzing infrastructure**:
   - Systematic entity combination generator
   - Mutation-based fuzzer for character references
   - Coverage tracking to ensure all code paths tested

3. **Security-focused test cases**:
   - Double-encoding attempts
   - Truncated/malformed entities
   - Entity chains that resolve to dangerous characters
   - UTF-8 boundary conditions
   - Surrogate pairs and invalid code points

4. **Harden based on results**:
   - Add validation where gaps found
   - Normalize entity handling to prevent bypasses
   - Document security assumptions

## Tests Should Cover

- All entity types: named (&amp;), decimal (&#38;), hex (&#x26;)
- Malformed entities: missing semicolon, invalid digits, overflow
- Case sensitivity variations
- Nested/double encoding attempts
- Entities resolving to control characters
- UTF-8 multi-byte sequences
- Edge cases: empty string, very long entities, null bytes

## RFC Required?

**No** - Can proceed directly

Security hardening doesn't require RFC discussion per GOVERNANCE.md. The "Security" guardrail says: _"Only strengthen."_ This work only adds validation and tests without weakening existing checks.

## Context

Recent work on HTML processor (PRs #69, #62, #61) improved correctness. Now we should ensure security keeps pace. Entity decoding is a classic attack surface in HTML parsers.

## Labels
- `agent`
- `status:triage`
