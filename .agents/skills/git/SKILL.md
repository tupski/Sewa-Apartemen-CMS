---
name: git
description: >-
  Use when committing, staging, branching, or managing repo hygiene. Trigger
  phrases: "commit this", "write a commit message", "stage files", "gitignore",
  "don't commit secrets", "squash/rebase", "force push", "line endings". Encodes
  this repo's commit conventions and secret-hygiene rules.
---

# Purpose
Keep the git history clean, safe, and consistent with this repo's conventions.
This skill enforces small focused commits, blocks secret/artifact commits, and
records the commit-message style actually used here.

# When to Use
- Creating commits or writing commit messages.
- Staging changes, updating `.gitignore`, or handling repo hygiene.
- Any discussion of rewriting history or force pushing.

# Rules
- Small, focused commits — one logical change each; keep diffs reviewable.
- Commit-message convention IS Conventional Commits (verified from `git log`):
  `feat:`, `fix:`, `chore:`, `style:`, `test:` with optional scopes, e.g.
  `feat(security): add SSRF protection`, `chore(i18n): add pricing headings`.
  Follow this style.
- Do NOT commit secrets. `cyberstrike.json` is listed in `.gitignore` (security
  scanner config that may contain credentials) but was historically tracked — do
  not add secrets to it or any tracked file. Add new secret-like files to
  [`.gitignore`](.gitignore).
- Do NOT commit generated artifacts — build output, caches, logs, `vendor/`,
  `node_modules/`. Respect [`.gitignore`](.gitignore).
- Do NOT rewrite history (rebase/squash/amend pushed commits) or force push
  without explicit user instruction.
- `.gitattributes` is present — respect its line-ending/normalization settings;
  do not fight it with editor-forced CRLF/LF changes across a file.
- Stage specific files over `git add .` to avoid sweeping in unrelated changes;
  flag any `.env`-like or credential file before staging.

# Workflow
1. Review the diff; group changes into one logical unit per commit.
2. Stage the specific files for that unit.
3. Write a Conventional Commits message (`type(scope): summary`).
4. Confirm no secrets/artifacts are staged before committing.

# Common Mistakes
- Committing secrets or `.env` values into tracked files.
- Committing build output/caches/logs that `.gitignore` should exclude.
- Force pushing or rewriting shared history without instruction.
- Non-conventional or vague commit messages ("update", "fix stuff").
- `git add .` sweeping in unrelated or sensitive files.

# Validation
- `git status` shows only intended files staged; no secrets/artifacts.
- Commit message follows `type(scope): summary`.
- No history rewrite/force push occurred unless explicitly requested.

# Related Files
- [`.gitignore`](.gitignore), [`.gitattributes`](.gitattributes)
- [`README.md`](README.md)
