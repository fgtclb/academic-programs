.. _important-list-ordering-ties-are-broken-by-uid:

===============================================
Important: List ordering ties are broken by uid
===============================================

Description
===========

:php:`ProgramRepository::findByDemand()` orders by the sorting
option the plugin demands — and by nothing else, so records equal in that
ordering (two programs with the same title, for example) were returned in
whatever relative order the database yielded. On PostgreSQL that is not the
same list twice. The query now appends :sql:`uid` ascending as a tiebreaker.

Impact
======

No visible change is expected on SQLite, MySQL and MariaDB: within records equal
in the demanded ordering, :sql:`uid` ascending is the order they return in
practice, and it is guaranteed now rather than coincidental. PostgreSQL promises
no order without one, so an installation on it may see such a list change once.

Affected Installations
======================

Every installation of this extension.

.. index:: Frontend, PHP-API, ext:academic_programs
