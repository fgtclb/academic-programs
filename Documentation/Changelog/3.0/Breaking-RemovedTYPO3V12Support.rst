..  _breaking-removed-typo3-v12-support:

===================================
Breaking: Removed TYPO3 v12 support
===================================

Description
===========

Support for TYPO3 v12 has been removed for the `3.x` version line, based on
the dual TYPO3 core version support per major version of the academic
extensions support matrix.

This includes removing build, test and configuration parts only required for
TYPO3 v12. Version specific code paths are dropped in a dedicated step.

Impact
======

TYPO3 v12 or older instances can no longer install or update to the `3.x`
version of the academic extensions and are required to upgrade TYPO3 first.

The extension cannot be installed on TYPO3 v12 anymore but does not break
otherwise.

Affected installations
======================

All installations using an academic extension on TYPO3 v12 that want to
upgrade to the `3.x` version line.

Migration
=========

Upgrade the TYPO3 installation to a supported version (TYPO3 v13) beforehand
or within the same upgrade step.
