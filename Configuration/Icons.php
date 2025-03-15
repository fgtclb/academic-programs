<?php

declare(strict_types=1);

use FGTCLB\AcademicPrograms\Enumeration\CategoryTypes;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$iconIdentifier = static fn(string $key): string => 'academic-programs-' . $key;

$iconList = [];
foreach (CategoryTypes::getConstants() as $type) {
    $identifier = $iconIdentifier($type);
    $iconList[$identifier] = [
        'provider' => SvgIconProvider::class,
        'source' => sprintf(
            'EXT:academic_programs/Resources/Public/Icons/CategoryTypes/%s.svg',
            GeneralUtility::underscoredToUpperCamelCase($type)
        ),
    ];
}

$iconList['academic-programs'] = [
    'provider' => SvgIconProvider::class,
    'source' => 'EXT:academic_programs/Resources/Public/Icons/Extension.svg',
];

return $iconList;
