# FGTCLB: Environment State Manager

[![TYPO3 v12](https://img.shields.io/badge/TYPO3-12-orange.svg)](https://get.typo3.org/version/12)
[![TYPO3 v13](https://img.shields.io/badge/TYPO3-13-orange.svg)](https://get.typo3.org/version/13)

TYPO3 CMS extension providing a frontend environment builder and a state
manager. It allows code to build and apply a fully featured TYPO3 frontend
environment (request, `TypoScriptFrontendController` / `ServerRequest` based
frontend context, TypoScript, language and visibility aspects, …) for a given
page and to safely back up and restore the global state around such an
operation.

This functionality was extracted from `fgtclb/academic-base` into a dedicated,
reusable extension.

## Features

* `EnvironmentBuilderFactory` returning a TYPO3 core version compatible
  `FrontendEnvironmentBuilder` (TYPO3 v12 and v13).
* `StateManager` to apply and restore a built frontend environment state,
  emitting `StateApplyEvent` and `StateBackupEvent` PSR-14 events.

## Compatibility

| Extension | TYPO3      | PHP           |
|-----------|------------|---------------|
| 1.x       | v12 / v13  | 8.1 – 8.5     |

## Installation

```bash
composer require fgtclb/environment-state-manager
```

## Development

Tests and code quality checks are executed through the container based test
runner:

```bash
# Prepare dependencies for a TYPO3 version
Build/Scripts/runTests.sh -t 13 -p 8.2 -s composerUpdate

# Coding guidelines (php-cs-fixer)
Build/Scripts/runTests.sh -s cgl

# Static analysis
Build/Scripts/runTests.sh -t 13 -s phpstan

# Unit / functional tests
Build/Scripts/runTests.sh -t 13 -s unit
Build/Scripts/runTests.sh -t 13 -s functional
```

See `Build/Scripts/runTests.sh -h` for all options.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
