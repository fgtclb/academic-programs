<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

/*
 * `academic-programs` is the icon of the academic program page type. It is registered
 * with the provider of EXT:academic_base, which inlines the file in both markups
 * instead of rendering an <img>. An <img> is opaque to CSS and keeps the colours of
 * its file, so an icon drawn in a dark ink stays dark on the dark cards of the
 * backend colour scheme. Inlined and drawn in `currentColor` it follows the text
 * colour.
 */
return [
    'academic-programs' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_programs/Resources/Public/Icons/Extension.svg',
    ],
    /*
     * The icon of the credit points fact of a program, which is a program field rather than
     * a category type and therefore has no icon from Configuration/CategoryTypes.yaml. Font
     * Awesome Free solid, named `tx-<extkey>-<group>-<name>` with the file at
     * `Icons/<group>/<name>.svg`. Licence and origin:
     * Resources/Public/Icons/LICENSE-font-awesome.txt.
     */
    'tx-academicprograms-info-credit-points' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_programs/Resources/Public/Icons/info/credit-points.svg',
    ],
];
