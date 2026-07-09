..  _installation:

============
Installation
============

The extension has to be installed like any other TYPO3 CMS extension. You can
download and install it using one of the following methods.

..  tabs::

    ..  group-tab:: Composer

        ..  code-block:: bash
            :caption: Install the stable release

            composer require 'fgtclb/academic-programs':'^2'

        ..  tip::

            We recommend to pin academic extensions on minor level to mitigate
            possible issues in projects in case `composer update` is used based
            on the fact that projects commonly tends to override fluid templates
            and changes for otherwise non-breaking changes are possible promoted
            to be breaking in case template changes are not adopted why it has
            been considered to mark template changes as breaking changes on
            minor version updates. That means, we suggest to use for example
            following command to ensure that we stay in the minor version range
            but have the hightest patchlevel enforced for it and keep possible
            bugfix releases for that minor version possible to install:

            ..  code-block:: bash

                composer require 'fgtclb/academic-programs':'~2.3.1@dev'

    ..  group-tab:: Extension Manager

        #.  Switch to the module :guilabel:`Admin Tools > Extensions`.
        #.  Switch to :guilabel:`Get Extensions`.
        #.  Search for the extension key :guilabel:`academic_programs`.
        #.  Import the extension from the repository.

    ..  group-tab:: Upload ZIP (TER)

        #.  Get the current version from `TER`_ by downloading the ZIP version.
            Alternatively, get the ZIP from the `GitHub Releases`_ page.
        #.  Switch to the module :guilabel:`Admin Tools > Extensions`.
        #.  Enable :guilabel:`Upload Extension`.
        #.  Select or drag the extension ZIP archive and upload the file.

..  _TER: https://extensions.typo3.org/extension/academic_programs
..  _GitHub Releases: https://github.com/fgtclb/academic-programs/releases
