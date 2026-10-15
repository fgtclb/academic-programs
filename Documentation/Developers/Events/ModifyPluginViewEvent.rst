..  _developers-event-modify-plugin-view-event:

=====================
ModifyPluginViewEvent
=====================

:php:`\FGTCLB\AcademicBase\Event\ModifyPluginViewEvent` belongs to
:guilabel:`academic_base`, and the plugins of seven academic extensions
dispatch it. It hands a listener the view of a plugin right before it
renders, so the listener can assign further variables that a template override
renders. The event, its methods and the context it carries are described in
the `documentation of academic_base
<https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Developers/Events/ModifyPluginViewEvent.html>`__.
This page states what the plugins of :guilabel:`academic_programs` pass in.

When it is dispatched
=====================

Each of the three content elements dispatches the event once per rendering,
after its action assigned its own variables:

..  list-table::
    :header-rows: 1
    :widths: 25 20 15 40

    *   -   Content element
        -   :php:`getPluginName()`
        -   :php:`getActionName()`
        -   Dispatched
    *   -   :guilabel:`Course List`
        -   ``ProgramList``
        -   ``list``
        -   after :ref:`developers-event-modify-program-list-event` and the
            seven variables of the list
    *   -   :guilabel:`Course Finder`
        -   ``ProgramFinder``
        -   ``finder``
        -   after :ref:`developers-event-modify-program-list-event` and the
            seven variables of the finder
    *   -   :guilabel:`Course Data`
        -   ``ProgramDetails``
        -   ``show``
        -   after the variables of the details

:php:`getControllerExtensionName()` returns ``AcademicPrograms`` for all three.
A submission of the filter form of the list is answered with a redirect and
renders nothing, so it dispatches no event. The program page is no plugin and
dispatches :ref:`developers-event-modify-program-data-event` instead.

What this extension passes in
=============================

The view holds these variables when the event fires. A listener that assigns
one of them again replaces it in the templates.

..  list-table::
    :header-rows: 1
    :widths: 25 75

    *   -   Content element
        -   Variables
    *   -   :guilabel:`Course List`
        -   :html:`programs`, :html:`data` (the content element record as an
            array), :html:`record`, :html:`demand`, :html:`categories`,
            :html:`filterTypes`, :html:`visitorSelection` and the
            :html:`settings`
    *   -   :guilabel:`Course Finder`
        -   :html:`data`, :html:`record`, :html:`categories`,
            :html:`filterTypes`, :html:`listUri`, :html:`preselection`,
            :html:`programCategories` (JSON) and the :html:`settings`
    *   -   :guilabel:`Course Data`
        -   :html:`data`, :html:`record`, :html:`program` (the
            :php:`\FGTCLB\AcademicPrograms\Domain\Model\Program` of the page the
            element sits on, or :php:`null`), :html:`facts` and the
            :html:`settings`

:php:`getSettings()` of the context returns the settings of the content
element merged over :typoscript:`plugin.tx_academicprograms.settings`, the
values of the site settings included.

Example
=======

A site places the :guilabel:`Course Data` element on its program pages and
wants a link below the facts that leads back to the program list. The site
already names that page in the site setting
:typoscript:`plugin.tx_academicprograms.page.listPid`, which the header of the
program page links to. The listener hands it to the view of the element:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/AddProgramListPageToProgramDetails.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicBase\Event\ModifyPluginViewEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class AddProgramListPageToProgramDetails
    {
        #[AsEventListener(identifier: 'my-extension/add-program-list-page-to-program-details')]
        public function __invoke(ModifyPluginViewEvent $event): void
        {
            $context = $event->getPluginControllerActionContext();
            if ($context->getControllerExtensionName() !== 'AcademicPrograms'
                || $context->getPluginName() !== 'ProgramDetails'
            ) {
                return;
            }
            $listPid = (int)($context->getSite()?->getSettings()->get('plugin.tx_academicprograms.page.listPid', 0) ?? 0);
            $event->getView()->assign('programListPid', $listPid);
        }
    }

An override of :file:`Details/Show.html` renders the link while
:html:`{programListPid}` is not 0. The setting is a site setting of the set
`fgtclb/academic-programs`. A site on the static templates sets it in its
:file:`settings.yaml` as well, or the listener reads 0 there.

Rules and pitfalls
==================

*   The event fires for the plugins of seven academic extensions. Check the
    extension name and the plugin name before acting, as the example does.
*   A variable the action assigned is replaced only in the view. The query has
    run already: programs a listener assigns as :html:`programs` are rendered,
    but the filter keeps offering the categories of the queried programs. A
    change of which programs are shown belongs in
    :ref:`developers-event-modify-program-demand-event` or
    :ref:`developers-event-modify-program-list-event`.
*   The finder hands :html:`programCategories` to the browser as JSON. A
    listener that replaces it has to keep its shape, a list with one list of
    category uids per program, or the narrowing of the options stops.

Introduced in
=============

*   :ref:`feature-plugin-view-event` made the three content elements dispatch
    the event in 3.0.
*   :ref:`breaking-1791043408` names the event as the replacement of a
    subclass of :php:`DetailsController` that added a view variable.
*   The event itself was introduced by
    `academic_base 3.0 <https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Changelog/3.0/Feature-ModifyPluginViewEvent.html>`__.
