..  include:: /Includes.rst.txt

..  _developer-state-manager:

=============
State Manager
=============

The :php:`StateManagerInterface` is the central, public service of this
extension. It bootstraps an environment for a given context, applies it to the
global TYPO3 state and is able to back up and restore that state.

..  note::

    Both frontend and backend environments are bootstrapped, selected through
    the :php:`ApplicationType` of the :php:`StateBuildContext`. The examples
    below use :php:`ApplicationType::FRONTEND`; see the
    :ref:`environment builder <developer-environment-builder>` for a backend
    example.

The state manager is registered as a public service and resolved to a TYPO3
core version compatible implementation. Inject it through dependency injection:

..  code-block:: php

    use FGTCLB\EnvironmentStateManager\StateManagerInterface;

    final class MyService
    {
        public function __construct(
            private readonly StateManagerInterface $stateManager,
        ) {}
    }

..  note::

    Always type-hint :php:`StateManagerInterface`, never a concrete
    :php:`Core12\StateManager` or :php:`Core13\StateManager`. The dependency
    injection container resolves the implementation for the running TYPO3 core
    version. See :ref:`developer-public-api` for the full public API surface.

Execute code within an environment
==================================

The recommended way to run code inside a built environment is the
:php:`execute()` method. It backs up the current environment, bootstraps the
environment described by the :php:`StateBuildContext`, runs the given closure
and restores the previous environment afterwards – even if the closure throws:

..  code-block:: php

    use FGTCLB\EnvironmentStateManager\StateBuildContext;
    use TYPO3\CMS\Core\Http\ApplicationType;

    $stateBuildContext = new StateBuildContext(
        applicationType: ApplicationType::FRONTEND,
        pageId: 42,
        languageId: 0,
    );

    $this->stateManager->execute($stateBuildContext, function () {
        // Code in here runs within the frontend environment built for page 42.
        // The previous environment is restored automatically afterwards.
    });

Manual backup, bootstrap and restore
=====================================

If you need finer control, the individual steps are available as well. Always
make sure to restore a previously created backup, for example by using a
:php:`try`/:php:`finally` block:

..  code-block:: php

    use FGTCLB\EnvironmentStateManager\StateBuildContext;
    use TYPO3\CMS\Core\Http\ApplicationType;

    $stateBuildContext = new StateBuildContext(
        applicationType: ApplicationType::FRONTEND,
        pageId: 42,
        languageId: 0,
    );

    $this->stateManager->backup();
    try {
        $state = $this->stateManager->bootstrap($stateBuildContext);
        // work with the bootstrapped $state ...
    } finally {
        $this->stateManager->restore();
    }

Applying a pre-built state
==========================

A :php:`StateInterface` instance – for example one created through the
:ref:`environment builder <developer-environment-builder>` – can be applied to
the global environment directly:

..  code-block:: php

    $this->stateManager->apply($state);

PSR-14 events
=============

The state manager dispatches the following events that can be used to react on
state changes:

..  list-table::
    :header-rows: 1

    *   -   Event
        -   Dispatched
    *   -   :php:`FGTCLB\EnvironmentStateManager\Event\StateApplyEvent`
        -   When a state is applied to the global environment.
    *   -   :php:`FGTCLB\EnvironmentStateManager\Event\StateBackupEvent`
        -   When the current environment is backed up onto the snapshot stack.

Register an event listener as usual through the :file:`Services.yaml` /
:file:`Services.php` or the :php:`#[AsEventListener]` attribute:

..  code-block:: php

    use FGTCLB\EnvironmentStateManager\Event\StateApplyEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class MyStateApplyListener
    {
        #[AsEventListener]
        public function __invoke(StateApplyEvent $event): void
        {
            // react on the applied state
        }
    }
