..  _important-minimum-typo3-versions:

==============================================
Important: TYPO3 13.4.35 or 14.3.7 is required
==============================================

Description
===========

The `3.x` version line of `EXT:academic_programs` requires TYPO3 **13.4.35** or
**14.3.7** at least, the latest patch release of each supported core version
when the line was prepared. Earlier patch releases of TYPO3 v13 and v14 are no
longer accepted.

TYPO3 14.3.7 carries a core fix for the language overlay of records a plugin
shows although they are hidden or scheduled (forge issue #100638). Before it,
the hidden translation of such a record was not found on TYPO3 v14, and with
a strict language fallback the record disappeared from the translated page.

The requirement is declared in both places TYPO3 reads it:

*   :file:`composer.json` requires every TYPO3 system extension it names with
    ``~13.4.35 || ~14.3.7``.
*   :file:`ext_emconf.php` depends on ``typo3`` and ``core`` with
    ``13.4.35-14.3.99``, and every other system extension it names carries
    the same range.

Impact
======

Composer refuses to install or update the extension next to an older TYPO3
patch release, and the Extension Manager of a classic installation refuses to
activate it.

Affected Installations
======================

Every installation on TYPO3 v13 below 13.4.35, or on TYPO3 v14 below 14.3.7.

Migration
=========

Update TYPO3 to its latest patch release first, or in the same step as the
extension. Both patch releases change database indexes of the core, so apply
the database changes afterwards, with the database analyzer of the Install
Tool or with :bash:`vendor/bin/typo3 extension:setup`.

..  index:: NotScanned, ext:academic_programs
