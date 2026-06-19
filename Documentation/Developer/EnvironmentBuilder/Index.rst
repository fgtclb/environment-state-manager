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

The returned :php:`StateInterface` holds the bootstrapped environment elements,
for example the :php:`ServerRequestInterface`, the
:php:`TypoScriptFrontendController`, the :php:`PageRenderer` and the
:php:`Context`.

..  note::

    A :php:`FGTCLB\EnvironmentStateManager\Exception\NoTypo3VersionCompatibleEnvironmentBuilderFound`
    exception is thrown when no builder is available for the current TYPO3 core
    version. For an unsupported application type a :php:`\RuntimeException` is
    thrown.

In most cases you do not interact with the builder directly but use the
:ref:`state manager <developer-state-manager>`, which uses the factory
internally.
