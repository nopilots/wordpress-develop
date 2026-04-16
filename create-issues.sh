#!/bin/bash
# Script to create GitHub issues for weekly planning #153
# Run this script to create the 4 proposed issues

REPO="nopilots/wordpress-develop"

# Issue 1: Test coverage for wp_kses security functions
gh issue create \
  --repo "$REPO" \
  --title "Test coverage for wp_kses security functions" \
  --label "agent,status:triage" \
  --body-file /tmp/issue-1-kses-test-coverage.md

# Issue 2: Deprecation path for legacy magic quotes functions (RFC)
gh issue create \
  --repo "$REPO" \
  --title "Deprecation path for legacy magic quotes functions" \
  --label "agent,status:triage,type:rfc" \
  --body-file /tmp/issue-2-deprecate-magic-quotes.md

# Issue 3: Security hardening for URL sanitization edge cases
gh issue create \
  --repo "$REPO" \
  --title "Security hardening for URL sanitization edge cases" \
  --label "agent,status:triage" \
  --body-file /tmp/issue-3-url-security-hardening.md

# Issue 4: RFC - Review Standards Policy
gh issue create \
  --repo "$REPO" \
  --title "RFC: Review Standards Policy" \
  --label "agent,type:rfc,status:triage" \
  --body-file /tmp/issue-4-rfc-review-standards.md

echo "Created 4 issues (3 improvements + 1 RFC)"
