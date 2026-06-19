..  include:: /Includes.rst.txt

..  _installation:

============
Installation
============

The extension has to be installed like any other TYPO3 CMS extension.
You can install the extension using one of the following methods:

#.  **Use composer**:
    Run

    ..  code-block:: bash

        composer require fgtclb/environment-state-manager

    in your TYPO3 installation.

#.  **Get it from the Extension Manager**:
    Switch to the module :guilabel:`Admin Tools > Extensions`.
    Switch to :guilabel:`Get Extensions` and search for the extension key
    *environment_state_manager* and import the extension from the repository.

#.  **Get it from typo3.org**:
    You can always get the current version from `TER`_ by downloading the zip
    version. Upload the file afterwards in the Extension Manager.

..  _TER: https://extensions.typo3.org/extension/environment_state_manager

The extension does not require any further configuration. See the
:ref:`Developer Corner <developer>` on how to use the provided services.
