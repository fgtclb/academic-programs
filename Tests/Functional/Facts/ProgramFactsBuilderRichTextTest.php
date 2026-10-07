<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Facts;

use FGTCLB\AcademicPrograms\Domain\Model\ProgramFact;
use FGTCLB\AcademicPrograms\Domain\Model\ProgramFactsSourceInterface;
use FGTCLB\AcademicPrograms\Enumeration\ProgramFactsPlace;
use FGTCLB\AcademicPrograms\Service\ProgramFactsBuilder;
use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\CategoryTypes\Collection\CategoryCollection;
use FGTCLB\CategoryTypes\Domain\Model\Category;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

/**
 * Whether the builder calls a program field fact rich text, read from the TCA schema of
 * `pages` for the program page type.
 *
 * Each case changes `$GLOBALS['TCA']` the way a site package would in
 * `Configuration/TCA/Overrides/pages.php` and rebuilds the schema of the instance from it.
 * The rendered output of the same changes, loaded from a site package, is covered by
 * "ProgramFactsPlainTextTest".
 */
final class ProgramFactsBuilderRichTextTest extends AbstractAcademicProgramsTestCase
{
    private const FIELDS = 'degree,creditPoints,jobProfile,performanceScope,prerequisites';

    #[Test]
    public function theShippedTextFieldsAreRichText(): void
    {
        $this->assertSame(
            [
                'degree' => false,
                'creditPoints' => false,
                'jobProfile' => true,
                'performanceScope' => true,
                'prerequisites' => true,
            ],
            $this->richTextFlags(),
        );
    }

    #[Test]
    public function editorSwitchedOffForTheProgramPageTypeIsNoRichText(): void
    {
        $GLOBALS['TCA']['pages']['types']['20']['columnsOverrides']['job_profile']['config']['enableRichtext'] = false;

        $flags = $this->richTextFlags();

        $this->assertFalse($flags['jobProfile']);
        $this->assertTrue($flags['performanceScope']);
        $this->assertTrue($flags['prerequisites']);
    }

    #[Test]
    public function editorSwitchedOffForTheFieldIsNoRichText(): void
    {
        $GLOBALS['TCA']['pages']['columns']['performance_scope']['config']['enableRichtext'] = false;

        $flags = $this->richTextFlags();

        $this->assertTrue($flags['jobProfile']);
        $this->assertFalse($flags['performanceScope']);
        $this->assertTrue($flags['prerequisites']);
    }

    #[Test]
    public function editorSwitchedOnAgainForTheProgramPageTypeIsRichText(): void
    {
        $GLOBALS['TCA']['pages']['columns']['prerequisites']['config']['enableRichtext'] = false;
        $GLOBALS['TCA']['pages']['types']['20']['columnsOverrides']['prerequisites']['config']['enableRichtext'] = true;

        $this->assertTrue($this->richTextFlags()['prerequisites']);
    }

    /**
     * Without a type entry of its own, a program page is edited with the base columns, and
     * the builder reads those instead of failing on a sub-schema that does not exist.
     */
    #[Test]
    public function withoutTheProgramPageTypeTheBaseColumnDecides(): void
    {
        unset($GLOBALS['TCA']['pages']['types']['20']);
        $GLOBALS['TCA']['pages']['columns']['job_profile']['config']['enableRichtext'] = false;

        $flags = $this->richTextFlags();

        $this->assertFalse($flags['jobProfile']);
        $this->assertTrue($flags['performanceScope']);
    }

    /**
     * A sub-schema holds only the fields its form shows. A field a site package leaves
     * out of the program page form is read from the base column, so a value stored while
     * the field had the editor still renders as HTML.
     */
    #[Test]
    public function aFieldLeftOutOfTheProgramPageFormIsReadFromTheBaseColumn(): void
    {
        $GLOBALS['TCA']['pages']['types']['20']['showitem'] = 'doktype, title';
        $GLOBALS['TCA']['pages']['columns']['performance_scope']['config']['enableRichtext'] = false;

        $flags = $this->richTextFlags();

        $this->assertTrue($flags['jobProfile']);
        $this->assertFalse($flags['performanceScope']);
        $this->assertTrue($flags['prerequisites']);
    }

    #[Test]
    public function aFieldThatIsNoTextFieldIsNoRichText(): void
    {
        $GLOBALS['TCA']['pages']['columns']['job_profile']['config'] = ['type' => 'input'];

        $this->assertFalse($this->richTextFlags()['jobProfile']);
    }

    #[Test]
    public function aRemovedColumnIsNoRichText(): void
    {
        unset($GLOBALS['TCA']['pages']['columns']['job_profile']);

        $this->assertFalse($this->richTextFlags()['jobProfile']);
    }

    /**
     * The flag of every fact the builder returns, by identifier, after the schema was
     * rebuilt from the current `$GLOBALS['TCA']`.
     *
     * @return array<string, bool>
     */
    private function richTextFlags(): array
    {
        $tcaSchemaFactory = $this->get(TcaSchemaFactory::class);
        $tcaSchemaFactory->rebuild($GLOBALS['TCA']);
        $facts = (new ProgramFactsBuilder($tcaSchemaFactory))->build($this->program(), self::FIELDS, ProgramFactsPlace::Page);

        $flags = [];
        foreach ($facts as $fact) {
            $flags[$fact->identifier] = $fact->isRichText;
        }
        $this->assertSame(
            explode(',', self::FIELDS),
            array_map(static fn(ProgramFact $fact): string => $fact->identifier, $facts),
        );
        return $flags;
    }

    private function program(): ProgramFactsSourceInterface
    {
        $collection = $this->createStub(CategoryCollection::class);
        $collection->method('getAllCategoriesByType')->willReturn([
            'degree' => [1 => new Category(1, 0, 'Bachelor of Science')],
        ]);

        return new class ($collection) implements ProgramFactsSourceInterface {
            public function __construct(
                private readonly CategoryCollection $collection,
            ) {}

            public function getCategoryCollection(): CategoryCollection
            {
                return $this->collection;
            }

            public function getCreditPoints(): int
            {
                return 180;
            }

            public function getJobProfile(): string
            {
                return '<p>Research and development.</p>';
            }

            public function getPerformanceScope(): string
            {
                return '<p>Six semesters.</p>';
            }

            public function getPrerequisites(): string
            {
                return '<p>General qualification for university entrance.</p>';
            }
        };
    }
}
