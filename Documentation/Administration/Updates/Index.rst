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

The environment builder and state manager were extracted from
``fgtclb/academic-base``. Code that previously consumed the feature through
the ``fgtclb/academic-base`` internal classes needs to switch to the new
package and namespace:

*   Require ``fgtclb/environment-state-manager`` in addition to (or instead of)
    relying on the classes shipped within ``fgtclb/academic-base``.
*   Replace the ``FGTCLB\AcademicBase\Environment\*`` namespace with
    ``FGTCLB\EnvironmentStateManager\*`` and drop the ``Environment``
    sub-namespace segment, for example
    ``FGTCLB\AcademicBase\Environment\StateManagerInterface`` becomes
    ``FGTCLB\EnvironmentStateManager\StateManagerInterface``.
