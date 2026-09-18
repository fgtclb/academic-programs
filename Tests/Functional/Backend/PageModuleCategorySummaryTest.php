<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Backend;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;

/**
 * The page module of a program page shows the categories of that page above the content grid.
 *
 * These tests go through the event dispatcher rather than calling the listener, so they cover
 * the registration as much as the output: `EventListener\AddPageModuleCategorySummary` is
 * picked up from its `#[AsEventListener]` attribute alone, and removing the attribute is what
 * turns them red.
 *
 * `ModifyPageLayoutContentEvent` is dispatched by
 * `TYPO3\CMS\Backend\Controller\PageLayoutController::mainAction()` on TYPO3 v13 and v14
 * alike, and both versions render its header content as `eventContentHtmlTop` in the page
 * module template.
 */
final class PageModuleCategorySummaryTest extends AbstractAcademicProgramsTestCase
{
    private const PROGRAM_PAGE = 2;
    private const STANDARD_PAGE = 3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/PageModuleCategorySummary/pages.csv');
        $this->setUpBackendUser(1);
        // Part of every backend request, and `ModuleTemplate` reads it directly. It also pins
        // the language the type titles below are asserted in.
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    #[Test]
    public function theCategoriesOfThePageAreShownInThePageModule(): void
    {
        $headerContent = $this->headerContentOf(self::PROGRAM_PAGE);

        $this->assertStringContainsString('Bachelor of Science', $headerContent);
        $this->assertStringContainsString('Campus A', $headerContent);
    }

    /**
     * The second half of the defect this change repairs: the removed partial translated
     * `sys_category.academic_programs.{type}`, a key that exists in no XLF file of this
     * extension. The real keys are `sys_category.programs.*`, and the summary takes the
     * titles from the registered category types rather than from a key convention - so these
     * are the titles of `Configuration/CategoryTypes.yaml`, resolved.
     */
    #[Test]
    public function everyCategoryTypeIsLabelledWithItsRegisteredTitle(): void
    {
        $headerContent = $this->headerContentOf(self::PROGRAM_PAGE);

        $this->assertStringContainsString('Degree', $headerContent);
        $this->assertStringContainsString('Location', $headerContent);
    }

    /**
     * A type of the group the page carries no category of is listed all the same, so an
     * editor sees that the type exists and is unset rather than nothing at all.
     */
    #[Test]
    public function aTypeWithoutACategoryIsListedAsNotSet(): void
    {
        $headerContent = $this->headerContentOf(self::PROGRAM_PAGE);

        $this->assertStringContainsString('Teaching language', $headerContent);
        $this->assertStringContainsString('Not set', $headerContent);
    }

    /**
     * The fixture page carries the same two categories as the program page, so an empty header
     * is the page type being rejected and not an empty result set.
     */
    #[Test]
    public function aStandardPageGetsNoSummaryAlthoughItCarriesTheSameCategories(): void
    {
        $this->assertSame('', $this->headerContentOf(self::STANDARD_PAGE));
    }

    private function headerContentOf(int $pageId): string
    {
        $request = (new ServerRequest('https://localhost/typo3/module/web/layout'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('/module/web/layout', ['packageName' => 'typo3/cms-backend']))
            ->withQueryParams(['id' => (string)$pageId]);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $event = new ModifyPageLayoutContentEvent(
            $request,
            $this->get(ModuleTemplateFactory::class)->create($request),
        );
        $this->get(EventDispatcherInterface::class)->dispatch($event);

        return $event->getHeaderContent();
    }
}
