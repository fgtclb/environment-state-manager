# FGTCLB: Environment State Manager

[![TYPO3 v12](https://img.shields.io/badge/TYPO3-12-orange.svg)](https://get.typo3.org/version/12)
[![TYPO3 v13](https://img.shields.io/badge/TYPO3-13-orange.svg)](https://get.typo3.org/version/13)

TYPO3 CMS extension providing an environment builder and a state manager. It
allows code to build and apply a fully featured TYPO3 environment (request,
controller context, `ServerRequest`, TypoScript, language and visibility
aspects, …) for a given page and to safely back up and restore the global
state around such an operation.

> **Note:** Currently only **frontend** environment handling is implemented.
> **Backend** environment handling is planned to be added later. The API is
> designed around an application type, so backend support can be added without
> breaking the public interfaces.

This functionality was extracted from `fgtclb/academic-base` into a dedicated,
reusable extension.

## Features

* `EnvironmentBuilderFactory` returning a TYPO3 core version compatible
  environment builder (TYPO3 v12 and v13). A `FrontendEnvironmentBuilder` is
  shipped today; a backend environment builder is planned.
* `StateManager` to build, apply and restore an environment state, emitting
  `StateApplyEvent` and `StateBackupEvent` PSR-14 events.

## Compatibility

| Branch | Extension | TYPO3     | PHP       |
|--------|-----------|-----------|-----------|
| main   | 1.x       | v12 / v13 | 8.1 – 8.5 |

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

## Tests

The test suite combines feature tests for the extracted functionality with a
set of tests adopted from the `fgtclb/academic-*` extension family.

### Feature tests

* `Tests/Unit/EnvironmentBuilderFactoryTest.php` and
  `Tests/Functional/EnvironmentBuilderFactoryTest.php` cover the
  `EnvironmentBuilderFactory`, including the TYPO3 core version specific
  service resolution.
* `Tests/Functional/Core12` and `Tests/Functional/Core13` hold the
  `StateManager` functional tests, gated per TYPO3 version through the
  `not-core-12` / `not-core-13` PHPUnit groups.

### Adopted tests

These tests were adopted from `fgtclb/academic-persons`. Because a standalone
extension can not depend on the monorepo-only
`fgtclb/academics-monorepo-testing-helper` package, the underlying traits are
adopted into this repository under
`Tests/FunctionalTestCase/` (namespace
`FGTCLB\EnvironmentStateManager\Tests\FunctionalTestCase`):

* `Tests/Unit/VersionCompatTest.php` and `Tests/Functional/VersionCompatTest.php`
  use `ExtensionCoreVersionCompatTestsTrait` to assert the supported TYPO3 v12
  and v13 major versions, as a unit and a functional test respectively.
* `Tests/Functional/ExtensionLoadedTest.php` uses `ExtensionsLoadedTestsTrait`
  to verify the extension is registered both by its composer package name
  (`fgtclb/environment-state-manager`) and its extension key
  (`environment_state_manager`). The academic extension chain of the original
  test is reduced to this extension only.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
