<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

/*
 * The backend icons of this extension: the academic program page type and the program
 * content elements (list, details and finder). Font Awesome Free solid, drawn in
 * `currentColor` and inlined by the provider of EXT:academic_base, so they follow the
 * text colour in both backend colour schemes instead of keeping the ink of an <img>.
 * Licence and origin of every file: Resources/Public/Icons/LICENSE-font-awesome.txt.
 *
 * Identifiers follow `tx-<extension key without underscores>-<group>-<name>`, files
 * `Icons/<group>/<name>.svg`: `doktype` for the page type, `plugin` for the content
 * elements. Both share one drawing under two identifiers, so a project can replace either
 * of them on its own in the Configuration/Icons.php of a package that depends on this one.
 *
 * The category type and group icons are registered by EXT:category_types from
 * Configuration/CategoryTypes.yaml, the icon of the credit points fact is a frontend icon
 * and registered in Configuration/FrontendIcons.php.
 */
return [
    'tx-academicprograms-doktype-program' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_programs/Resources/Public/Icons/plugin/programs.svg',
    ],
    'tx-academicprograms-plugin-programs' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_programs/Resources/Public/Icons/plugin/programs.svg',
    ],
];
