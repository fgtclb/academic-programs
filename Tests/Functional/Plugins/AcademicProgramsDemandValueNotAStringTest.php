<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Plugins;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * Every form and link of the program list carries a string per value of the demand. A
 * crafted request may carry an array instead, `demand[sortingField][]=title` for example,
 * and Extbase hands the demand argument over as it came. Such a value is ignored like an
 * unknown option, so the list keeps the default of the extension, title, A to Z, instead of
 * failing with a `TypeError` (ACE-926).
 *
 * - `/list`: the program list with the filter and the sorting shown, preset title, A to Z,
 *   with the programs of "/study".
 */
final class AcademicProgramsDemandValueNotAStringTest extends AbstractAcademicProgramsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const LIST_NAMESPACE = 'tx_academicprograms_programlist';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProgramsDemandValueNotAString/records.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_programs/Configuration/TypoScript/constants.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_programs/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_programs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @return \Generator<string, array{0: array<string, mixed>}>
     */
    public static function demandValueNotAStringDataProvider(): \Generator
    {
        yield 'sorting' => [['sorting' => ['title desc']]];
        yield 'sorting field' => [['sortingField' => ['lastUpdated']]];
        yield 'sorting direction' => [['sortingDirection' => ['desc']]];
    }

    /**
     * @param array<string, mixed> $demand
     */
    #[DataProvider('demandValueNotAStringDataProvider')]
    #[Test]
    public function aSubmittedDemandValueThatIsNotAStringIsIgnored(array $demand): void
    {
        $this->assertDefaultList($this->postDemand('https://www.acme.com/list', $demand));
    }

    /**
     * A link with the cache hash of its arguments, as one the site generated would carry.
     *
     * @param array<string, mixed> $demand
     */
    #[DataProvider('demandValueNotAStringDataProvider')]
    #[Test]
    public function aLinkDemandValueThatIsNotAStringIsIgnored(array $demand): void
    {
        $this->assertDefaultList($this->renderFrontendPage($this->pageUrl('/list', 2, [
            self::LIST_NAMESPACE => ['demand' => $demand],
        ])));
    }

    /**
     * Only the value that is not a string is ignored, the other values of the submission
     * still apply.
     */
    #[Test]
    public function theOtherSubmittedValuesStillApply(): void
    {
        $content = $this->postDemand('https://www.acme.com/list', [
            'sortingField' => ['lastUpdated'],
            'sortingDirection' => 'desc',
        ]);

        $this->assertRenderedInOrder($content, 'Molecular Chemistry', 'Mechanical Engineering');
        $this->assertRenderedInOrder($content, 'Mechanical Engineering', 'Applied Physics');
    }

    private function assertDefaultList(string $content): void
    {
        $this->assertRenderedInOrder($content, 'Applied Physics', 'Mechanical Engineering');
        $this->assertRenderedInOrder($content, 'Mechanical Engineering', 'Molecular Chemistry');
    }

    /**
     * The page as the answer to a post of the filter form renders it.
     *
     * @param array<string, mixed> $demand
     */
    private function postDemand(string $url, array $demand): string
    {
        $parsedBody = [self::LIST_NAMESPACE => ['demand' => $demand]];
        $body = new Stream('php://temp', 'rw');
        $body->write(http_build_query($parsedBody));
        $body->rewind();

        return $this->renderFrontendPage(
            (new InternalRequest($url))
                ->withMethod('POST')
                ->withAddedHeader('Content-Type', 'application/x-www-form-urlencoded')
                ->withBody($body)
                ->withParsedBody($parsedBody),
        );
    }

    /**
     * The URL of a page with plugin arguments and the cache hash a link carries: without
     * one, the page answers `404`.
     *
     * @param array<string, mixed> $arguments
     */
    private function pageUrl(string $path, int $pageId, array $arguments): string
    {
        $query = HttpUtility::buildQueryString($arguments);
        $cacheHash = GeneralUtility::makeInstance(CacheHashCalculator::class)
            ->generateForParameters('id=' . $pageId . '&' . $query);

        return 'https://www.acme.com' . $path . '?' . $query . '&cHash=' . $cacheHash;
    }

    private function assertRenderedInOrder(string $content, string $first, string $second): void
    {
        $firstPosition = strpos($content, $first);
        $secondPosition = strpos($content, $second);
        $this->assertNotFalse($firstPosition, $first . ' is not rendered.');
        $this->assertNotFalse($secondPosition, $second . ' is not rendered.');
        $this->assertLessThan($secondPosition, $firstPosition, $first . ' is not rendered before ' . $second . '.');
    }
}
