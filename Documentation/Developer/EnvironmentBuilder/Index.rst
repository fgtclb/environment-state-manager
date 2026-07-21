..  include:: /Includes.rst.txt

..  _developer-environment-builder:

===================
Environment Builder
===================

An environment builder creates a :php:`StateInterface` instance describing a
fully bootstrapped TYPO3 environment for a given :php:`StateBuildContext`.

The build context
=================

The :php:`StateBuildContext` is a small, immutable DTO describing *what*
environment should be built:

..  code-block:: php

    use FGTCLB\EnvironmentStateManager\StateBuildContext;
    use TYPO3\CMS\Core\Http\ApplicationType;

    $stateBuildContext = new StateBuildContext(
        applicationType: ApplicationType::FRONTEND,
        pageId: 1,
        languageId: 0,
    );

The factory
===========

The concrete builder differs between the supported TYPO3 core versions. Use the
:php:`EnvironmentBuilderFactoryInterface` to retrieve a TYPO3 core version
compatible builder for the given context. The factory is registered as a public
service and can be injected through dependency injection:

..  code-block:: php

    use FGTCLB\EnvironmentStateManager\EnvironmentBuilderFactoryInterface;
    use FGTCLB\EnvironmentStateManager\StateBuildContext;
    use FGTCLB\EnvironmentStateManager\StateInterface;
    use TYPO3\CMS\Core\Http\ApplicationType;

    final class MyService
    {
        public function __construct(
            private readonly EnvironmentBuilderFactoryInterface $environmentBuilderFactory,
        ) {}

        public function buildState(int $pageId): StateInterface
        {
            $stateBuildContext = new StateBuildContext(
                applicationType: ApplicationType::FRONTEND,
                pageId: $pageId,
                languageId: 0,
            );

            $environmentBuilder = $this->environmentBuilderFactory->create($stateBuildContext);

            return $environmentBuilder->build($stateBuildContext);
        }
    }

The returned :php:`StateInterface` holds the version-agnostic bootstrapped
environment elements, for example the :php:`ServerRequestInterface`, the
:php:`PageRenderer` and the :php:`Context`.

TYPO3 core-version specific state lives on the matching
:php:`Core13\ExtendedStateInterface`. In
particular the :php:`TypoScriptFrontendController` accessors are declared there,
because the :php:`TypoScriptFrontendController` is deprecated in TYPO3 v13 and
removed in TYPO3 v14. Narrow the returned state to an :php:`ExtendedStateInterface`
when you explicitly need that version-specific state.

..  note::

    Always type-hint the :php:`EnvironmentBuilderFactoryInterface`,
    :php:`EnvironmentBuilderInterface` and :php:`StateInterface`, never the
    concrete :php:`Core13\*` classes. The dependency injection
    container resolves the implementation for the running TYPO3 core version. See
    :ref:`developer-public-api` for the full public API surface.

Both the :php:`ApplicationType::FRONTEND` and :php:`ApplicationType::BACKEND`
application types are implemented, provided by the
:php:`FrontendEnvironmentBuilder` and the :php:`BackendEnvironmentBuilder`
respectively. A
:php:`FGTCLB\EnvironmentStateManager\Exception\NoTypo3VersionCompatibleEnvironmentBuilderFound`
exception is thrown when no builder is available for the current TYPO3 core
version.

Building a backend environment
==============================

For :php:`ApplicationType::BACKEND` the build context additionally accepts the
backend user and the workspace to operate in. The builder assembles a backend
request, a backend user, a language service and a context with the
``backend.user`` and ``workspace`` aspects for the selected page:

..  code-block:: php

    use FGTCLB\EnvironmentStateManager\StateBuildContext;
    use TYPO3\CMS\Core\Http\ApplicationType;

    $stateBuildContext = new StateBuildContext(
        applicationType: ApplicationType::BACKEND,
        pageId: 42,
        // Optional: the backend user to load. Defaults to a synthetic in-memory
        // admin that needs no `be_users` record.
        backendUserId: null,
        // Optional: the workspace to operate in. Defaults to the live workspace.
        workspaceId: null,
    );

    $this->stateManager->execute($stateBuildContext, function () {
        // Code in here runs within the backend environment built for page 42,
        // e.g. BackendUtility::getPagesTSconfig(42) resolves the page TSconfig.
    });

In most cases you do not interact with the builder directly but use the
:ref:`state manager <developer-state-manager>`, which uses the factory
internally.
