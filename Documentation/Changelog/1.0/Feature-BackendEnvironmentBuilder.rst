..  include:: /Includes.rst.txt

..  _feature-backend-environment-builder:

====================================
Feature: Backend environment builder
====================================

Description
===========

The environment builder supports building backend environments for
:php:`ApplicationType::BACKEND`, next to frontend environments. A
:php:`BackendEnvironmentBuilder` is shipped for TYPO3 v12 and v13 and selected
automatically by the :php:`EnvironmentBuilderFactory` for a backend build context.

For a selected page id the backend environment builder assembles a faithful
backend environment, mirroring what the backend middleware chain produces during
an HTTP request:

*   a backend PSR-7 request (``applicationType`` backend, ``normalizedParams``
    and the resolved ``site``),
*   a backend user — the one referenced by the build context, or a synthetic
    in-memory admin by default,
*   a language service created from the backend user's preferences,
*   a context carrying the ``backend.user`` and ``workspace`` aspects.

The :php:`StateBuildContext` provides two optional, backend-only options:

*   :php:`backendUserId` – the backend user to load into the environment.
    ``null`` uses a synthetic in-memory admin that needs no ``be_users`` record;
    a uid loads that existing backend user with its groups, permissions and user
    TSconfig.
*   :php:`workspaceId` – the workspace to operate in. ``null`` defaults to the
    live workspace.

The public :php:`StateInterface` provides a :php:`languageService()` /
:php:`withLanguageService()` accessor, and the state manager backs up, applies
and restores :php:`$GLOBALS['LANG']` alongside the request, TSFE and backend
user.

Example
=======

..  code-block:: php

    use FGTCLB\EnvironmentStateManager\StateBuildContext;
    use FGTCLB\EnvironmentStateManager\StateManagerInterface;
    use TYPO3\CMS\Core\Http\ApplicationType;

    final class MyService
    {
        public function __construct(
            private readonly StateManagerInterface $stateManager,
        ) {}

        public function run(): void
        {
            $stateBuildContext = new StateBuildContext(
                applicationType: ApplicationType::BACKEND,
                pageId: 42,
            );

            $this->stateManager->execute($stateBuildContext, function () {
                // Runs within the backend environment built for page 42.
            });
        }
    }

See the :ref:`Developer Corner <developer-environment-builder>` for details.
