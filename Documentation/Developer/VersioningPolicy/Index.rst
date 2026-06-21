..  include:: /Includes.rst.txt

..  _developer-versioning-policy:

==========================================
Versioning, deprecation and support policy
==========================================

This extension follows a predictable release and deprecation process so that
depending extensions know what to expect across releases.

Semantic versioning
====================

Releases follow `semantic versioning <https://semver.org/>`__ (``MAJOR.MINOR.PATCH``)
applied to the :ref:`public API <developer-public-api>`:

*   **PATCH** releases contain backward-compatible bug fixes only.
*   **MINOR** releases add backward-compatible functionality and may add new
    deprecations, but never remove or change existing public API.
*   **MAJOR** releases may contain breaking changes to the public API, including
    the removal of previously deprecated functionality.

Only the public API is covered by this promise. Internal implementation details
(see :ref:`developer-public-api`) may change in any release.

Deprecations and breaking changes
==================================

Functionality that is going to be removed is deprecated first whenever a
backward-compatible path can be provided:

*   Deprecations are announced in a dedicated changelog entry under
    :file:`Documentation/Changelog/<version>/Deprecation-*.rst`, describing what
    is deprecated and how to migrate.
*   Where a runtime path exists, deprecated code triggers an
    ``E_USER_DEPRECATED`` notice.
*   Deprecated functionality is removed no earlier than the next **MAJOR**
    release. Breaking changes are documented in a ``Breaking-*.rst`` changelog
    entry and the commit is prefixed with ``[!!!]``.

Not every change can go through a deprecation phase. TYPO3 core-version specific
state, such as the :php:`TypoScriptFrontendController` accessors, lives on the
TYPO3 core-version specific ``Core12``/``Core13`` ``ExtendedStateInterface`` and
not on the version-agnostic :php:`StateInterface`. As the
:php:`TypoScriptFrontendController` is deprecated in TYPO3 v13 and removed in
TYPO3 v14, a future TYPO3 v14 extended interface will simply omit those
accessors; the version-agnostic public contract stays untouched.

TYPO3 core version support
==========================

Each release states the supported TYPO3 core versions in its
:file:`composer.json` and :file:`ext_emconf.php`. Support for a TYPO3 core
version is dropped only in a **MAJOR** release of this extension, accompanied by
a breaking changelog entry. New TYPO3 core versions are added in **MINOR**
releases through dedicated, version-specific ``Core<major>/`` implementations.
