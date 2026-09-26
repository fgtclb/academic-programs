<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Backend\FormEngine;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\CropVariantsAssertionTrait;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The crop variants an editor gets in the image cropper for the media of a program page,
 * and for the media of a standard page, which the program page type must leave alone.
 *
 * The media of a program page stores a crop made with the free crop TYPO3 offers when a
 * field configures no variant, under the name `default`; the cropper has to show and keep
 * it as it was.
 */
final class PageMediaCropVariantsTest extends AbstractAcademicProgramsTestCase
{
    use CropVariantsAssertionTrait;

    private const FIXTURES = __DIR__ . '/Fixtures/PageMediaCropVariants/';

    protected function setUp(): void
    {
        parent::setUp();
        $folder = $this->instancePath . '/fileadmin/images';
        GeneralUtility::mkdir_deep($folder);
        copy(self::FIXTURES . 'landscape.jpg', $folder . '/landscape.jpg');
        $this->importCSVDataSet(self::FIXTURES . 'records.csv');
        $this->setUpBackendUser(1);
    }

    #[Test]
    public function theMediaOfAProgramPageOffersDefaultLandscapeAndPortrait(): void
    {
        $variants = $this->offeredCropVariants('pages', 10, 'media');

        $this->assertSame(['default', 'landscape', 'portrait'], array_keys($variants));
        $this->assertSame(['16:9' => 16 / 9], $this->aspectRatiosOf($variants['landscape']));
        $this->assertSame('16:9', $variants['landscape']['selectedRatio']);
        $this->assertSame(['3:4' => 0.75], $this->aspectRatiosOf($variants['portrait']));
        $this->assertSame('3:4', $variants['portrait']['selectedRatio']);
    }

    #[Test]
    public function theDefaultVariantIsTheOneTypo3OffersWithoutConfiguration(): void
    {
        $variants = $this->offeredCropVariants('pages', 10, 'media');
        $coreVariants = $this->cropVariantsWithoutConfiguration('pages', 10, 'media');

        $this->assertSame(['default'], array_keys($coreVariants));
        $this->assertSame($coreVariants['default'], $variants['default']);
    }

    /**
     * The fixture stores a crop area in no ratio the cropper offers. A `default` variant
     * with a fixed ratio would fit it into that ratio as soon as the editor opens the image.
     */
    #[Test]
    public function aCropStoredBeforeTheChangeKeepsItsArea(): void
    {
        $variants = $this->offeredCropVariants('pages', 10, 'media');

        $this->assertEquals(['x' => 0.1, 'y' => 0.2, 'width' => 0.5, 'height' => 0.3], $variants['default']['cropArea']);
        $this->assertSame('NaN', $variants['default']['selectedRatio']);
    }

    /**
     * The cropper hands a stored crop to the variant of its name. A variant without one
     * takes the stored crops in their stored order, starting with the first, even when
     * another variant took that one by name: the first new variant starts from the crop
     * stored for `default`, fitted into its ratio, the second one, with none left, from
     * the whole image, fitted and centred. The fixture image is 800 x 600 pixels.
     */
    #[Test]
    public function theNewVariantsStartFromTheStoredCropAndTheWholeImage(): void
    {
        $variants = $this->offeredCropVariants('pages', 10, 'media');

        $this->assertEqualsWithDelta(['x' => 0.15, 'y' => 0.2, 'width' => 0.4, 'height' => 0.3], $variants['landscape']['cropArea'], 0.0001);
        $this->assertEqualsWithDelta(['x' => 0.21875, 'y' => 0.0, 'width' => 0.5625, 'height' => 1.0], $variants['portrait']['cropArea'], 0.0001);
    }

    #[Test]
    public function theMediaOfAStandardPageKeepsWhatTypo3Offers(): void
    {
        $this->assertSame(
            $this->cropVariantsWithoutConfiguration('pages', 1, 'media'),
            $this->offeredCropVariants('pages', 1, 'media'),
        );
    }

    /**
     * The program page type configures its variants in `columnsOverrides`, which FormEngine
     * merges into the media field of the standard page. On TYPO3 v13 that field carries the
     * palettes of the file references in `overrideChildTca`, for backwards compatibility; a
     * page type that replaced `overrideChildTca` instead of adding to it would drop them.
     * Everything but the crop variants therefore has to be the configuration of a standard
     * page.
     */
    #[Test]
    public function theProgramPageTypeAddsNothingButTheCropVariantsToTheMediaField(): void
    {
        $standardPage = $this->compileRecordForm('pages', 1)['processedTca']['columns']['media']['config'] ?? null;
        $programPage = $this->compileRecordForm('pages', 10)['processedTca']['columns']['media']['config'] ?? null;
        $this->assertIsArray($standardPage);
        $this->assertIsArray($programPage);
        $this->assertIsArray($programPage['overrideChildTca']['columns']['crop']['config']['cropVariants'] ?? null);

        unset($programPage['overrideChildTca']['columns']['crop']);
        if ($programPage['overrideChildTca']['columns'] === []) {
            unset($programPage['overrideChildTca']['columns']);
        }
        if ($programPage['overrideChildTca'] === []) {
            unset($programPage['overrideChildTca']);
        }
        $this->assertSame($standardPage, $programPage);
    }
}
