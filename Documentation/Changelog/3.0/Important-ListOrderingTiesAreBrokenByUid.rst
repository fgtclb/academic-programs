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

No visible change is expected: within records equal in the demanded ordering,
:sql:`uid` ascending is the order every supported database returned in
practice, it is simply guaranteed now rather than coincidental.

Affected Installations
======================

Every installation of this extension.

.. index:: Frontend, PHP-API, ext:academic_programs
