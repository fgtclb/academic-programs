<?php

declare(strict_types=1);

(static function (): void {
    $llBackendType = function (string $label) {
        return sprintf('LLL:EXT:educational_course/Resources/Private/Language/locallang.xlf:sys_category.type.%s', $label);
    };

    $iconType = function (string $iconType) {
        return sprintf(
            'educational-course-%s',
            $iconType
        );
    };

    // create new group
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItemGroup(
        'sys_category',
        'type',
        'courses',
        'LLL:EXT:educational_course/Resources/Private/Language/locallang.xlf:sys_category.courses',
    );

    $typeIconClasses = [];
    $constants = \FGTCLB\EducationalCourse\Domain\Enumeration\Category::getConstants();
    foreach ($constants as $constant) {
        // Set select option for Category Attribute
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
            'sys_category',
            'type',
            [
                $llBackendType($constant),
                $constant,
                $iconType($constant),
                'courses',
            ]
        );

        // Set icons for Category Attribute
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
