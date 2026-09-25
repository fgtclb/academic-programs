<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Imaging;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\ColourSchemeAwareIconsTrait;
use PHPUnit\Framework\Attributes\Test;

/**
 * The icon of the credit points fact. It is no record icon - the frontend renders it in
 * the facts of a program - but it is drawn in `currentColor` and inlined like one, so it
 * takes the text colour of the facts it stands in.
 *
 * The identifier is spelled out rather than read from the builder, so a rename has to be
 * made twice instead of silently agreeing with itself.
 */
final class FactIconsTest extends AbstractAcademicProgramsTestCase
{
    use ColourSchemeAwareIconsTrait;

    private const CREDIT_POINTS_ICON = 'tx-academicprograms-info-credit-points';

    #[Test]
    public function creditPointsIconIsRegisteredWithTheColourSchemeAwareProvider(): void
    {
        $this->assertIconIsRegisteredWithCurrentColorProvider(self::CREDIT_POINTS_ICON);
    }

    #[Test]
    public function creditPointsIconIsInlinedInBothMarkups(): void
    {
        $this->assertIconIsInlinedInBothMarkups(self::CREDIT_POINTS_ICON);
    }

    #[Test]
    public function creditPointsIconMarkupFollowsTheTextColour(): void
    {
        $this->assertIconMarkupFollowsTheTextColour(self::CREDIT_POINTS_ICON);
    }
}
