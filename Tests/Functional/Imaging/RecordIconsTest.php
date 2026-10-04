<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Imaging;

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use FGTCLB\AcademicPrograms\Enumeration\PageTypes;
use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\ColourSchemeAwareIconsTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendIconsAssertionTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\IconRegistry;

/**
 * Every identifier below is what a TCA record type resolves to, for the page type, the
 * content elements and the category types, so it reaches the record list, the page tree,
 * the page module and FormEngine through the *default* markup. That markup has to be
 * the inlined file rather than an <img>, because an <img> is opaque to CSS and keeps the
 * ink of its file on the dark cards of a dark backend colour scheme (ACE-523).
 *
 * The identifiers are spelled out here rather than read back out of the registration, so a
 * rename has to be made twice instead of silently agreeing with itself.
 */
final class RecordIconsTest extends AbstractAcademicProgramsTestCase
{
    use ColourSchemeAwareIconsTrait;
    use FrontendIconsAssertionTrait;

    private const DOKTYPE_ICON = 'tx-academicprograms-doktype-program';

    private const PLUGIN_ICON = 'tx-academicprograms-plugin-programs';

    private const SHIPPED_DRAWING = 'EXT:academic_programs/Resources/Public/Icons/plugin/programs.svg';

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function recordIconIdentifiers(): \Generator
    {
        $identifiers = [
            self::DOKTYPE_ICON,
            self::PLUGIN_ICON,
            'category_types.programs.admission_restriction',
            'category_types.programs.application_period',
            'category_types.programs.begin_program',
            'category_types.programs.costs',
            'category_types.programs.paying',
            'category_types.programs.degree',
            'category_types.programs.department',
            'category_types.programs.standard_period',
            'category_types.programs.location',
            'category_types.programs.program_type',
            'category_types.programs.teaching_language',
            'category_types.programs.topic',
        ];
        foreach ($identifiers as $identifier) {
            yield $identifier => [$identifier];
        }
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function categoryTypeIconIdentifiers(): \Generator
    {
        foreach (self::recordIconIdentifiers() as $name => $arguments) {
            if (str_starts_with($arguments[0], 'category_types.')) {
                yield $name => $arguments;
            }
        }
    }

    /**
     * The frontend renders the category type icons too, from the frontend icon registry,
     * with the file and the provider of the backend.
     */
    #[Test]
    #[DataProvider('categoryTypeIconIdentifiers')]
    public function categoryTypeIconIsTheSameFrontendIcon(string $identifier): void
    {
        $this->assertIconIsRegisteredInBothRegistries($identifier);
        $this->assertFrontendIconIsRegisteredWithProvider($identifier, CurrentColorSvgIconProvider::class);
        $this->assertFrontendIconMarkupFollowsTheTextColour($identifier);
    }

    #[Test]
    #[DataProvider('recordIconIdentifiers')]
    public function recordIconIsRegisteredWithTheColourSchemeAwareProvider(string $identifier): void
    {
        $this->assertIconIsRegisteredWithCurrentColorProvider($identifier);
    }

    #[Test]
    #[DataProvider('recordIconIdentifiers')]
    public function recordIconIsInlinedInBothMarkups(string $identifier): void
    {
        $this->assertIconIsInlinedInBothMarkups($identifier);
    }

    #[Test]
    #[DataProvider('recordIconIdentifiers')]
    public function recordIconMarkupFollowsTheTextColour(string $identifier): void
    {
        $this->assertIconMarkupFollowsTheTextColour($identifier);
    }

    #[Test]
    #[DataProvider('recordIconIdentifiers')]
    public function renderedRecordIconCarriesItsIdentifier(string $identifier): void
    {
        $this->assertRenderedIconCarriesItsIdentifier($identifier);
    }

    /**
     * The identifiers above are hand maintained, so they cannot catch a record icon that is
     * added later and never converted. This one is derived from the TCA and does.
     */
    #[Test]
    public function everyRecordTypeIconOfThisExtensionIsColourSchemeAware(): void
    {
        $this->assertEveryRecordTypeIconIsColourSchemeAware('academic_programs');
    }

    /**
     * The page type shows its icon in two places that are written separately: the item of
     * the page type select and the page tree, which reads `typeicon_classes`.
     */
    #[Test]
    public function pageTypeIsDrawnWithTheDoktypeIcon(): void
    {
        $itemIcons = array_column($GLOBALS['TCA']['pages']['columns']['doktype']['config']['items'] ?? [], 'icon', 'value');

        $this->assertSame(self::DOKTYPE_ICON, $itemIcons[PageTypes::TYPE_ACADEMIC_PROGRAM] ?? null);
        $this->assertSame(
            self::DOKTYPE_ICON,
            $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes'][PageTypes::TYPE_ACADEMIC_PROGRAM] ?? null,
        );
    }

    /**
     * Every content element of this extension, read from the CType items rather than from a
     * list, so an element added later cannot keep an icon of its own unnoticed. The item
     * icon is what `addPlugin()` copies into `typeicon_classes` of `tt_content`, which is
     * what the page module draws.
     */
    #[Test]
    public function everyContentElementIsDrawnWithThePluginIcon(): void
    {
        $itemIcons = [];
        foreach ($GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'] ?? [] as $item) {
            if (str_starts_with((string)($item['value'] ?? ''), 'academicprograms_')) {
                $itemIcons[$item['value']] = $item['icon'] ?? null;
            }
        }
        ksort($itemIcons);

        $this->assertSame(
            [
                'academicprograms_programdetails' => self::PLUGIN_ICON,
                'academicprograms_programfinder' => self::PLUGIN_ICON,
                'academicprograms_programlist' => self::PLUGIN_ICON,
            ],
            $itemIcons,
        );
        foreach (array_keys($itemIcons) as $cType) {
            $this->assertSame(self::PLUGIN_ICON, $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'][$cType] ?? null, $cType);
        }
    }

    /**
     * The extension registers exactly these two backend icons, both from the shipped
     * drawing. Renamed identifiers keep no alias, and `Extension.svg` is the extension icon
     * only, found by convention and registered for nothing.
     */
    #[Test]
    public function extensionRegistersItsTwoBackendIconsOnly(): void
    {
        $iconRegistry = $this->get(IconRegistry::class);

        $ownIcons = [];
        foreach ($iconRegistry->getAllRegisteredIconIdentifiers() as $identifier) {
            // Reading the configuration of a deprecated core icon raises a deprecation, and
            // none of them is ours.
            if ($iconRegistry->isDeprecated($identifier)) {
                continue;
            }
            $source = (string)($iconRegistry->getIconConfigurationByIdentifier($identifier)['options']['source'] ?? '');
            if (str_starts_with($source, 'EXT:academic_programs/') && !str_starts_with($identifier, 'category_types')) {
                $ownIcons[$identifier] = $source;
            }
        }
        ksort($ownIcons);

        $this->assertSame(
            [
                self::DOKTYPE_ICON => self::SHIPPED_DRAWING,
                self::PLUGIN_ICON => self::SHIPPED_DRAWING,
            ],
            $ownIcons,
        );
    }
}
