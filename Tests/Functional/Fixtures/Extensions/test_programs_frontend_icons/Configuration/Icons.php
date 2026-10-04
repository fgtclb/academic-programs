<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

// A replacement of the credit points icon left in the file of the backend registry,
// which the frontend does not read.
return [
    'tx-academicprograms-info-credit-points' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:test_programs_frontend_icons/Resources/Public/Icons/SiteBackend.svg',
    ],
];
