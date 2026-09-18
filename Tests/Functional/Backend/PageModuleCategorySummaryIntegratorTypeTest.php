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
 * A category type an installation adds to the `programs` group is labelled with the title it
 * gave it.
 *
 * This is the reason the summary asks the registry for the title instead of building the
 * label key from the group and the type identifier. Repairing the key the removed partial
 * used - `sys_category.academic_programs.{type}` for `sys_category.programs.{type}` - would
 * have labelled the twelve types `Configuration/CategoryTypes.yaml` ships and left every
 * added one blank, because no XLF file of this extension carries a key for a type it does
 * not know about.
 *
 * The fixture type carries a **literal** title rather than an `LLL:` reference, which is the
 * shape `f:translate` answers an empty string for. The summary resolves it with
 * `LanguageService::sL()`, which returns a non-reference unchanged.
 */
final class PageModuleCategorySummaryIntegratorTypeTest extends AbstractAcademicProgramsTestCase
{
    private const PROGRAM_PAGE = 2;

    protected function setUp(): void
    {
        $this->testExtensionsToLoad[] = 'tests/programs-extra-category-type';
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/PageModuleCategorySummary/integratorType.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    #[Test]
    public function anAddedTypeIsLabelledWithTheTitleTheIntegratorGaveIt(): void
    {
        $this->assertStringContainsString('Internship Placement', $this->headerContent());
    }

    #[Test]
    public function theCategoryOfAnAddedTypeIsListed(): void
    {
        $this->assertStringContainsString('Summer Term Placement', $this->headerContent());
    }

    private function headerContent(): string
    {
        $request = (new ServerRequest('https://localhost/typo3/module/web/layout'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('/module/web/layout', ['packageName' => 'typo3/cms-backend']))
            ->withQueryParams(['id' => (string)self::PROGRAM_PAGE]);
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
