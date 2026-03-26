---
description: Apply only when generating a suggested git commit message from the VS Code Source Control "Generate Commit Message" action. Do not use for staging files or running git commits.
---

# Commit Message Generation

Use this instruction only to generate a commit message suggestion from the current git changes shown in Source Control. It must not stage files, create a commit, or perform any git action that changes repository state.

## Workflow

- Base the message on the currently staged changes when staged changes exist.
- If nothing is staged, base the message on the current unstaged working tree changes.
- Summarise the actual change, not the user's intent or ticket title.
- Only use a scope on `docs`, `chore`, `build`, and `ci` commits, and only when it meaningfully narrows the audience or area. Omit the scope on all other types.
- Return only the proposed commit message.

## Output Quality Examples

The following shows the same diff handled correctly and incorrectly.

**Bad** — raw diff tokens leaked into the message:

```
refactor(U pouvoirs Ret coins): update config methods to use helper traits 文 আ obстоятельsspaq Tritur disposto மற்றும்...
```

**Good** — high-level summary inferred from file paths and clear hunks only:

```
refactor: extract repeated config resolution logic into helper methods
```

If the diff is too noisy to summarise accurately, use a safe generic fallback:

```
chore: update project files
```

## Format

```
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

- **Present tense, imperative mood**: "add feature" not "added feature"
- **Scope**: lowercase, in parentheses — only permitted on `docs`, `chore`, `build`, and `ci` types; omit on all others
- **Description**: under 72 characters, concise, and in sentence case; preserve original casing for code/class references in backticks

## Commit Types

This repository extends the standard Conventional Commits types with additional project-specific types.

| Type        | When to use                                                 |
| ----------- | ----------------------------------------------------------- |
| `feat`      | New feature                                                 |
| `fix`       | Bug fix                                                     |
| `docs`      | Documentation and docblocks only                            |
| `style`     | Formatting/whitespace, missing semi-colons, no logic change |
| `refactor`  | Code restructuring, no behaviour change                     |
| `perf`      | Performance improvement                                     |
| `test`      | Add or update tests                                         |
| `build`     | Build system or dependency changes                          |
| `chore`     | Maintenance, tooling, config, version bumps                 |
| `ci`        | CI/CD pipeline                                              |
| `revert`    | Revert a previous commit                                    |
| `remove`    | Remove code or files                                        |
| `security`  | Security-related changes                                    |
| `deprecate` | Deprecation-related changes                                 |

## Custom Type Examples

Prefer these extended types when they describe the change more accurately than `refactor`, `fix`, or `chore`.

- Use `remove` only when code or files are actually deleted, not when they are merely moved or refactored.
- Use `security` when security risk reduction is the primary intent and outcome, not for unrelated fixes.
- Use `deprecate` only when introducing or documenting a deprecation path, not when fully removing the deprecated code.

```
remove(drivers): delete legacy driver compatibility shim

security(nginx): harden fastcgi param handling for site isolation

deprecate(config): mark `php_port` as deprecated in favour of `php81_port`
```

## Breaking Changes

Use `!` after type/scope and add a `BREAKING CHANGE:` footer:

```
feat!: rename PHP port config key

BREAKING CHANGE: `php_port` renamed to `php_port_override`
```

## Body & Footers

Add a body when the _why_ is not obvious from the subject line.

Body guidance:

- Explain why the change was needed when it is not already obvious.
- Include relevant technical context only when it improves clarity.
- Use sentence case and proper punctuation.
- Use bullet points only when listing multiple distinct changes.
- Always separate each bullet point with a blank line.
- When referring to methods across multiple classes, prefix them with the class name, for example `ClassName::methodName`.

Footer guidance:

- Put issue references in the footer, for example `Closes #36` or `Refs #36`.

**Bad** — wrong case, missing backticks, footer buried in body, no blank line before footer:

```
fix - Fixed the field variable

fixed $field to $correctField in Config getValue method. also updated tests. closes #36
```

**Good**:

```
fix: correct variable replacement

Variable was missed when replacement happened, causing errors.

- Fixed incorrect `$field` variable name to `$correctField` in `Config::getValue` method.

- Updated tests to cover this case.

Closes #36
```

## Best Practices

- Review the current Source Control changes before generating the message.
- Ensure the entire message is professional and clearly communicates the purpose of the commit.
- Important: Use `docs` for JSDoc additions/changes, not `refactor`.
- Use **British English** spelling for commit messages (e.g. "optimise" not "optimize", "colour", not "color") to maintain consistency with existing messages.
- Always enclose code references in backticks (e.g. `php_port`).
- Always separate each bullet point with a blank line.

## Safety Rules

- Never stage files, commit changes, amend commits, or push.
- Never suggest including secrets, credentials, or private keys in a commit.
