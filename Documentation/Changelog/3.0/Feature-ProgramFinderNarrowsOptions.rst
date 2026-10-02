..  _feature-1790975989:

==============================================================
Feature: The program finder narrows its options in the browser
==============================================================

Description
===========

The :guilabel:`Program Finder` now offers only the combinations that find a
program. Once a visitor selects a degree, every topic no program of that degree
carries is disabled, and it is enabled again as soon as the degree is cleared
or changed. A category the editor preselected narrows the other selects as soon
as the page has loaded. All of this happens in the browser, without a request
and without reloading the page.

The submit button states how many programs the current selection finds, for
example "Show 2 programs". A visually hidden status message announces a changed
number to screen reader users, politely and without moving the focus. Loading
the page announces nothing.

Without JavaScript the finder behaves as before: every option a program in the
storage of the finder carries is selectable, the button shows its plain label,
and submitting opens the program list with the selection applied.

With :guilabel:`Include subcategories` switched on in the finder, a program
counts for every category above the ones it carries, so a parent the server
offers stays selectable in the browser as well.

See :ref:`program-finder-narrowing`.

Impact
======

Every finder narrows its options after the update. The page with a finder loads
the new module
:js:`@fgtclb/academic-programs/frontend/program-finder.js` through the import
map of the extension, and the finder form carries the categories of the
programs of its storage in the attribute
:html:`data-academic-programs-finder-programs`. Narrowing disables options,
also on a site that hides options without results, where it is only the
options no program carries that are left out.

A project that overrides :file:`Program/Finder.html` gets the narrowing only
when its template carries the data attributes the module reads, see
:ref:`program-finder-narrowing`. Without them the override keeps working as
before and nothing is narrowed. A project that reloads the finder on every
change, through a turbo frame for example, can drop that code.

The count is written into a new span inside the submit button. The button
reads the new labels `finder.submit.count.one` and
`finder.submit.count.other` of :file:`locallang.xlf`, with `%d` for the number.

..  index:: Frontend, Fluid, JavaScript, ext:academic_programs
