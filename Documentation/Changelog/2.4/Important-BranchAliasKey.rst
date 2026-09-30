..  _important-1790796667:

=========================================================
Important: The development branch installs as `2.4.x-dev`
=========================================================

Description
===========

The branch alias of the `2.x` development branch of `fgtclb/academic-programs`
is keyed to the version name Composer gives that branch, `2.x-dev`. It was
keyed to `dev-2`, a name Composer never gives a branch called `2`, and Composer
skips such an alias without a word: `2.4.x-dev` did not exist, and no
requirement `~2.4.0@dev` of a sibling package could be satisfied from the
branch.

Impact
======

`2.4.x-dev` now exists for `fgtclb/academic-programs`.
`composer require fgtclb/academic-programs:2.4.x-dev`, or `:2.x-dev`, installs
the development state of the `2.x` line, and so does every package of this
repository that requires it with `~2.4.0@dev`.

An inline alias added to a project to work around it, for example
`"fgtclb/academic-base": "2.x-dev as 2.4.x-dev"`, is no longer needed and can
be removed.

Released versions and the `main` branch (`dev-main`, `3.0.x-dev`) are not
affected.

Affected Installations
======================

Composer installations that require a package of this repository from its `2.x`
development branch. Classic, non-Composer installations are not affected.

.. index:: ext:academic_programs
