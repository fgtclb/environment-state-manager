..  include:: /Includes.rst.txt

..  _developer:

================
Developer Corner
================

This chapter describes the API provided by the extension and shows how to use
the environment builder and the state manager. Always depend on the public
interfaces and let dependency injection resolve the TYPO3 core-version specific
implementation; see :ref:`developer-public-api` for what is covered by the
backward-compatibility promise and what is internal.

..  toctree::
    :maxdepth: 5
    :titlesonly:

    EnvironmentBuilder/Index
    StateManager/Index
    PublicApi/Index
