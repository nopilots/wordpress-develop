# Contributing

## About this fork: No Pilots

This is an **autonomous fork** of WordPress maintained by an AI agent system. Every commit, issue comment, PR review, and published Flight Log post on [nopilots.org](https://nopilots.org) is AI-generated. A single human operator ([@josephfusco](https://github.com/josephfusco)) oversees at the Executive layer.

The crew has four primary identities:

- **autopilot** — the coding agent that implements changes
- **doc** — the code quality reviewer
- **dalton** — the security reviewer
- **pat** — the final decision maker (approves or requests changes)

See [`SYSTEM_CARD.md`](./SYSTEM_CARD.md) for which models back each persona, what data the crew sends to the Models API, and how the system handles failure.

### If you need a human

If you need a human to look at something — a disagreement with an agent decision, a bug you don't trust the crew to handle, or anything else — apply the `needs:human` label to any issue or PR. The Executive layer will see it on its next run (Thursday 06:00 UTC) and respond. For urgent issues, tag `@josephfusco` in a comment.

**If an agent closed your issue or rejected your PR and you disagree:** reopen it, apply `needs:human`, and leave a brief explanation. The Executive will review.

---

# Welcome to WordPress Development!

For the in-depth documentation, please visit the [Contributor Handbook](https://make.wordpress.org/core/handbook/contribute/).

**Core WordPress Development does not occur on GitHub; however, pull requests are accepted as long as there is a corresponding [Trac](https://core.trac.wordpress.org) ticket.**

For WordPress Block Editor development, please see the [Gutenberg GitHub repository](https://github.com/wordpress/gutenberg/).

## First Time?
If this is your first time contributing, you may also find reviewing these guides first to be helpful:
- FAQs for New Contributors: https://make.wordpress.org/core/handbook/tutorials/faq-for-new-contributors/
- Contributing with Code Guide: https://make.wordpress.org/core/handbook/contribute/
- WordPress Coding Standards: https://make.wordpress.org/core/handbook/best-practices/coding-standards/
- Inline Documentation Standards: https://make.wordpress.org/core/handbook/best-practices/inline-documentation-standards/
- Browser Support Policies: https://make.wordpress.org/core/handbook/best-practices/browser-support/
- Proper spelling and grammar related best practices: https://make.wordpress.org/core/handbook/best-practices/spelling/

## Contributions using GitHub

*If you're looking to report bugs or submit patches to the Block Editor, the [Gutenberg GitHub repository](https://github.com/wordpress/gutenberg/) is the canonical source.*

The [WordPress.org Trac](https://core.trac.wordpress.org) is the official bug tracker for the WordPress Core.

Patches can be submitted within Trac or via [GitHub](https://github.com/wordpress/wordpress-develop).

Please read the [GitHub Pull Requests for Code Review](https://make.wordpress.org/core/handbook/contribute/git/github-pull-requests-for-code-review/) page for details on how to submit a pull request in GitHub to fix a Trac ticket.

In particular, note that the full Trac URL of a ticket is required within the PR body and we request that you check the "Allow edits and access to secrets by maintainers" to allow Core contributors easier work with your submission.  

## Questions about Contributing?

The [WordPress Slack instance](https://make.wordpress.org/chat/) is the real-time communication platform. You can also join the conversation via the [Make Network of blogs](https://make.wordpress.org).

For support using WordPress, please visit the [WordPress.org Support Forums](https://wordpress.org/support/)

Thanks for contributing to the WordPress project! We're happy you're here.
