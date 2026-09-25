..  _breaking-program-finder-registered-upstream:

==================================================================
Breaking: The content type academicprograms_programfinder is taken
==================================================================

Description
===========

This extension now registers the content element :guilabel:`Program Finder`
itself, as the content type `academicprograms_programfinder` with the Extbase
plugin `ProgramFinder` of the extension `AcademicPrograms`, and the FlexForm
field `settings.listPid` for its target page. See
:ref:`program-finder`.

Those are the names projects used when they registered a finder of their own in
the plugin namespace of this extension.

Impact
======

A project that registers `academicprograms_programfinder` itself, usually in an
extension loaded after this one, collides with this registration. What the
editor and the visitor get depends on how the project registered it:

*   A type item added with :php:`ExtensionManagementUtility::addPlugin()` or
    :php:`ExtensionUtility::registerPlugin()` replaces the one of this
    extension. One added with :php:`addTcaSelectItem()`, or written into the
    items array directly, is added a second time, and the type select offers
    the element twice.
*   A FlexForm data structure the project assigns replaces the one of this
    extension, so the backend shows the fields of the project finder and not
    :guilabel:`Filter types` and :guilabel:`Preselected categories`.
*   A :php:`configurePlugin()` call for `ProgramFinder` that names the
    :php:`ProgramController` of this extension replaces the actions of the
    finder, and its list of uncached actions with them: the project action
    keeps rendering the element, cached unless the project lists it as
    uncached.
*   One that names a controller of its own only adds it: the controller of this
    extension stays the default, and the finder of this extension renders the
    element with its own options. A template of the project is used only when
    it is the :file:`Program/Finder.html` of the last bullet.
*   A class that extends or replaces :php:`ProgramController` - a subclass, an
    XCLASS or a service alias - and declares a :php:`finderAction()` of its own
    now overrides the action of this extension. With a signature that is not
    compatible (a required parameter, another return type) PHP refuses to load
    the class, which takes the program list down as well.
*   A template :file:`Program/Finder.html` of the project in the template root
    path of :typoscript:`plugin.tx_academicprograms` is now rendered by the
    action of this extension, with its variables.

Stored finder records keep their content type and their `settings.listPid`, and
render with the finder of this extension once the project registration is gone.

Affected Installations
======================

Installations that register the content type `academicprograms_programfinder`,
configure the plugin `ProgramFinder` of the extension `AcademicPrograms`, or
declare a :php:`finderAction()` in a class that extends or replaces
:php:`ProgramController`, in a site package or project extension.

Installations with a finder under a content type of their own are not affected;
they can migrate to this element, see below.

Migration
=========

Remove from the project:

*   the type item of `academicprograms_programfinder` in the TCA of
    :sql:`tt_content` (:php:`ExtensionManagementUtility::addPlugin()`,
    :php:`addTcaSelectItem()` or a direct write), and the :sql:`pi_flexform`
    data structure assigned to it;
*   the :php:`ExtensionUtility::configurePlugin()` call for `ProgramFinder`;
*   the FlexForm file of the finder;
*   the controller code of the finder - a :php:`finderAction()` in a class that
    extends or replaces :php:`ProgramController`, or a branch on the content
    type in an overridden :file:`Program/List.html`;
*   the page TSconfig that enables the type and adds its wizard entry.

Then enable the finder set `fgtclb/academic-programs-program-finder`, or depend
on the aggregate set `fgtclb/academic-programs`.

A hard-coded preselected category becomes the field
:guilabel:`Preselected categories`. A project template
:file:`Program/Finder.html` already is an override of the template of this
extension: adapt it to the variables :html:`{filterTypes}`,
:html:`{categories}`, :html:`{preselection}`, :html:`{listUri}` and
:html:`{data}`, or delete it.

The upgrade check :bash:`academic:upgrade:check` of :guilabel:`EXT:academic_base`
reports an XCLASS of the controller; it does not report a project registration
of the content type.

A finder under a content type of its own migrates by changing the content type
of its records to `academicprograms_programfinder` and storing its target page
as `settings.listPid` in :sql:`pi_flexform`.

..  index:: Backend, Frontend, FlexForm, TCA, TSConfig, ext:academic_programs
