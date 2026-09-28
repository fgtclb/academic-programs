..  _feature-1790613217:

===========================================================
Feature: The program category trees start at a site setting
===========================================================

Description
===========

Editors picked the categories of a program page, the :guilabel:`Default
categories` of the :guilabel:`Program List` and the :guilabel:`Preselected
categories` of the :guilabel:`Program Finder` from the whole category tree of
the installation, although the program categories are usually one branch of
it. Projects restricted the tree in their own TCA or page TSconfig, with a
category uid that differs between the live and the development database.

The new site setting :typoscript:`plugin.tx_academicprograms.categoryRootUids`
names the categories the three trees start at, comma separated:

..  code-block:: yaml
    :caption: config/sites/my-site/settings.yaml

    plugin:
      tx_academicprograms:
        categoryRootUids: '12'

Editors are then offered those categories and the categories below them, on
the pages of that site. One uid shows that category as the top of the tree,
and it can be selected itself. Several uids show each of them below the root
of the tree.

The setting is declared with the aggregate set `fgtclb/academic-programs`. A
site that does not depend on that set, because it depends on a component set
only or uses the static templates, writes it to its :file:`settings.yaml` in
the nested form shown above. The setting is read by the backend, and there is
no TypoScript constant for it.

See :ref:`configuration-category-tree-root`.

Impact
======

The setting is empty, and nothing changes until a site sets it. An empty
setting, a value that names no category uid and a page outside of every site
keep the whole tree, and the categories of a standard page are not affected.

A project that restricts the tree itself sets the uid of its program category
root in the site settings and removes its own configuration. Page TSconfig
:typoscript:`TCEFORM.pages.categories.config.treeConfig.startingPoints` still
wins over the setting until it is removed.

A category a program page carries outside of the configured branch is no
longer shown in the tree. It stays on the page as long as the editor does not
touch the tree, and is removed without notice as soon as the editor changes the
selection there. Set the root before the editors start.

..  index:: Backend, FlexForm, TCA, ext:academic_programs
