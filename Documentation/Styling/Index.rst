..  index:: ! Styling
..  _styling:

=======
Styling
=======

The plugins and the program page of this extension render their markup with
the class system of the academic extensions and ship no styling for it. The
system, the classes of Bootstrap it keeps and how a site package styles the
markup are described once, in the chapter `Styling of academic_base
<https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Styling/Index.html>`__.
This chapter shows how the markup of this extension is built up.

..  contents::
    :local:
    :depth: 1

..  _styling-outermost-element:

The outermost elements
======================

..  list-table::
    :header-rows: 1
    :widths: 30 35 35

    *   -   Plugin or page
        -   Content element or page type
        -   Outermost element
    *   -   Program list
        -   `academicprograms_programlist`
        -   `academic-programs-list`
    *   -   Program details
        -   `academicprograms_programdetails`
        -   `academic-programs-detail`
    *   -   Program finder
        -   `academicprograms_programfinder`
        -   `academic-programs-finder`
    *   -   Program page
        -   the page type of a program
        -   `academic-programs-page container`

..  _styling-list:

The program list
================

..  code-block:: html

    <div class="academic-programs-list" data-academic-programs-list="…">
        <!-- the header of the content element, when the plugin renders it -->
        <div class="ace-content" data-academic-programs-list-content …>
            <form class="ace-form" data-academic-programs-list-form …>
                <div class="ace-filters">
                    <div class="row">
                        <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                            <div class="ace-filter ace-field ace-select-wrap">
                                <label class="ace-label" for="…">Degree</label>
                                <select class="ace-control ace-select" …>…</select>
                            </div>
                        </div>
                        <div class="col-12">
                            <details class="ace-more">…</details>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4 col-xl-3" data-academic-programs-list-submit>
                            <button type="submit" class="btn btn-primary">Show programs</button>
                        </div>
                    </div>
                </div>
            </form>
            <ul class="ace-list ace-active-filters" aria-label="…">…</ul>
            <a class="ace-link" href="…">Reset filters</a>
            <p class="ace-count">12 programs</p>
            <div class="ace-itemlist">
                <div class="row">
                    <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                        <article class="ace-item">…</article>
                    </div>
                </div>
            </div>
        </div>
        <p class="ace-status visually-hidden" role="status" aria-live="polite" …></p>
    </div>

*   The filter bar is built up like the one of the other list plugins: the
    sorting selects, one filter per category type, the further filters inside
    the :html:`<details class="ace-more">` with its
    :html:`<summary class="ace-toggle">`, and the submit button.
*   With JavaScript, the list script replaces the `ace-content` when a select
    changes, without a page load, and hides the column of the submit button
    with the attribute :html:`hidden`. The `ace-status` is outside the replaced
    region and announces the new number of results to a screen reader. With
    the script switched off, see :ref:`styling-script-switch`, the button stays
    and the form is sent as a request of its own.
*   The active filters, the reset link and the count are only rendered when the
    content element switches them on, see :ref:`configuration-active-filters`.
    An active filter is an `ace-list-item ace-active-filter` holding an
    `ace-link` with an `ace-icon`.
*   A list without programs renders the message in a
    :html:`<span class="ace-empty">` in place of the item list.

..  _styling-item:

A program
---------

..  code-block:: html

    <article class="ace-item">
        <div class="ace-item-content">
            <header class="ace-header">
                <h2 class="ace-title"><a href="…">Program title</a></h2>
            </header>
            <ul class="ace-list ace-attributes">
                <li class="ace-list-item ace-attribute" data-academic-programs-fact="degree">
                    <span class="ace-icon">…</span>
                    <b class="ace-label">Degree:</b>
                    <span class="ace-value">Bachelor</span>
                </li>
            </ul>
        </div>
        <!-- the first image of the program, preset "card" -->
    </article>

The attributes are the facts the content element shows on a card, see
:ref:`program-facts`. A fact without an icon renders no `ace-icon`. The value
of a rich text fact is an `ace-value ce-bodytext`, so the content styles of the
site apply to it.

..  _styling-detail:

The program details
===================

..  code-block:: html

    <div class="academic-programs-detail">
        <!-- the header of the content element, when the plugin renders it -->
        <ul class="ace-list ace-attributes">
            <li class="ace-list-item ace-attribute" data-academic-programs-fact="…">…</li>
        </ul>
    </div>

The facts are built up like those of a program in the list.

..  _styling-finder:

The program finder
==================

..  code-block:: html

    <!-- the header of the content element, when the plugin renders it -->
    <div class="academic-programs-finder">
        <form class="ace-form" data-academic-programs-finder-programs="…" …>
            <div class="ace-filters">
                <div class="row">
                    <div class="col-12 col-md">
                        <div class="ace-filter ace-field ace-select-wrap">
                            <label class="ace-label" for="…">Degree</label>
                            <select class="ace-control ace-select" data-academic-programs-finder-select …>…</select>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <button type="submit" class="btn btn-primary">
                            <span class="ace-count" data-academic-programs-finder-count>Show 12 programs</span>
                        </button>
                    </div>
                </div>
            </div>
            <p class="ace-status visually-hidden" role="status" aria-live="polite" …></p>
        </form>
    </div>

*   The header of the content element is rendered before the
    `academic-programs-finder`, not inside it.
*   The finder renders nothing without a list page or without a filter to
    show.
*   The finder script writes the number of matching programs into the
    `ace-count` of the button, and announces it in the `ace-status`. Without
    the script, the button keeps its label.
*   The submit button is a Bootstrap button, `btn btn-primary`, as the one of
    the list is.

..  _styling-page:

The program page
================

..  code-block:: html

    <div class="academic-programs-page container">
        <div class="ace-hero">
            <header class="ace-header">
                <h1 class="ace-title">Program title</h1>
                <p class="ace-subtitle">…</p>
            </header>
            <!-- the first image of the program, preset "detail" -->
        </div>
        <a class="ace-link" href="…">Back to the list</a>
        <a class="btn btn-primary ace-apply" href="…">Apply now</a>
        <ul class="ace-list ace-attributes">…</ul>
        <!-- the content elements of the page -->
    </div>

*   The back link is only rendered when a list page is configured, the
    application link only when the program has one, see
    :ref:`program-application-link`.
*   The application link is a Bootstrap button, `ace-apply` tells it from other
    buttons of the site.
*   The facts are the facts of the page, built up like those of a program in
    the list.
*   The page renders inside the layout of the site, see
    :ref:`program-page-layout`.

..  _styling-script-switch:

The scripts
===========

The list and the finder each load a script, which the site setting or
TypoScript constant `plugin.tx_academicprograms.assets.js` switches off for
both, see :ref:`configuration-javascript`. The markup stays the same, so a
script of the project can drive it through the same `data-academic-programs-*`
attributes. No other plugin and not the program page load a script.

..  _styling-example:

An example
==========

Rules in the stylesheet of the site package, scoped by the outermost element:

..  code-block:: css

    .academic-programs-list .ace-item {
        display: flex;
        flex-direction: column-reverse;
        height: 100%;
    }

    .academic-programs-list .ace-attributes,
    .academic-programs-page .ace-attributes {
        list-style: none;
        padding-left: 0;
    }

    .academic-programs-page .ace-apply {
        margin-block: 1rem;
    }

To change the markup itself, override the partials of the extension, the page
partials as described in :ref:`program-page-partials`.
