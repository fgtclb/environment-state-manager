..  include:: /Includes.rst.txt

..  _introduction:

============
Introduction
============

What does it do?
================

The :guilabel:`Environment State Manager` extension provides a programmatic
way to build, apply and restore a TYPO3 environment.

It is useful whenever code that usually runs in a frontend request context has
to be executed in a different context – for example in a backend module, a
command line task or a middleware – where a fully initialized frontend
environment (request, frontend controller, TypoScript, language and visibility
aspects) is not available.

..  note::

    Both **frontend** and **backend** environment handling are implemented.
    The API is built around an :php:`ApplicationType`, selecting the matching
    environment builder for the requested type.

Features
========

*   :php:`EnvironmentBuilderFactory` returning a TYPO3 core version compatible
    environment builder for TYPO3 v12 and v13. A :php:`FrontendEnvironmentBuilder`
    and a :php:`BackendEnvironmentBuilder` are shipped.
*   :php:`StateManager` to build, apply and restore an environment state.
*   :php:`StateApplyEvent` and :php:`StateBackupEvent` PSR-14 events dispatched
    around applying and backing up the state.

Compatibility
=============

..  list-table::
    :header-rows: 1

    *   -   Branch
        -   Extension
        -   TYPO3
        -   PHP
    *   -   main
        -   1.x
        -   v12 / v13
        -   8.1 - 8.5
