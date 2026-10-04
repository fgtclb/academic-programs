<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Imaging;

use FGTCLB\AcademicPrograms\Enumeration\PageTypes;
use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\ColourSchemeAwareIconsTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendIconsAssertionTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\IconFilesAssertionTrait;
use PHPUnit\Framework\Attributes\Test;

/**
 * The icon rules of docs/architecture/icons.md, checked for every icon of this extension:
 * its page type and content element icons, the credit points icon of the facts, and the
 * category type and group icons, which reach both registries. The other icon tests spell
 * out the identifiers that exist today. These checks read the registration files, the
 * registries, the TCA and the icon directory instead, so an icon added later is held to the
 * same rules without a test naming it.
 */
final class IconRulesTest extends AbstractAcademicProgramsTestCase
{
    use ColourSchemeAwareIconsTrait;
    use FrontendIconsAssertionTrait;
    use IconFilesAssertionTrait;

    #[Test]
    public function backendIdentifiersFollowTheNamingScheme(): void
    {
        $this->assertIconIdentifiersFollowTheNamingScheme('academic_programs', ['doktype', 'plugin']);
    }

    #[Test]
    public function frontendIdentifiersFollowTheNamingScheme(): void
    {
        $this->assertFrontendIconIdentifiersFollowTheNamingScheme('academic_programs', ['info']);
    }

    #[Test]
    public function everyBackendIconOfTheExtensionIsInTheHouseFormat(): void
    {
        $this->assertEveryIconOfTheExtensionIsInTheHouseFormat('academic_programs');
    }

    #[Test]
    public function everyFrontendIconOfTheExtensionIsInTheHouseFormat(): void
    {
        $this->assertEveryFrontendIconOfTheExtensionIsInTheHouseFormat('academic_programs');
    }

    /**
     * Some category types of this extension are drawn from the shared set of academic_base.
     * The walks above attribute an icon by its file, so those icons are seen by the walks of
     * academic_base, which find every icon drawn from one of its files, whoever registers it.
     */
    #[Test]
    public function everyIconDrawnFromTheSharedSetIsInTheHouseFormat(): void
    {
        $this->assertEveryIconOfTheExtensionIsInTheHouseFormat('academic_base');
        $this->assertEveryFrontendIconOfTheExtensionIsInTheHouseFormat('academic_base');
    }

    /**
     * Every type of a table of this extension, and every content element and page type
     * named here, names an icon of this extension in the group of its kind, drawn for the
     * colour scheme. A table added later is covered as it is. A content element or page
     * type added later has to be added to the list.
     */
    #[Test]
    public function everyTypeOfTheExtensionNamesAnIconOfItsOwn(): void
    {
        $this->assertEveryTypeOfTheExtensionNamesAnIconOfItsOwn(
            'academic_programs',
            contentTypes: [
                'academicprograms_programlist',
                'academicprograms_programdetails',
                'academicprograms_programfinder',
            ],
            pageTypes: [PageTypes::TYPE_ACADEMIC_PROGRAM],
        );
    }

    #[Test]
    public function everyIconFileIsTheSourceOfARegisteredIcon(): void
    {
        $this->assertEveryIconFileIsRegistered('academic_programs');
    }

    #[Test]
    public function everyIconFileIsAttributedInTheLicenceNotice(): void
    {
        $this->assertEveryIconFileIsAttributedInTheNotice('academic_programs');
    }
}
