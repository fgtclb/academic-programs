.. _important-1787141400:

======================================================================
Important: The page TSconfig directory is now spelled :file:`TSconfig`
======================================================================

Description
===========

The extensions of this set spelled their page TSconfig directory in three
different ways — :file:`TsConfig`, :file:`TSconfig` and :file:`TSConfig`. They now
all use :file:`TSconfig`, which is how TYPO3 spells the term and what the core
documentation uses.

In this extension the directory was :file:`Configuration/TsConfig`.

Nothing was broken before, because every import matched the directory it
pointed at. The reason to change it is that a filesystem is case sensitive on
Linux and case insensitive on macOS and Windows, so a path copied between two
of these extensions resolved on one machine and silently not on another — and
a page TSconfig :typoscript:`@import` that does not resolve raises no error, the
configuration is simply absent.

Impact
======

Every file this extension ships moved with the directory. The imports inside
the extension were updated in the same change, so an installation that only
installs the extension has nothing to do.

**An integrator who references these paths from their own configuration has to
update them**, because the old path no longer exists:

..  code-block:: typoscript
    :caption: Page TSconfig of your own site package

    # before
    @import 'EXT:academic_programs/Configuration/TsConfig/page.tsconfig'
    # after
    @import 'EXT:academic_programs/Configuration/TSconfig/page.tsconfig'

    # before
    @import 'EXT:academic_programs/Configuration/TsConfig/Page/BackendLayouts/*.tsconfig'
    # after
    @import 'EXT:academic_programs/Configuration/TSconfig/Page/BackendLayouts/*.tsconfig'

Affected Installations
======================

Every installation that imports a page TSconfig file of this extension by path,
or that copied such a path into its own site package. An installation that
relies only on the auto-included :file:`Configuration/page.tsconfig` of the
extension, or on its site set, is unaffected.

.. index:: TSConfig, Backend, ext:academic_programs
