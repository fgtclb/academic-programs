<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

/*
 * The frontend icons of this extension, registered in the frontend icon registry of
 * EXT:academic_base and rendered with its `ab:icon` ViewHelper. The backend never shows
 * them, so they are not in `Configuration/Icons.php`. A site package that depends on this
 * extension replaces one by registering its identifier in its own
 * `Configuration/FrontendIcons.php`.
 *
 * The category type icons of the facts are not registered here: EXT:category_types
 * registers them in both registries from `Configuration/CategoryTypes.yaml`.
 */
return [
    /*
     * The icon of the credit points fact of a program, which is a program field rather than
     * a category type and therefore has no icon from Configuration/CategoryTypes.yaml. Font
     * Awesome Free solid, named `tx-<extkey>-<group>-<name>` with the file at
     * `Icons/<group>/<name>.svg`. Licence and origin:
     * Resources/Public/Icons/LICENSE-font-awesome.txt. Drawn in `currentColor` and inlined
     * by the provider, so it takes the text colour of the facts it stands in.
     */
    'tx-academicprograms-info-credit-points' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_programs/Resources/Public/Icons/info/credit-points.svg',
    ],
];
