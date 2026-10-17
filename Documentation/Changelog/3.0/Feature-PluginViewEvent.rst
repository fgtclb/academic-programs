..  _feature-plugin-view-event:

===================================================
Feature: The plugins dispatch the plugin view event
===================================================

Description
===========

The program list, the program finder and the program details of
:guilabel:`academic_programs` dispatch
:php:`\FGTCLB\AcademicBase\Event\ModifyPluginViewEvent` of
:guilabel:`academic_base`, once each time they render, after assigning their
own variables. A listener adds a variable to their templates without replacing
the controller.

The context of the event names the extension :php:`AcademicPrograms` and the
plugin names :php:`ProgramList`, :php:`ProgramFinder` and
:php:`ProgramDetails`, which a listener checks for the plugin it means.

The event, with an example listener, is described in the :ref:`changelog of
academic_base
<fgtclb/academic-base:feature-modify-plugin-view-event>`.
What these plugins pass in is described in
:ref:`developers-event-modify-plugin-view-event`.

Impact
======

A project adds a variable to the templates of these plugins with an event
listener instead of a subclass or a copy of the controller. The behaviour is
the same on TYPO3 v13 and v14.

..  index:: Frontend, PHP-API, NotScanned, ext:academic_programs
