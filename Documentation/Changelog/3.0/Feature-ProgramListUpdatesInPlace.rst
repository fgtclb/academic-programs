..  _feature-1790982306:

==================================================
Feature: The program list updates without a reload
==================================================

Description
===========

The :guilabel:`Program List` updates in place when a visitor changes a filter
or the sorting. The results, the filter form, the active filters and the
result count are replaced by the ones of the filtered list, without reloading
the page. The address bar shows the URL of the filtered list afterwards, so the
list can still be bookmarked, shared and reloaded, and the back and forward
buttons of the browser step through the selections. The page shows what a
reload of that URL shows: with two program lists on one page, a change in one
filters both, as it always did after the reload.

Screen reader users hear the number of programs found after an update, through
a visually hidden status message. The select the visitor changed keeps the
focus.

Without JavaScript the form now has a submit button and works as a plain form.
Before, it could not be submitted at all without JavaScript. With JavaScript
the button is hidden.

See :ref:`program-list-in-place`.

Impact
======

After updating the extension, every program list with a filter or a sorting
updates in place. The page loads the new module
:js:`@fgtclb/academic-programs/frontend/program-list.js` through the import map
of the extension. Where a request fails, the form is submitted as before and
the page reloads.

The selects of the list carry no inline :html:`onchange` handler any more. A
site with a strict content security policy no longer has to allow inline event
handlers for the list.

The button reads the new label `list.submit` of :file:`locallang.xlf`. The
announcement reads the labels of the result count, `list.resultCount.singular`
and `list.resultCount.plural`.

..  index:: Frontend, Fluid, JavaScript, ext:academic_programs
