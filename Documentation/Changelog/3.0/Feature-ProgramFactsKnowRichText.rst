..  _feature-programs-facts-know-rich-text:

======================================================
Feature: Program facts know whether they are rich text
======================================================

Description
===========

The facts of a program, on the program page, in the :guilabel:`Program Details`
content element and on the cards of the :guilabel:`Program List`, now know
whether the value of a program field is rich text. The job profile, the
performance scope and the prerequisites are rich text while their field has the
rich text editor on program pages, which it has as shipped.

The decision follows the TCA of :sql:`pages` for the program page type. A site
package that switches the editor off for one of the fields, for every page type
or in the ``columnsOverrides`` of the program page type only, changes the facts
without a template of its own. See :ref:`Rich text facts <program-facts-rich-text>`.

:file:`Program/Facts/Item.html` uses the new property :html:`{fact.isRichText}`:

*   The value of a rich text fact is rendered as it is stored, as before, in an
    element with the class :html:`ce-bodytext`, so the styles a site gives to
    the body text of content elements apply to it.
*   Any other value, the credit points and a text field without the editor, is
    escaped, and its line breaks are kept.

An override of the partial adopts the same branches:

..  code-block:: html
    :caption: Program/Facts/Item.html

    <f:if condition="{fact.isRichText}">
        <f:then>
            <span class="ce-bodytext">
                {fact.value -> f:format.raw()}
            </span>
        </f:then>
        <f:else>
            <span>
                <f:if condition="{fact.isCategoryType}">
                    <f:then>
                        <f:for each="{fact.categories}" as="category" iteration="i">
                            {category.title}{f:if(condition: '!{i.isLast}', then: ', ')}
                        </f:for>
                    </f:then>
                    <f:else>
                        {fact.value -> f:format.nl2br()}
                    </f:else>
                </f:if>
            </span>
        </f:else>
    </f:if>

Impact
======

With the shipped configuration the facts show the same values as before. The
value element of the three text facts gains the class :html:`ce-bodytext`, every
other value element stays a plain :html:`<span>`.

A project that switched the rich text editor off for one of the three fields
sees a change, and it is intended: the facts used to print the stored text as
HTML, unescaped and without its line breaks, although the backend offered the
field as plain text. They now show it as text. Stored markup, typed into the
field or left from the time it had the editor, appears literally. Such a project
either switches the editor on again for the field, or overrides
:file:`Program/Facts/Item.html` and keeps printing the value with
:html:`f:format.raw()`.

An override of :file:`Program/Facts/Item.html` that prints every value raw keeps
its output and gets no class.

..  index:: Frontend, TCA, ext:academic_programs
