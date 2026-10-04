<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\CategoryTypes;

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendIconsAssertionTrait;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class CategoryTypesTest extends AbstractAcademicProgramsTestCase
{
    use FrontendIconsAssertionTrait;

    #[Test]
    public function extensionCategoryTypesYamlIsLoaded(): void
    {
        /** @var CategoryTypeRegistry $categoryTypeRegistry */
        $categoryTypeRegistry = $this->get(CategoryTypeRegistry::class);
        $groupedCategoryTypes = $categoryTypeRegistry->getGroupedCategoryTypes();
        $this->assertCount(1, array_keys($groupedCategoryTypes));
        $this->assertArrayHasKey('programs', $groupedCategoryTypes);
        $expected = include __DIR__ . '/Fixtures/DefaultExtensionCategoryTypes.php';
        $this->assertSame($expected, $categoryTypeRegistry->toArray());
    }

    /**
     * The group title of `Configuration/CategoryTypes.yaml` heads the types of the group in
     * the type select of a category, instead of the key `programs`.
     */
    #[Test]
    public function groupTitleHeadsTheTypesInTheTypeSelect(): void
    {
        $this->assertSame(
            'LLL:EXT:academic_programs/Resources/Private/Language/locallang_be.xlf:sys_category.programs.group',
            $GLOBALS['TCA']['sys_category']['columns']['type']['config']['itemGroups']['programs'] ?? null,
        );
    }

    /**
     * The declared group icon exists and is registered for inlining, with the same file in
     * the icon registry of the backend and in the frontend icon registry.
     */
    #[Test]
    public function groupIconIsShippedAndRegistered(): void
    {
        $group = $this->get(CategoryTypeRegistry::class)->getGroup('programs');
        $this->assertNotNull($group);
        $this->assertSame('EXT:academic_programs/Resources/Public/Icons/category-group/programs.svg', $group->getIcon());
        $this->assertFileExists(GeneralUtility::getFileAbsFileName($group->getIcon()));

        $iconRegistry = $this->get(IconRegistry::class);
        $this->assertSame(
            CurrentColorSvgIconProvider::class,
            $iconRegistry->getIconConfigurationByIdentifier('category_types_group.programs')['provider'] ?? null,
        );
        $this->assertIconIsRegisteredInBothRegistries('category_types_group.programs');
    }

    /**
     * The registry reads the paths and never opens the files, so an icon of a type pointing
     * at a missing file fails only where the icon is rendered. Every icon declared for the
     * group and its types has to be there, the ones in academic_base included.
     */
    #[Test]
    public function everyIconDeclaredInCategoryTypesYamlExists(): void
    {
        $configuration = Yaml::parseFile(__DIR__ . '/../../../Configuration/CategoryTypes.yaml');
        $entries = [...($configuration['groups'] ?? []), ...($configuration['types'] ?? [])];

        $this->assertCount(13, $entries);
        foreach ($entries as $entry) {
            $icon = (string)($entry['icon'] ?? '');
            $this->assertNotSame('', $icon, sprintf('"%s" declares no icon.', $entry['identifier'] ?? ''));
            $this->assertFileExists(
                GeneralUtility::getFileAbsFileName($icon),
                sprintf('The icon of "%s" does not exist.', $entry['identifier'] ?? ''),
            );
        }
    }
}
