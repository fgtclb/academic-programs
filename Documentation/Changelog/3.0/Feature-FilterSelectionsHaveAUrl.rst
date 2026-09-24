.. _feature-1790226102:

=============================================
Feature: Program filter selections have a URL
=============================================

Description
===========

The filter and sorting form of the program list submits by POST, and a filtered
list used to be the answer to that POST: it had no URL of its own. It could not
be bookmarked or shared, a reload asked to send the form again, and no link
rendered inside the list could carry the selection.

The plugin now answers the submission with a `303 See Other` to the same page
and plugin, carrying the selection as GET arguments:

..  code-block:: text

    ?tx_academicprograms_programlist[action]=list
    &tx_academicprograms_programlist[controller]=Program
    &tx_academicprograms_programlist[demand][filterCollection][categories]=3,6
    &tx_academicprograms_programlist[demand][sortingDirection]=asc
    &tx_academicprograms_programlist[demand][sortingField]=title
    &cHash=…

*   The URL is built from what the plugin accepted, not from the request: a
    category of another group, a uid no category has and the referrer and
    request hash fields of the form never appear in it.
*   The categories of every category type are one comma separated list, in
    ascending order, so one selection has exactly one URL.
*   The sorting is always part of the URL. The content element's preset
    categories and sorting apply only to the page URL without any list
    argument, so a visitor who clears a preset category keeps it cleared.
*   A GET request with those arguments renders the list exactly as the POST did
    before, and the form shows the selection again.
*   The selection is read from the submitted form alone. A form that posts to
    the URL of a filtered list replaces its selection rather than adding to it,
    and a POST carrying no demand of the plugin - another plugin's form on the
    page - is not redirected.

Nothing changes in the templates, so overridden filter templates keep working as
long as they submit fields the demand knows: the redirect carries the selection
the plugin accepted and nothing else. A field a project adds to its filter
partial, read by a listener from the request, is lost on the redirect. The list
action stays non-cacheable, and the demand is not part of the cache hash, so
every filter URL of a list shares one page cache entry, see
:ref:`important-1790226107`.

Impact
======

*   A filter or sorting submission answers `303` instead of `200`. Browsers and
    `fetch()` follow it by default; JavaScript that posts the form and reads
    the HTML of the response itself has to follow the redirect. The redirect
    target carries the plugin's own arguments only: a page type, arguments of
    other plugins and campaign parameters of the submitted URL are not kept,
    so a form posted to a page type endpoint lands on the full page.
*   A controller subclass that overrides `listAction()` keeps working, but does
    not redirect unless it calls the parent action or the protected
    `redirectFilterSubmission()` first, as the shipped action does. A project
    that added its own redirect after a POST can drop it; the URLs that redirect
    produced change their `cHash`, see :ref:`important-1790226107`.
*   The route enhancer this extension ships puts the sorting of the redirect
    into the path, and keeps a category filter in the query string. It declares
    no `defaults` any more, see :ref:`important-1790226103`; an enhancer a site
    writes for this plugin itself must not declare them either.

.. index:: Frontend, NotScanned
