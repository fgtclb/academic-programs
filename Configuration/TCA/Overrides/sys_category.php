<?php

declare(strict_types=1);

(static function (): void {
    $llBackendType = function (string $label) {
        return sprintf('LLL:EXT:academic_programs/Resources/Private/Language/locallang.xlf:sys_category.type.%s', $label);
    };

    $iconType = function (string $iconType) {
        return sprintf(
            'academic-programs-%s',
            $iconType
        );
    };

    // create new group
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItemGroup(
        'sys_category',
        'type',
        'programs',
        'LLL:EXT:academic_programs/Resources/Private/Language/locallang.xlf:sys_category.programs',
    );

    $typeIconClasses = [];
    $constants = \FGTCLB\AcademicPrograms\Enumeration\CategoryTypes::getConstants();
    foreach ($constants as $constant) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
            'sys_category',
            'type',
            [
                $llBackendType($constant),
                $constant,
                $iconType($constant),
                'programs',
            ]
        );

        $typeIconClasses[$constant] = $iconType($constant);
    }

    \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
        $GLOBALS['TCA']['sys_category'],
        [
            'ctrl' => [
                'typeicon_classes' => $typeIconClasses,
            ],
        ]
    );

    $GLOBALS['TCA']['sys_category']['columns']['tx_migrations_version'] = [
        'config' => [
            'type' => 'passthrough',
        ],
    ];
})();
