<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Imaging;

use FGTCLB\AcademicBase\Imaging\FrontendIconRegistry;
use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendIconsAssertionTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconProvider\AbstractSvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Imaging\IconSize;

/**
 * The icon of the credit points fact is a frontend icon: registered in
 * `Configuration/FrontendIcons.php` and rendered by `ab:icon` of academic_base in the
 * facts of a program. The backend never shows it, so the icon registry of the backend
 * must not know it, or a site that replaces it in `Configuration/Icons.php` sees no effect
 * and no error. It is drawn in `currentColor` and inlined, so it takes the text colour of
 * the facts it stands in. The icons of the page type and of the content elements are the
 * opposite case, backend icons only.
 *
 * The identifier and the file are spelled out rather than read from the builder, so a
 * rename has to be made twice instead of silently agreeing with itself.
 */
final class FactIconsTest extends AbstractAcademicProgramsTestCase
{
    use FrontendIconsAssertionTrait;

    private const CREDIT_POINTS_ICON = 'tx-academicprograms-info-credit-points';

    #[Test]
    public function creditPointsIconIsAFrontendIconWithTheShippedFile(): void
    {
        $this->assertFrontendIconIsRegisteredWithProvider(self::CREDIT_POINTS_ICON, CurrentColorSvgIconProvider::class);
        $this->assertSame(
            'EXT:academic_programs/Resources/Public/Icons/info/credit-points.svg',
            $this->get(FrontendIconRegistry::class)->getIconConfiguration(self::CREDIT_POINTS_ICON)['options']['source'] ?? null,
        );
    }

    /**
     * The default markup is the inlined file rather than an `<img>`, and the `inline`
     * alternative is the same string.
     */
    #[Test]
    public function creditPointsIconIsInlinedInBothMarkups(): void
    {
        $icon = $this->getFrontendIcon(self::CREDIT_POINTS_ICON);

        $this->assertStringStartsWith('<svg', $icon->getMarkup());
        $this->assertSame($icon->getMarkup(), $icon->getAlternativeMarkup(AbstractSvgIconProvider::MARKUP_IDENTIFIER_INLINE));
    }

    #[Test]
    public function creditPointsIconMarkupFollowsTheTextColour(): void
    {
        $this->assertFrontendIconMarkupFollowsTheTextColour(self::CREDIT_POINTS_ICON);
    }

    #[Test]
    public function renderedCreditPointsIconCarriesItsIdentifier(): void
    {
        $this->assertRenderedFrontendIconCarriesItsIdentifier(self::CREDIT_POINTS_ICON);
    }

    /**
     * Asked through the icon API of the backend, the way `core:icon` asks, the identifier
     * is unknown and the answer is TYPO3's placeholder.
     */
    #[Test]
    public function creditPointsIconIsNoBackendIcon(): void
    {
        $this->assertFrontendIconIsNotABackendIcon(self::CREDIT_POINTS_ICON);
        $this->assertSame(
            'default-not-found',
            $this->get(IconFactory::class)->getIcon(self::CREDIT_POINTS_ICON, IconSize::SMALL)->getIdentifier(),
        );
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function backendIconIdentifiers(): \Generator
    {
        yield 'page type' => ['tx-academicprograms-doktype-program'];
        yield 'content elements' => ['tx-academicprograms-plugin-programs'];
    }

    #[Test]
    #[DataProvider('backendIconIdentifiers')]
    public function pageTypeAndContentElementIconsAreNoFrontendIcons(string $identifier): void
    {
        $this->assertTrue($this->get(IconRegistry::class)->isRegistered($identifier));
        $this->assertFalse($this->get(FrontendIconRegistry::class)->isRegistered($identifier));
    }
}
