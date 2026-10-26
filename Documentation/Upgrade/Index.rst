..  _upgrade:

=======
Upgrade
=======

This chapter lists, for each upgrade path, the steps for
:guilabel:`academic_programs` in the order to take them. Each step links
the changelog entry with the detail.

..  list-table::
    :header-rows: 1

    *   -   Installed version
        -   Target version
        -   Page
    *   -   2.3
        -   2.4
        -   :ref:`upgrade-from-2-3-to-2-4`
    *   -   2.4
        -   3.0
        -   :ref:`upgrade-from-2-4-to-3-0`
    *   -   2.3
        -   3.0
        -   :ref:`upgrade-from-2-3-to-3-0`

..  _upgrade-before-you-start:

Before you start
================

*   Make a copy of the database and of :file:`fileadmin/`, so the upgrade can
    be repeated or rolled back.
*   Run the upgrade on a staging copy first, and on the live installation once
    the staging copy works.
*   Upgrade all academic extensions of an installation together, in one
    deployment, to the same release.
*   :guilabel:`academic_programs` requires :guilabel:`academic_base` and
    :guilabel:`category_types`. Upgrade them first, in this order, each by the
    upgrade chapter of its own manual.
*   An installation on TYPO3 v12, on a release older than 2.3, on an
    unreleased revision or on a development constraint of an earlier version
    takes the steps of :ref:`Installations outside these paths
    <fgtclb/academic-base:upgrade-outside-these-paths>` in the manual of
    :guilabel:`academic_base` first.

..  toctree::
    :hidden:
    :titlesonly:

    From23To24
    From24To30
    From23To30
