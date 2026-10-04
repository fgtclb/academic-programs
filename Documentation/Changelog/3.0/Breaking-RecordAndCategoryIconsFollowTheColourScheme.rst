..  _breaking-programs-record-and-category-icons-follow-the-colour-scheme:

============================================================
Breaking: Record and category icons follow the colour scheme
============================================================

Description
===========

The record icons of this extension were registered with the core provider
:php:`\TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider`, which renders
the default markup - the markup a :php:`typeicon_classes` entry reaches - as an
:html:`<img>` tag. An image is opaque to CSS, so the icon kept the ink of its
file whatever the backend colour scheme said, and a dark drawing stayed dark on
the dark cards of the record list.

They are now registered with
:php:`\FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider`,
which inlines the file in both markups, and the files themselves are drawn in
`currentColor` with no colour of their own.

That covers the icon of the academic program page type and the twelve category
type icons of this extension, which
:php:`EXT:category_types` registers as :php:`category_types.programs.*`. The
twelve category types ask for it with `inlineIcon: true` in
:file:`Configuration/CategoryTypes.yaml`; without that flag a category type
icon keeps the core provider.

Two of those twelve were Font Awesome Pro files, which this project has no
licence for. Every drawing of this extension has since been replaced by a Font
Awesome Free icon, see
:ref:`breaking-programs-credit-points-icon-is-a-frontend-icon`.

The two content elements of this extension named their icon by file path rather
than by identifier. :php:`ExtensionManagementUtility::addPlugin()` writes that
value into :php:`typeicon_classes` verbatim, and
:php:`IconRegistry::registerTCAIcons()` registers icons from :php:`ctrl.iconfile`
only, so the path was never a registered identifier and
:php:`IconFactory::getIcon()` answered with the :php:`default-not-found`
placeholder. They now name a registered identifier, see
:ref:`breaking-programs-credit-points-icon-is-a-frontend-icon`.

Impact
======

The twelve category type icons reach the **frontend**, in the facts of a
program: :file:`Partials/Program/Facts/Item.html` renders
:html:`<ab:icon identifier="{fact.iconIdentifier}" />` with the icon ViewHelper
of :guilabel:`academic_base`. The identifier is
:html:`category_types.programs.{type}` for a category type.
:guilabel:`category_types` registers them in the frontend icon
registry as well, with the same provider, see
:ref:`breaking-programs-credit-points-icon-is-a-frontend-icon`. The call does
not ask for the `inline` markup, so their rendered markup changes: an
:html:`<img>` of a fixed pixel size becomes an inlined :html:`<svg>` with
:html:`width="1em" height="1em"`, which follows the font size and the colour of
the text around it. Every site using the program plugins sees those icons resize
and recolour.

Site CSS or JavaScript that sized, coloured or addressed the :html:`<img>` has
to address the :html:`<svg>` instead.

In the backend, the page type icon and the twelve category type icons take the
text colour around them, so they stay legible in a dark backend colour scheme.
The content elements show their icon in the page module and the record list
instead of the red not-found placeholder.

Affected Installations
======================

Every installation of this extension. Installations that render the program
plugins in the frontend are affected visibly.

Migration
=========

Replace an image selector with an element selector in the site CSS, for example

..  code-block:: css

    /* before */
    .program-categories .icon img { width: 32px; }

    /* after */
    .program-categories .icon svg { width: 1.25em; }

The icon element keeps the surrounding
:html:`<span class="t3js-icon icon" data-identifier="…">` wrapper, so a
selector written against the wrapper needs no change.

.. index:: Backend, Frontend, TCA, ext:academic_programs
