# Contributing

Contributions are welcome! This is a short overview; the full contribution
guide lives in the rendered documentation under
[`Documentation/Contribution/Index.rst`](Documentation/Contribution/Index.rst).

## Development environment

All tests and quality tools run in containers through the
`Build/Scripts/runTests.sh` wrapper. The only host requirement is a container
runtime — **podman** (preferred) or **docker**. The wrapper pulls the required
TYPO3 testing images on first use and installs dependencies into the
git-ignored `.Build/` directory.

```bash
# Install dependencies (TYPO3 v12, PHP 8.2 — default matrix).
Build/Scripts/runTests.sh -t 12 -p 8.2 -s composerUpdate

# Switch the working copy to the TYPO3 v13 dependency set.
Build/Scripts/runTests.sh -t 13 -p 8.2 -s composerUpdate
```

> `-t` only changes what `composerUpdate` installs. To run a suite against
> TYPO3 v13, run `composerUpdate -t 13` first, then pass `-t 13` to the suite.

## Running tests and quality checks

```bash
# Unit tests.
Build/Scripts/runTests.sh -s unit

# Functional tests on SQLite (no database container needed).
Build/Scripts/runTests.sh -s functional -d sqlite

# A single test class/method — phpunit args go after the "--" separator.
Build/Scripts/runTests.sh -s functional -d sqlite -- --filter EnvironmentBuilderFactoryTest

# Coding guidelines (check only, as in CI) and static analysis.
Build/Scripts/runTests.sh -s cgl -n
Build/Scripts/runTests.sh -s phpstan

# Render the documentation.
Build/Scripts/runTests.sh -s renderDocumentation
```

Please make sure `cgl -n`, `phpstan` and the unit and functional suites pass
before opening a pull request. Run `Build/Scripts/runTests.sh -h` for all
options.

## Commit messages

This repository follows the TYPO3 core commit message conventions:

```
[TAG] Short imperative summary

A wrapped body (~72 characters per line) explaining what the change does
and, more importantly, why it is needed.
```

- The subject starts with a **tag**, followed by a short summary in
  **imperative mood**, capitalized, **without** a trailing period.
- Separate subject and body with a blank line; wrap the body at ~72 characters.
- An issue reference is **not required** (optionally `Resolves: #123` in the
  body).

Tags: `[FEATURE]`, `[TASK]`, `[BUGFIX]`, `[DOCS]` (test-only changes use
`[TASK]`). Breaking changes are additionally prefixed with `[!!!]` (for example
`[!!!][TASK] …`).

See [`Documentation/Contribution/Index.rst`](Documentation/Contribution/Index.rst)
for the full details.
