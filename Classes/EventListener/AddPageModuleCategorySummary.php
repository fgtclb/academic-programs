<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\EventListener;

use FGTCLB\AcademicPrograms\Enumeration\PageTypes;
use FGTCLB\CategoryTypes\Backend\PageCategorySummaryRenderer;
use TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent;

/**
 * Shows the categories of a program page in the page module, above the content grid.
 *
 * The summary itself is `EXT:category_types`, which renders the same table for the page types
 * of this extension, of `EXT:academic_projects` and of `EXT:academic_partners`. All this listener
 * contributes are the two constants that make it a program summary: the page type and the
 * category group.
 *
 * It needs no condition of its own. `renderForPageOfType()` answers with an empty string for
 * every page that is not of the given type, and `addHeaderContent()` appends an empty string
 * without a trace - so the page module of a standard page is byte for byte what it was.
 *
 * `addHeaderContent()` and not `setHeaderContent()`: the header of the page module is shared
 * with the listeners of every other extension, and setting replaces what they contributed.
 *
 * Registered with the `event.listener` tag in `Configuration/Services.yaml` rather than with
 * an attribute: TYPO3's `Core\Attribute\AsEventListener` does not exist on TYPO3 v12, which
 * this branch supports, and Symfony's attribute of that name is not a substitute - it
 * registers nothing here and the listener would silently never fire.
 *
 * Until ACE-688 this extension shipped the summary as
 * `Resources/Private/Backend/Partials/PageLayout/Doktype20.html`, registered in
 * `Configuration/page.tsconfig` as `templates.typo3/cms-backend.academic-programs`. No template of
 * `EXT:backend` renders a `PageLayout/Doktype*` partial, so nothing rendered it.
 *
 * It did render once. `861d7d0e9` (2023-03-16) added the markup as a full override of
 * core's page module template, at `Backend/Templates/PageLayout/PageLayout.html`,
 * registered through `module.tx_backend.view.templateRootPaths.10`; it rendered the
 * categories and then delegated to core's `PageLayout/Grid`. One day later `7b8eac8cf`
 * renamed it to the partial above and switched the registration to `partialRootPaths`,
 * which is where it stopped appearing. TYPO3 v12.0 then dropped
 * `module.tx_backend.view` entirely (Breaking: #96812), and the
 * `templates.typo3/cms-backend.*` line is its replacement - pointing at the same
 * unrendered partial. Both the partial and the line are gone now.
 */
final class AddPageModuleCategorySummary
{
    private const CATEGORY_GROUP = 'programs';

    public function __construct(
        private readonly PageCategorySummaryRenderer $pageCategorySummaryRenderer,
    ) {}

    public function __invoke(ModifyPageLayoutContentEvent $event): void
    {
        $event->addHeaderContent($this->pageCategorySummaryRenderer->renderForPageOfType(
            $event->getRequest(),
            PageTypes::TYPE_ACADEMIC_PROGRAM,
            self::CATEGORY_GROUP,
        ));
    }
}
