<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

// The way a site package replaces the credit points icon and the icon of a shipped
// category type for the frontend.
return [
    'tx-academicprograms-info-credit-points' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:test_programs_frontend_icons/Resources/Public/Icons/SiteCreditPoints.svg',
    ],
    'category_types.programs.degree' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:test_programs_frontend_icons/Resources/Public/Icons/SiteDegree.svg',
    ],
];
