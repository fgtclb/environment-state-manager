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

TYPO3 CMS extension providing an environment builder and a state manager. It
builds and applies a fully featured TYPO3 environment (request, controller
context, TypoScript, language and visibility aspects, …) for a given page and
safely backs up and restores the global state around such an operation.

..  note::

    Currently only **frontend** environment handling is implemented.
    **Backend** environment handling is planned to be added later. The API is
    built around an :php:`ApplicationType`, so backend support can be added
    without breaking the public interfaces.

This functionality was extracted from ``fgtclb/academic-base`` into a
dedicated, reusable extension.

Over the time there has been multiple extensions to allow the creation of the
`TypoScriptFrontendController (TSFE)` but missed all the other handling and
state in various places. They further lacked all a proper state management
and build when used in FE or BE web-requests and did not returned to the
previous state leaving the context in a populated (broken) state, something
this extension tries to handle more properly over the different TYPO3 versions.

It ca be used in tasks, commands, schedulers, frontend requests, backend requests
and also within functional tests to properly build the more global state.

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

    ..  card:: :ref:`Contribution <contribution>`

        Set up a development environment, run the tests and follow the commit
        message rules.

    ..  card:: :ref:`Changelog <changelog>`

        Overview of the changes per released version.

..  toctree::
    :maxdepth: 2
    :titlesonly:
    :hidden:

    Introduction/Index
    Administration/Index
    Developer/Index
    Contribution/Index
    Changelog/Index
