..  include:: /Includes.rst.txt

..  _start:

=========================
Environment State Manager
=========================

:Extension key:
    environment_state_manager

:Package name:
    fgtclb/environment-state-manager

:Version:
    |release|

:Language:
    en

:Author:
    FGTCLB GmbH

:License:
    This document is published under the
    `Open Content License <https://www.openhub.net/licenses/opl>`__.

:Rendered:
    |today|

----

TYPO3 CMS extension providing a frontend environment builder and a state
manager. It builds and applies a fully featured TYPO3 frontend environment
(request, frontend controller context, TypoScript, language and visibility
aspects, …) for a given page and safely backs up and restores the global
state around such an operation.

This functionality was extracted from ``fgtclb/academic-base`` into a
dedicated, reusable extension.

----

..  card-grid::
    :columns: 1
    :columns-md: 2
    :gap: 4
    :class: pb-4
    :card-height: 100

    ..  card:: :ref:`Introduction <introduction>`

        Learn what the extension provides and when to use it.

    ..  card:: :ref:`Administration <administration>`

        Install and update the extension in your TYPO3 installation.

    ..  card:: :ref:`Developer Corner <developer>`

        Technical description and code examples for the environment builder
        and the state manager.

    ..  card:: :ref:`Changelog <changelog>`

        Overview of the changes per released version.

..  toctree::
    :maxdepth: 2
    :titlesonly:
    :hidden:

    Introduction/Index
    Administration/Index
    Developer/Index
    Changelog/Index
