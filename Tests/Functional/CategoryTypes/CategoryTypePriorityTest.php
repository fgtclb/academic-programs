<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\CategoryTypes;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;

/**
 * A project raises the priority of a shipped type, and every place that lists the types of
 * the `programs` group follows: the categories of the program page and of the details
 * element, the filter selects of the list, the category summary of the page module and the
 * type select of a category.
 *
 * The fixture extension `test_programs_category_type_priority` overrides `location` with
 * `useExisting` and the priority 10. Every other type keeps the priority 0, so without the
 * override the order is the one of `Configuration/CategoryTypes.yaml`: degree, standard
 * period, location.
 *
 * "Applied Physics" carries one category of each of the three types. Page 10 is rendered as
 * a program page, page 12 carries the details element and page 2 the list with its filter.
 */
final class CategoryTypePriorityTest extends AbstractAcademicProgramsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    private const PROGRAM_PAGE_UID = 10;
    private const PROGRAM_PAGE = 'https://www.acme.com/applied-physics';
    private const DETAILS_PAGE = 'https://www.acme.com/applied-physics-details';
    private const LIST_PAGE = 'https://www.acme.com/programs';

    protected function setUp(): void
    {
        $this->testExtensionsToLoad[] = 'tests/programs-category-type-priority';
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/categoryTypePriority.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param list<string> $beforeExtension The page object of a site package.
     * @param list<string> $afterExtension
     */
    private function setUpSite(array $beforeExtension, array $afterExtension = []): void
    {
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_programs/Configuration/TypoScript/constants.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    ...$beforeExtension,
                    'EXT:academic_programs/Configuration/TypoScript/setup.typoscript',
                    ...$afterExtension,
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }

    /**
     * A site whose page object renders the content of the page and nothing else.
     */
    private function setUpPluginSite(): void
    {
        $this->setUpSite([], ['EXT:academic_programs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript']);
    }

    #[Test]
    public function programPageShowsTheLocationFirst(): void
    {
        $this->setUpSite(['EXT:academic_programs/Tests/Functional/Pages/Fixtures/TypoScript/Setup/SitePackage.typoscript']);

        $categories = $this->categoriesList($this->renderFrontendPage(self::PROGRAM_PAGE));

        $this->assertRenderedInOrder($categories, 'Location', 'Campus A', 'Degree', 'Bachelor of Science', 'Standard period', '6 semesters');
    }

    #[Test]
    public function detailsElementShowsTheLocationFirst(): void
    {
        $this->setUpPluginSite();

        $categories = $this->categoriesList($this->renderFrontendPage(self::DETAILS_PAGE));

        $this->assertRenderedInOrder($categories, 'Location', 'Campus A', 'Degree', 'Bachelor of Science', 'Standard period', '6 semesters');
    }

    #[Test]
    public function listOffersTheLocationFilterFirst(): void
    {
        $this->setUpPluginSite();

        $this->assertSame(
            ['location', 'degree', 'standard_period'],
            $this->renderedFilterTypes($this->renderFrontendPage(self::LIST_PAGE)),
        );
    }

    /**
     * The summary lists every type of the group, the ones the page has no category of
     * included, so the location is in front of the first shipped type as well.
     */
    #[Test]
    public function pageModuleSummaryListsTheLocationFirst(): void
    {
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');

        $headerContent = $this->pageModuleHeaderContent(self::PROGRAM_PAGE_UID);

        $this->assertRenderedInOrder($headerContent, 'Location', 'Campus A', 'Admission restriction', 'Degree', 'Bachelor of Science');
    }

    #[Test]
    public function typeSelectOffersTheLocationFirst(): void
    {
        $values = [];
        foreach ($GLOBALS['TCA']['sys_category']['columns']['type']['config']['items'] as $item) {
            if (($item['group'] ?? null) === 'programs') {
                $values[] = $item['value'];
            }
        }

        $this->assertSame('location', $values[0] ?? null);
        $this->assertSame(['location', 'degree', 'standard_period'], array_values(array_intersect($values, ['degree', 'standard_period', 'location'])));
    }

    /**
     * The category list of the program page or of the details element, and nothing around
     * it: the first list of the element `Program/Categories` is rendered into.
     */
    private function categoriesList(string $content): string
    {
        $wrapper = strpos($content, 'class="academic-programs-detail');
        $this->assertIsInt($wrapper, 'The page renders no program details.');
        $start = strpos($content, '<ul>', $wrapper);
        $this->assertIsInt($start, 'The page renders no category list.');
        $end = strpos($content, '</ul>', $start);
        $this->assertIsInt($end);

        return substr($content, $start, $end - $start);
    }

    /**
     * The category filter selects of the list form, by their `id`, which is the type
     * identifier, in document order.
     *
     * @return list<string>
     */
    private function renderedFilterTypes(string $content): array
    {
        $document = new \DOMDocument();
        $this->assertTrue(@$document->loadHTML($content));
        $selects = (new \DOMXPath($document))->query(
            '//form[contains(@class, "academic-programs-filtersorting")]//select[contains(@name, "[demand][filterCollection]")]'
        );
        $this->assertInstanceOf(\DOMNodeList::class, $selects);

        $identifiers = [];
        foreach ($selects as $select) {
            $this->assertInstanceOf(\DOMElement::class, $select);
            $identifiers[] = $select->getAttribute('id');
        }

        return $identifiers;
    }

    private function pageModuleHeaderContent(int $pageId): string
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

    /**
     * Each string is found after the previous one.
     */
    private function assertRenderedInOrder(string $content, string ...$strings): void
    {
        $offset = 0;
        foreach ($strings as $string) {
            $position = strpos($content, $string, $offset);
            $this->assertIsInt(
                $position,
                sprintf('"%s" is not rendered after offset %d.', $string, $offset),
            );
            $offset = $position + strlen($string);
        }
    }
}
