..  include:: /Includes.rst.txt

..  _feature-initial-release:

=========================
Feature: Initial release
=========================

Description
===========

Initial release of the ``fgtclb/environment-state-manager`` extension. The
environment builder and state manager were extracted from
``fgtclb/academic-base`` into this dedicated, reusable extension.

The extension provides:

*   :php:`EnvironmentBuilderFactory` returning a TYPO3 core version compatible
    environment builder for TYPO3 v12 and v13. A :php:`FrontendEnvironmentBuilder`
    and a :php:`BackendEnvironmentBuilder` are shipped (see
    :ref:`feature-backend-environment-builder`).
*   :php:`StateManager` to bootstrap, apply, back up and restore an environment
    state.
*   :php:`StateApplyEvent` and :php:`StateBackupEvent` PSR-14 events.

The classes are provided under the :php:`FGTCLB\EnvironmentStateManager`
namespace. See the :ref:`Developer Corner <developer>` for usage examples.
