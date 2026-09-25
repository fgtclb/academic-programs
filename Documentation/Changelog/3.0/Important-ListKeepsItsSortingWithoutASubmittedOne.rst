..  _important-1790370061:

========================================================================
Important: The program list keeps its sorting when a filter carries none
========================================================================

Description
===========

The program list applied the :guilabel:`Default sorting` of its content element
only to a request without any demand. A request whose demand carried a category
filter but no sorting was sorted by title, ascending, the default of the
extension, whatever the element was configured with.

Such a request now keeps the sorting of the element. It is what three things
send:

*   the filter form of a list element whose sorting select is hidden
    (:guilabel:`Hide sorting`);
*   the :guilabel:`Program Finder`, which submits its selection to the list
    without a sorting (:ref:`program-finder`);
*   a link to the list that carries a category filter and no sorting.

A demand that carries a sorting, as the filter form of a list with a visible
sorting select always does, is sorted the way it asks, as before. A sorting
setting without a direction - a :typoscript:`plugin.tx_academicprograms.settings.sorting`
of `title` alone, where the element's :guilabel:`Default sorting` does not set
one - keeps the default sorting instead of raising a PHP warning.

Impact
======

A list element with a hidden sorting select and a :guilabel:`Default sorting`
other than title ascending shows the filtered list in that sorting after the
update, and the URL the filter submission redirects to carries it. So does
the list a finder opens.

..  index:: Frontend, ext:academic_programs
