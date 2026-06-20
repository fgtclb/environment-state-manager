..  include:: /Includes.rst.txt

..  _contribution:

============
Contribution
============

Contributions are welcome. This chapter describes how to set up a development
environment, how to run the tests and quality checks, and the commit message
rules used in this repository.

The source code and issue tracker are hosted on GitHub:
`fgtclb/environment-state-manager <https://github.com/fgtclb/environment-state-manager>`__.

..  _contribution-environment:

Development environment
=======================

All tests and quality tools run in containers through the
:file:`Build/Scripts/runTests.sh` wrapper. The only requirement on the host is
a container runtime – **podman** (preferred) or **docker**. The wrapper pulls
the required TYPO3 testing images on first use; nothing else is installed on the
host.

Dependencies are installed into the git-ignored :file:`.Build/` directory. The
wrapper installs them for a specific TYPO3 core and PHP version:

..  code-block:: bash

    # Install dependencies for TYPO3 v12 on PHP 8.2 (default matrix).
    Build/Scripts/runTests.sh -t 12 -p 8.2 -s composerUpdate

    # Switch the working copy to the TYPO3 v13 dependency set.
    Build/Scripts/runTests.sh -t 13 -p 8.2 -s composerUpdate

..  note::

    The ``-t`` option only influences what ``composerInstall`` /
    ``composerUpdate`` installs. To run the test suites against TYPO3 v13 you
    must run ``composerUpdate -t 13`` **first**; afterwards pass ``-t 13`` to the
    suite as well. ``composerUpdate`` removes and reinstalls :file:`.Build/` and
    :file:`composer.lock` (both git-ignored).

Run ``Build/Scripts/runTests.sh -h`` to see all options.

..  _contribution-tests:

Running tests
=============

..  code-block:: bash

    # Unit tests.
    Build/Scripts/runTests.sh -s unit

    # Functional tests on SQLite (no database container required).
    Build/Scripts/runTests.sh -s functional -d sqlite

    # Functional tests against other database management systems.
    Build/Scripts/runTests.sh -s functional -d mariadb -i 10.6
    Build/Scripts/runTests.sh -s functional -d mysql -i 8.0
    Build/Scripts/runTests.sh -s functional -d postgres -i 10

To run a single test class or method, append phpunit arguments **after a**
``--`` **separator** (the wrapper parses its own options with ``getopts``, so
phpunit flags must follow ``--``):

..  code-block:: bash

    Build/Scripts/runTests.sh -s functional -d sqlite -- --filter EnvironmentBuilderFactoryTest

The functional :file:`Tests/Functional/Core12/` and
:file:`Tests/Functional/Core13/` directories are gated with phpunit groups
(``not-core-12`` / ``not-core-13``), so the wrapper automatically runs the set
matching the installed core version.

..  _contribution-quality:

Code quality
============

..  code-block:: bash

    # Coding guidelines: fix in place ...
    Build/Scripts/runTests.sh -s cgl

    # ... or check only (no changes), as run in CI.
    Build/Scripts/runTests.sh -s cgl -n

    # Static analysis (PHPStan).
    Build/Scripts/runTests.sh -s phpstan

    # Render this documentation into Documentation-GENERATED-temp.
    Build/Scripts/runTests.sh -s renderDocumentation

Please make sure ``cgl -n``, ``phpstan`` and the unit and functional test
suites pass before opening a pull request.

..  _contribution-commit-messages:

Commit message rules
====================

This repository follows the TYPO3 core commit message conventions.

Format
------

..  code-block:: none

    [TAG] Short imperative summary

    A wrapped body (around 72 characters per line) that explains what the
    change does and, more importantly, why it is needed. Describe the
    behaviour change and the motivation, not the line-by-line diff.

Rules:

*   The subject line starts with a **tag** in square brackets, followed by a
    short summary in **imperative mood** ("Add", "Fix", "Rename"), capitalized
    and **without** a trailing period.
*   Keep the subject concise (aim for ~52 characters, ~72 at most).
*   Separate the subject from the body with a single blank line.
*   Wrap the body at around 72 characters and explain the *what* and *why*.
*   An issue reference is **not required**. When a change relates to a GitHub
    issue, you may reference it in the body (for example ``Resolves: #123``).

Tags
----

..  list-table::
    :header-rows: 1

    *   -   Tag
        -   Use for
    *   -   ``[FEATURE]``
        -   A new feature or capability.
    *   -   ``[TASK]``
        -   Maintenance, refactoring, tooling, tests and other non-functional
            changes.
    *   -   ``[BUGFIX]``
        -   A bug fix.
    *   -   ``[DOCS]``
        -   Documentation-only changes.

Breaking changes are additionally prefixed with ``[!!!]`` in front of the tag,
so reviewers and users can spot them immediately:

..  code-block:: none

    [!!!][TASK] Remove deprecated state accessor

    Explain what breaks and how to migrate.

Examples
--------

..  code-block:: none

    [FEATURE] Add backend environment builder

    [TASK] Mark concrete implementations as internal

    [DOCS] Document the public API and stability guarantees
