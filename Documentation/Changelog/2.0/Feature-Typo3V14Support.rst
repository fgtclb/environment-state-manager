..  include:: /Includes.rst.txt

..  _feature-15-typo3-v14-support:

================================
Feature: #15 - TYPO3 v14 support
================================

Description
===========

The extension supports TYPO3 v14 next to TYPO3 v13. The TYPO3 core-version
specific implementations for TYPO3 v14 are shipped in the
:php:`FGTCLB\EnvironmentStateManager\Core14` namespace and selected
automatically through dependency injection, mirroring the existing TYPO3 v13
implementation.

TYPO3 v14 removed the :php:`TypoScriptFrontendController` (class, the
:php:`$GLOBALS['TSFE']` global and the `frontend.controller` request attribute).
It was the only TYPO3 core-version specific state this extension carried on the
:php:`Core13\ExtendedStateInterface`. TYPO3 v14 therefore has no extended state
interface: :php:`Core14\State` implements the version-agnostic
:php:`StateInterface` directly.

The frontend environment builder reproduces on TYPO3 v14 what the
:php:`TypoScriptFrontendController` bootstrap did on TYPO3 v13:

*   the PageRenderer language is initialized as the
    `PrepareTypoScriptFrontendRendering` middleware does,
*   the :php:`ContentObjectRenderer` is created and started without the removed
    :php:`TypoScriptFrontendController` constructor argument, as the frontend
    request handler does.

TYPO3 v14 added :php:`ApplicationType::INSTALL`. The
:php:`EnvironmentBuilderFactory` builds frontend and backend environments only
and throws the new :php:`UnsupportedApplicationType` exception for any other
application type.

Impact
======

The extension can be installed on TYPO3 v14. Code that needs the TYPO3 v13
:php:`TypoScriptFrontendController` state must continue to narrow to
:php:`Core13\ExtendedStateInterface`, which is only available on TYPO3 v13, as
documented.
