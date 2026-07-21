..  include:: /Includes.rst.txt

..  _developer-public-api:

=========================
Public API and stability
=========================

This extension was extracted from another extension to provide a stable,
reusable API that other extensions can build on and rely on. To keep that
promise meaningful, the API surface is split into a public part covered by
backward-compatibility guarantees and an internal part that may change at any
time.

Public API
==========

The following types form the public API. Depend on these interfaces and types:

..  list-table::
    :header-rows: 1

    *   -   Type
        -   Purpose
    *   -   :php:`FGTCLB\EnvironmentStateManager\StateManagerInterface`
        -   Central service to backup, bootstrap, apply, restore and execute
            within an environment.
    *   -   :php:`FGTCLB\EnvironmentStateManager\EnvironmentBuilderFactoryInterface`
        -   Resolves a TYPO3 core-version compatible environment builder.
    *   -   :php:`FGTCLB\EnvironmentStateManager\EnvironmentBuilderInterface`
        -   Builds a :php:`StateInterface` for a given build context.
    *   -   :php:`FGTCLB\EnvironmentStateManager\StateInterface`
        -   Immutable snapshot of the bootstrapped environment elements (request,
            context, backend user, language service, ...).
    *   -   :php:`FGTCLB\EnvironmentStateManager\StateBuildContext`
        -   DTO describing which environment to build (application type, page and
            language; backend user and workspace for backend environments).
    *   -   :php:`FGTCLB\EnvironmentStateManager\Event\StateApplyEvent`,
            :php:`FGTCLB\EnvironmentStateManager\Event\StateBackupEvent`
        -   PSR-14 events to react on state changes.
    *   -   :php:`FGTCLB\EnvironmentStateManager\Exception\NoTypo3VersionCompatibleEnvironmentBuilderFound`,
            :php:`FGTCLB\EnvironmentStateManager\Exception\SiteConfigCouldNotBeDetermined`
        -   Exceptions thrown by the API.

The TYPO3 core-version specific :php:`Core13\ExtendedStateInterface` extends
:php:`StateInterface` and is part
of the public API as well. They carry the version-specific parts of the state
contract: the :php:`TypoScriptFrontendController` accessors
(:php:`withTypoScriptFrontendController()` and
:php:`typoScriptFrontendController()`) are declared here, and not on the
version-agnostic :php:`StateInterface`, because the
:php:`TypoScriptFrontendController` is deprecated in TYPO3 v13 and removed in
TYPO3 v14. Type-hint the version-agnostic :php:`StateInterface` in code that
should work across core versions, and only reference an
:php:`ExtendedStateInterface` when you explicitly target a specific TYPO3 core
version.

Internal implementation details
===============================

The concrete, TYPO3 core-version specific implementations under the
:php:`FGTCLB\EnvironmentStateManager\Core13` namespace – for example
:php:`Core13\StateManager`, :php:`Core13\FrontendEnvironmentBuilder` and
:php:`Core13\State` – as well as the :php:`EnvironmentBuilderFactory` and the
internal traits are marked :php:`@internal`. They are registered as dependency
injection services and resolved to the implementation matching the running
TYPO3 core version.

..  warning::

    Never type-hint or instantiate the core-version specific classes directly.
    Always inject the interfaces; the dependency injection container provides the
    implementation for the current TYPO3 core version. Their constructor
    signatures already differ between core versions and may change at any time
    without a backward-compatibility break.
