.. _important-1787055000:

===================================================================
Important: The shipped route enhancer configuration can be imported
===================================================================

Description
===========

This extension ships a ready made route enhancer for its program list plugin in
:file:`Configuration/Yaml/Routes.yaml`, meant to be pulled into a site
configuration:

..  code-block:: yaml
    :caption: config/sites/my_site/config.yaml

    imports:
      - resource: 'EXT:academic_programs/Configuration/Yaml/Routes.yaml'

That file was indented with tab characters. YAML forbids a tab as indentation,
so it was never valid YAML: importing it aborts the whole site configuration
with the :php:`ParseException` *A YAML file cannot contain tabs as indentation*.
It has not been usable since it was added.

Three further defects made it a no-op even once it parsed:

* The enhancer key was misspelled :yaml:`AacdemicPrograms`. The key only names
  the enhancer within the site configuration and appears in no URL, but it is
  what an integrator reads and copies.
* :yaml:`plugin: List` named a plugin that does not exist. The plugins this
  extension registers are :yaml:`ProgramList` and :yaml:`ProgramDetails`, so
  the enhancer bound to the argument namespace of neither.
* There was no :yaml:`routes:` key. An Extbase enhancer registers one route per
  entry of that list, so an enhancer without it contributes nothing whatever
  else it declares.

The file now carries a complete enhancer for the program list plugin, mapping
the sorting of the list into the path.

Impact
======

The file parses, can be imported into a site configuration, and produces
speaking URLs for the sorting arguments of the program list — for example
:file:`/programs/last-updated/desc` instead of a query string. The mapped values
are those of :php:`FGTCLB\AcademicPrograms\Enumeration\SortingOptions`, which is
also what the select fields of the plugin offer.

Note that the sorting and filter form shipped with the plugin submits by POST,
so its own requests carry no arguments in the URL and are not enhanced. The
enhancer takes effect for links built with the arguments as GET parameters.

Affected Installations
======================

All installations of this extension. Nothing has to be done, and nothing can
have depended on the previous state: a site configuration that imported the file
did not load at all.

An installation that worked around this by copying the enhancer into its own
site configuration keeps that copy, including the spelling and the defects it
copied. A key below :yaml:`routeEnhancers` is freely chosen and appears in no
URL, so renaming it there is optional; the :yaml:`plugin` value and the missing
:yaml:`routes:` list are not.

.. index:: YAML, Frontend, ext:academic_programs
