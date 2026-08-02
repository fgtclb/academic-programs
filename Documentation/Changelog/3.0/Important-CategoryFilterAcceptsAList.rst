.. _important-1785709800:

=====================================================
Important: The category filter accepts a list of uids
=====================================================

Description
===========

`Factory\\DemandFactory::createDemandObject()` read the submitted category
filter itself:

..  code-block:: php

    foreach ($demandFromForm['filterCollection'] as $uids) {
        $categoryUids = array_merge($categoryUids, GeneralUtility::intExplode(',', $uids));
    }

`GeneralUtility::intExplode()` takes a **string**, so a filter value that is a
list ended in a `TypeError`:

..  code-block:: text

    TypeError: GeneralUtility::intExplode(): Argument #2 ($string) must be of
    type string, array given

That is exactly what a filter select with `multiple` submits — the argument the
category filter select gained in the same release. A `filterCollection` that is
not an array at all did not raise, but emitted a PHP warning
*foreach() argument must be of type array|object* and silently dropped the
filter.

Both shapes are reachable from a crafted request without any template being
involved, because the controller action takes the demand as a plain
`?array $demand = null` and validates nothing.

The filter is read through
`FGTCLB\\CategoryTypes\\Filter\\CategoryFilterNormalizer` now, which accepts a
single value, a list and a comma separated string, and treats anything it cannot
read as no filter.

Impact
======

*   A category filter select with `multiple` works.
*   A request with an unreadable filter renders the list unfiltered instead of
    failing.
*   An unselected filter no longer contributes uid `0` to the query. The
    prepended "all options" entry carries an empty value, and every unselected
    category type added one `0` to the uid list. The rendered result is
    unchanged — no category has uid `0` — but the list handed to
    `CategoryRepository::findByGroupAndUidList()` is empty now, which it accepts
    since the same release.
*   A uid submitted twice is used once.

Affected Installations
======================

None have to act. Own code calling `createDemandObject()` with a hand built
demand array keeps working, and gains the list shape.

References
==========

*   `CategoryFilterNormalizer
    <https://github.com/fgtclb/typo3-category-types>`__ in
    `EXT:category_types` — the class the filter is read with, and where its
    behaviour is documented.

.. index:: Frontend, PHP-API, NotScanned
