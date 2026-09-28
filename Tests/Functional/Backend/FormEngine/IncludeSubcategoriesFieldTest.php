<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Backend\FormEngine;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The field "Include subcategories" of the program list (1) and the program finder (2) as
 * an editor gets it.
 *
 * The form is compiled the way FormEngine compiles it when the content element is opened,
 * see `docs/architecture/backend-select-items.md`.
 */
final class IncludeSubcategoriesFieldTest extends AbstractAcademicProgramsTestCase
{
    private const FIELD = 'settings.filter.includeSubcategories';

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/IncludeSubcategoriesField/records.csv');
        $this->setUpBackendUser(1);
    }

    /**
     * @return \Generator<string, array{0: int}>
     */
    public static function elementDataProvider(): \Generator
    {
        yield 'program list' => [1];
        yield 'program finder' => [2];
    }

    /**
     * Off by default, so a new element lists what one without the field lists.
     */
    #[DataProvider('elementDataProvider')]
    #[Test]
    public function theFieldIsAToggleOffByDefault(int $contentUid): void
    {
        $field = $this->compile($contentUid)['processedTca']['columns']['pi_flexform']['config']['ds']['sheets']['sDEF']['ROOT']['el'][self::FIELD] ?? null;

        $this->assertIsArray($field, 'The element has no field to include subcategories.');
        $this->assertSame('check', $field['config']['type'] ?? null);
        $this->assertSame('checkboxToggle', $field['config']['renderType'] ?? null);
        $this->assertSame('0', (string)($field['config']['default'] ?? ''));
        $this->assertSame('Include subcategories', $field['label'] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    private function compile(int $contentUid): array
    {
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $request = (new ServerRequest('https://localhost/typo3/record/edit'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        return GeneralUtility::makeInstance(FormDataCompiler::class)->compile(
            [
                'request' => $request,
                'tableName' => 'tt_content',
                'vanillaUid' => $contentUid,
                'command' => 'edit',
            ],
            $this->get(TcaDatabaseRecord::class),
        );
    }
}
