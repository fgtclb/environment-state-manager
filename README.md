[![Latest Stable Version](https://poser.pugx.org/fgtclb/environment-state-manager/v/stable.svg?style=for-the-badge)](https://packagist.org/packages/fgtclb/environment-state-manager)
[![License](https://poser.pugx.org/fgtclb/environment-state-manager/license?style=for-the-badge)](https://packagist.org/packages/fgtclb/environment-state-manager)
[![TYPO3 13.4](https://img.shields.io/badge/TYPO3-13.4-green.svg?style=for-the-badge)](https://get.typo3.org/version/13.4)
[![Total Downloads](https://poser.pugx.org/fgtclb/environment-state-manager/downloads.svg?style=for-the-badge)](https://packagist.org/packages/fgtclb/environment-state-manager)
[![Monthly Downloads](https://poser.pugx.org/fgtclb/environment-state-manager/d/monthly?style=for-the-badge)](https://packagist.org/packages/fgtclb/environment-state-manager)

# FGTCLB: Environment State Manager

|                  | URL                                                                   |
|------------------|-----------------------------------------------------------------------|
| **Repository:**  | https://github.com/fgtclb/environment-state-manager                   |
| **Read online:** | https://docs.typo3.org/p/fgtclb/environment-state-manager/main/en-us/ |
| **TER:**         | https://extensions.typo3.org/extension/environment_state_manager/     |
| **ISSUES:**      | https://github.com/fgtclb/environment-state-manager/issues/           |
| **RELEASES:**    | https://github.com/fgtclb/environment-state-manager/releases/         |

## Description

TYPO3 CMS extension providing an environment builder and a state manager. It
allows code to build and apply a fully featured TYPO3 environment (request,
controller context, `ServerRequest`, TypoScript, language and visibility
aspects, …) for a given page and to safely back up and restore the global
state around such an operation.

> **Note:** Both **frontend** and **backend** environment handling are
> implemented. The API is designed around an application type, selecting the
> matching environment builder for the requested type.

This functionality was extracted from `fgtclb/academic-base` into a dedicated,
reusable extension.

Over time there have been multiple extensions that allowed creating the
`TypoScriptFrontendController (TSFE)` but missed all the other handling and
state in various places. They further lacked proper state management and
building when used in FE or BE web-requests and did not return to the
previous state, leaving the context in a populated (broken) state - something
this extension tries to handle more properly across the different TYPO3 versions.

It can be used in tasks, commands, schedulers, frontend requests, backend requests
and also within functional tests to properly build the more global state.

## Features

* `EnvironmentBuilderFactory` returning a TYPO3 core version compatible
  environment builder (TYPO3 v13). A `FrontendEnvironmentBuilder` and a
  `BackendEnvironmentBuilder` are shipped.
* `StateManager` to build, apply and restore an environment state, emitting
  `StateApplyEvent` and `StateBackupEvent` PSR-14 events.

## Compatibility

| Branch | State            | Extension | TYPO3     | PHP       |
|--------|------------------|-----------|-----------|-----------|
| main   | active support   | 2.x       | v13       | 8.2 – 8.5 |
| 1      | maintenance      | 1.x       | v12 / v13 | 8.1 – 8.5 |

## Installation

```bash
composer require fgtclb/environment-state-manager
```

## Development

> [!TIP]
> This extension uses the `Build/Scripts/runTests.sh` script dispatcher known from TYPO3 Core Development
> and also from a long list of public extensions adopting it for extension development. Every tool or test
> execution is dispatched through this wrapper script making it the same in every environment.
>
> This requires at `docker` or `podman` to be installed on the system.

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

## Documentation

The extension documentation is based on `ReStructured TEXT (ReST)` and the TYPO3 render-guides
can be used to render the documentation locally using the `Build/Scripts/runTests.sh` dispatcher.

```bash
Build/Scripts/runTests.sh -s renderDocumentation
```

The implementation follows the recommendation from the TYPO3 Documentation team except not using the Makefile or
Github Action approach in favour of the centralized `runTests.sh` wrapper approach.

Adding the `Makefile` will be declined due to our own policies.

### Feature tests

* `Tests/Unit/EnvironmentBuilderFactoryTest.php` and
  `Tests/Functional/EnvironmentBuilderFactoryTest.php` cover the
  `EnvironmentBuilderFactory`, including the TYPO3 core version specific
  service resolution.
* `Tests/Functional/Core13` holds the `StateManager` functional tests. They are
  gated per TYPO3 version through the `not-core-<version>` PHPUnit groups as
  soon as more than one TYPO3 major version is supported.

### Adopted tests

These tests were adopted from `fgtclb/academic-persons`. Because a standalone
extension can not depend on the monorepo-only
`fgtclb/academics-monorepo-testing-helper` package, the underlying traits are
adopted into this repository under
`Tests/FunctionalTestCase/` (namespace
`FGTCLB\EnvironmentStateManager\Tests\FunctionalTestCase`):

* `Tests/Unit/VersionCompatTest.php` and `Tests/Functional/VersionCompatTest.php`
  use `ExtensionCoreVersionCompatTestsTrait` to assert the supported TYPO3 v13
  major version, as a unit and a functional test respectively.
* `Tests/Functional/ExtensionLoadedTest.php` uses `ExtensionsLoadedTestsTrait`
  to verify the extension is registered both by its composer package name
  (`fgtclb/environment-state-manager`) and its extension key
  (`environment_state_manager`). The academic extension chain of the original
  test is reduced to this extension only.

## Create a release (maintainers only)

Prerequisites:

* git binary
* ssh key allowed to push new branches to the repository
* GitHub command line tool `gh` installed and configured with user having permission to create pull requests.

**Create release**

> Set `RELEASE_BRANCH` to branch release should happen, for example: 'main'.
> Set `RELEASE_VERSION` to release version working on, for example: '1.0.0'.

> [!IMPORTANT]
> Requires `GitHub cli tool` with personal token and
> maintainer permission on the extension repository.

```bash
echo '>> Create release' ; \
  RELEASE_BRANCH='main' ; \
  RELEASE_VERSION='1.0.0' ; \
  DEV_VERSION='1.0.1' ; \
  echo ">> Checkout branches" && \
  git checkout main && \
  git fetch --all && \
  git pull --rebase && \
  git checkout ${RELEASE_BRANCH} && \
  git pull --rebase && \
  echo ">> Create release ${RELEASE_VERSION}" && \
  git checkout -b release-${RELEASE_VERSION} && \
  sed -i "s/^COMPOSER_ROOT_VERSION.*/COMPOSER_ROOT_VERSION=\"${RELEASE_VERSION}\"/" Build/Scripts/runTests.sh && \
  sed -i "s/^  RELEASE_VERSION.*/  RELEASE_VERSION='${RELEASE_VERSION}' ; \\\\/" README.md && \
  sed -i "s/^  DEV_VERSION.*/  DEV_VERSION='${DEV_VERSION}' ; \\\\/" README.md && \
  tailor set-version ${RELEASE_VERSION} && \
  composer config "extra"."typo3/cms"."version" "${RELEASE_VERSION}" && \
  echo "${RELEASE_VERSION}" > VERSION && \
  git add . && \
  git commit -m "[RELEASE] ${RELEASE_VERSION}" && \
  git push --set-upstream origin release-${RELEASE_VERSION} && \
  gh pr create --fill --base ${RELEASE_BRANCH} --title "[RELEASE] ${RELEASE_VERSION}" && \
  sleep 10 && \
  gh pr checks --watch --interval 2 && \
  sleep 10 && \
  gh pr merge -rd --admin && \
  git remote prune origin && \
  git tag ${RELEASE_VERSION} && \
  git push origin ${RELEASE_VERSION} && \
  echo ">> Post-release - set dev version: ${DEV_VERSION}-dev" && \
  git checkout -b set-dev-version-${DEV_VERSION} && \
  sed -i "s/^COMPOSER_ROOT_VERSION.*/COMPOSER_ROOT_VERSION=\"${DEV_VERSION}-dev\"/" Build/Scripts/runTests.sh && \
  tailor set-version ${DEV_VERSION} && \
  composer config "extra"."typo3/cms"."version" "${DEV_VERSION}-dev" && \
  echo "${DEV_VERSION}-dev" > VERSION && \
  git add . && \
  git commit -m "[TASK] Set dev version ${DEV_VERSION}" && \
  git push --set-upstream origin set-dev-version-${DEV_VERSION} && \
  gh pr create --fill --base ${RELEASE_BRANCH} --title "[TASK] Set dev version \"${DEV_VERSION}-dev\"" && \
  sleep 10 && \
  gh pr checks --watch --interval 2 && \
  sleep 10 && \
  gh pr merge -rd --admin && \
  git remote prune origin
```

This triggers the `on push tags` workflow (`publish.yml`), which creates the
upload package, attaches it to a GitHub release for the tag, and publishes the
new version to the TYPO3 Extension Repository (TER).

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
