..  include:: /Includes.rst.txt

..  _updates:

=======
Updates
=======

This page documents update and migration steps between major versions.

Version 1.x
===========

This is the initial release of the standalone extension. There are no
migration steps required for a fresh installation.

..  _migration-academic-base:

Migrating from ``fgtclb/academic-base``
---------------------------------------

The environment builder and state manager were extracted from
``fgtclb/academic-base``, where they lived under the internal, ``@internal``
flagged namespaces ``FGTCLB\AcademicBase\Environment``,
``FGTCLB\AcademicBase\Core12\Environment`` and
``FGTCLB\AcademicBase\Core13\Environment``.

As of **``fgtclb/academic-base`` 2.4.0** those internal classes are
**deprecated** and will be **removed in ``fgtclb/academic-base`` 3.0.0**. Code
that consumed the feature through ``fgtclb/academic-base`` should switch to this
extension:

#.  Require the extension:

    ..  code-block:: bash

        composer require fgtclb/environment-state-manager

#.  Replace the namespace prefix. The class names, method signatures and
    behaviour are compatible; only the namespace changes (the ``Environment``
    sub-namespace segment is dropped):

    ..  list-table::
        :header-rows: 1

        *   -   ``fgtclb/academic-base`` (deprecated)
            -   ``fgtclb/environment-state-manager``
        *   -   ``FGTCLB\AcademicBase\Environment\*``
            -   ``FGTCLB\EnvironmentStateManager\*``
        *   -   ``FGTCLB\AcademicBase\Core12\Environment\*``
            -   ``FGTCLB\EnvironmentStateManager\Core12\*``
        *   -   ``FGTCLB\AcademicBase\Core13\Environment\*``
            -   ``FGTCLB\EnvironmentStateManager\Core13\*``

    For example ``FGTCLB\AcademicBase\Environment\StateManagerInterface``
    becomes ``FGTCLB\EnvironmentStateManager\StateManagerInterface``, and
    ``FGTCLB\AcademicBase\Core13\Environment\StateManager`` becomes
    ``FGTCLB\EnvironmentStateManager\Core13\StateManager``.

..  note::

    In ``fgtclb/academic-base`` 2.4.0 the value object
    :php:`StateBuildContext` was already removed and re-registered as a
    deprecated class alias onto
    :php:`\FGTCLB\EnvironmentStateManager\StateBuildContext` (via the
    ``typo3/class-alias-loader`` composer plugin), because it is carried by type
    through a public PSR-14 event. The remaining classes stay as deprecated real
    classes until 3.0.0.

API differences to be aware of
------------------------------

While migrating, note the following intentional differences from the former
``fgtclb/academic-base`` implementation:

*   :php:`StateBuildContext` gained two optional constructor arguments,
    :php:`$backendUserId` and :php:`$workspaceId`, used by the backend
    environment builder. Existing frontend call sites are unaffected.
*   The version-agnostic :php:`StateInterface` no longer declares the
    :php:`TypoScriptFrontendController` accessors. They moved to the TYPO3 core
    version specific :php:`Core12\ExtendedStateInterface` /
    :php:`Core13\ExtendedStateInterface`, because the
    :php:`TypoScriptFrontendController` is deprecated in TYPO3 v13 and removed in
    TYPO3 v14. A :php:`LanguageService` accessor pair was added instead.
