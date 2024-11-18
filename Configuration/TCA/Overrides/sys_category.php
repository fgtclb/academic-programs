<?php

declare(strict_types=1);

use FGTCLB\AcademicPrograms\Enumeration\CategoryTypes;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$ll = static fn (string $key): string => sprintf('LLL:EXT:academic_programs/Resources/Private/Language/locallang.xlf:%s', $key);
$iconIdentifier = static fn (string $key): string => 'academic-programs-' . $key;

// Add academic programs group to type select
ExtensionManagementUtility::addTcaSelectItemGroup(
    'sys_category',
    'type',
    'programs',
    $ll('sys_category.academic_programs.group'),
);

$typeIconClasses = [];
foreach (CategoryTypes::getConstants() as $type) {
    ExtensionManagementUtility::addTcaSelectItem(
        'sys_category',
        'type',
        [
            $ll('sys_category.academic_programs.' . $type),
            $type,
            $iconIdentifier($type),
            'programs',
        ]
    );
    $typeIconClasses[$type] = $iconIdentifier($type);
}

ArrayUtility::mergeRecursiveWithOverrule(
    $GLOBALS['TCA']['sys_category'],
    [
        'ctrl' => [
            'label_userFunc' => \FGTCLB\AcademicPrograms\Backend\Tca\Labels::class . '->category',
            'typeicon_classes' => $typeIconClasses,
        ],
    ]
);
