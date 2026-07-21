..  include:: /Includes.rst.txt

..  _breaking-15-removed-typo3-v12-and-php-81-support:

=====================================================
Breaking: #15 - Removed TYPO3 v12 and PHP 8.1 support
=====================================================

Description
===========

Support for TYPO3 v12 has been removed with version 2.0.0, following the dual
TYPO3 core version support per major extension version used as support matrix.
Along with it, support for PHP 8.1 has been removed, raising the lowest
supported PHP version to PHP 8.2.

This includes the removal of the TYPO3 v12 specific implementations in the
:php:`FGTCLB\EnvironmentStateManager\Core12` namespace, the related functional
tests and all build and continuous integration configuration for TYPO3 v12 and
PHP 8.1.

Impact
======

The extension can not be installed on TYPO3 v12 instances or with PHP 8.1
anymore. Composer refuses the installation of version 2.0.0 in that case.

The classes of the :php:`FGTCLB\EnvironmentStateManager\Core12` namespace do
not exist anymore. Code type-hinting or instantiating them directly fails with a
fatal error.

Affected installations
======================

Instances using TYPO3 v12 or PHP 8.1 together with version 1.x of this
extension, and code referencing the
:php:`FGTCLB\EnvironmentStateManager\Core12` classes directly.

Referencing the version specific classes directly has always been discouraged.
The documented public API is the version agnostic
:php:`EnvironmentBuilderFactoryInterface`, :php:`EnvironmentBuilderInterface`,
:php:`StateManagerInterface` and :php:`StateInterface`, which are unaffected.

Migration
=========

Upgrade the instance to TYPO3 v13 and PHP 8.2 or higher, either beforehand or
within the same step as updating this extension.

Instances which can not be upgraded yet stay on the 1.x releases, maintained in
branch :file:`1`, which continue to support TYPO3 v12 and v13 with PHP 8.1 and
higher.

Replace direct references to :php:`Core12` classes with the version agnostic
public API interfaces, which resolve to the implementation matching the running
TYPO3 core version through dependency injection.
