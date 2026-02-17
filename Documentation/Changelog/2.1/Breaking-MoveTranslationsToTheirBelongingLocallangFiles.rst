.. include:: /Includes.rst.txt

.. _breaking-1748865621:

==============================================================
Breaking: Move translations to their belonging locallang files
==============================================================

Description
===========

As some labels used for the backend were maintained in the locallang files
for frontend usage, these labels were organized accordingly.


Impact
======

Some labels in the backend might not be translated anymore, if a custom
translation was only added to the frontend locallang file.


Affected Installations
======================

Installations with custom translations only added to the frontend locallang
files.


Migration
=========

Add the custom translations to the correct locallang files.

.. index:: Backend, Frontend
