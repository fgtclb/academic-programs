.. _important-1790226107:

========================================================
Important: The list demand is not part of the cache hash
========================================================

Description
===========

The program list redirects a filter submission to a URL carrying the selection
(:ref:`feature-1790226102`). The page such a URL shows is page-cached like any
other - the list itself is not cacheable and is rendered into it on every
request - and the page cache identifier contains every argument the cache hash
covers. With the demand in the hash, the redirect would hand out a valid hash
for any combination of categories and sorting a visitor cares to submit, each
one a page cache entry of its own.

The extension therefore excludes the demand of its plugin,
`tx_academicprograms_programlist`, from the cache hash in its
:file:`ext_localconf.php`:

..  code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = '^tx_academicprograms_programlist[demand]';

The leading `^` makes the entry a prefix, so every argument below `demand` is
excluded. Every filter URL of the list shares one page cache entry, and still
carries a `cHash`, over the `action` and `controller` arguments.

Behind the route enhancer the extension ships (:ref:`important-1790226103`),
the sorting is part of the path and the route maps `action` and `controller`:
the URLs carry no `cHash` at all, and each of the six sorting paths is a page
cache entry of its own, because the page cache identifier contains the route
arguments of the path.

Impact
======

*   The setting is installation-wide, and added to whatever the installation
    configures itself. It names the demand of this plugin and nothing else.
*   Arguments below `demand` need no `cHash`, also with
    :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['enforceValidation']`
    switched on, which it is in every new installation. A URL that carries the
    `action` or `controller` argument still needs one.
*   The exclusion depends on nothing in the cached page reading the demand: the
    list action is not cacheable, and no other part of the page may take it from
    the URL either - a link with :typoscript:`addQueryString = untrusted`, for
    example, would carry the demand of whoever filled the page cache entry. A
    project that registers the list action as cacheable has to remove these
    entries again, and every filter URL shared until then that carries a `cHash`
    answers with a 404 afterwards: the hash was computed without the demand, and
    :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFoundOnCHashError']` is on
    by default. The URLs behind the shipped route enhancer carry none; they keep
    rendering, and answer with a 404 only with
    :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['enforceValidation']`
    switched on, as it is in every new installation.
*   A URL whose `cHash` was computed with the demand in it and that carries the
    `action` or `controller` argument as well, as every URL
    `UriBuilder::uriFor()` builds does - one a project's own redirect after the
    POST produced, or a link a template built with demand arguments - fails the
    cache hash check after the update and answers with a 404,
    :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFoundOnCHashError']` being
    on by default. Links a template builds get the new hash on their next
    rendering; bookmarks of such URLs do not.
*   A plugin namespace changed with :typoscript:`view.pluginNamespace` is not
    covered.

.. index:: Frontend, LocalConfiguration, NotScanned
