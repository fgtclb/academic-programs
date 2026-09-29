..  _feature-1790703178:

=============================================
Feature: Program pages link to an application
=============================================

Description
===========

The :guilabel:`Program` tab of a program page offers two new fields: the
:guilabel:`Application link`, a page or an external URL, and an optional
:guilabel:`Label of the application link` of up to 60 characters.

The program page renders the link as a button right after its header and
image. Without a label, the link reads "Apply now" in the language of the
page. Without a link, the page renders no button, and neither does a link to a
page that cannot be linked, such as a hidden one.

The button is the new partial :file:`Program/Page/CallToAction.html`. Templates
reach the values as :html:`{program.applicationLink}` and
:html:`{program.applicationLinkLabel}`, on the program page and in a list item
of the :guilabel:`Program List`, whose default item does not render them. A
list item of your own renders the same button with
:html:`<f:render partial="Program/Page/CallToAction" arguments="{program: program}" />`.

See :ref:`program-application-link`.

Impact
======

Run the database compare of the :guilabel:`Maintenance` module or
:bash:`vendor/bin/typo3 extension:setup`. It adds the columns
`application_link` and `application_link_label` to :sql:`pages`. The extension
declares neither in :file:`ext_tables.sql`: TYPO3 derives both from the TCA.

A program page renders no button until an editor sets a link, so existing pages
look as before.

An installation that added a column `application_link` of its own can remove
its TCA and its :file:`ext_tables.sql` line for it. The data stays where it is,
and the program page renders it from then on. A template of its own that
renders the link can go, or the page renders a second button.

An installation that kept the link in a column of another name copies it once
before removing that column, for example:

..  code-block:: sql

    UPDATE pages
       SET application_link = tx_mysitepackage_apply_url
     WHERE doktype = 20
       AND application_link = ''
       AND tx_mysitepackage_apply_url IS NOT NULL
       AND tx_mysitepackage_apply_url <> '';

An override of :file:`Pages/AcademicProgram.html` renders no button, since it
does not render the new partial.

..  index:: Backend, Database, Fluid, Frontend, TCA, ext:academic_programs
