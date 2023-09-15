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

    $sysCategoryTcaTypeIconOverrides = [
        'ctrl' => [
            'typeicon_classes' => [
                \FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_ADMISSION_RESTRICTION
                => $iconType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_ADMISSION_RESTRICTION),
                \FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_APPLICATION_PERIOD
                => $iconType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_APPLICATION_PERIOD),
                \FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_BEGIN_COURSE
                => $iconType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_BEGIN_COURSE),
                \FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_COSTS
                => $iconType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_COSTS),
                \FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_DEGREE
                => $iconType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_DEGREE),
                \FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_DEPARTMENT
                => $iconType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_DEPARTMENT),
                \FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_STANDARD_PERIOD
                => $iconType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_STANDARD_PERIOD),
                \FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_COURSE_TYPE
                => $iconType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_COURSE_TYPE),
                \FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_TEACHING_LANGUAGE
                => $iconType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_TEACHING_LANGUAGE),
                \FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_TOPIC
                => $iconType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_TOPIC),
            ],
        ],
    ];
    $addItems = [
        [
            $llBackendType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_ADMISSION_RESTRICTION),
            \FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_ADMISSION_RESTRICTION,
            $iconType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_ADMISSION_RESTRICTION),
            'courses',
        ],
        [
            $llBackendType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_APPLICATION_PERIOD),
            \FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_APPLICATION_PERIOD,
            $iconType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_APPLICATION_PERIOD),
            'courses',
        ],
        [
            $llBackendType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_BEGIN_COURSE),
            \FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_BEGIN_COURSE,
            $iconType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_BEGIN_COURSE),
            'courses',
        ],
        [
            $llBackendType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_COSTS),
            \FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_COSTS,
            $iconType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_COSTS),
            'courses',
        ],
        [
            $llBackendType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_DEGREE),
            \FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_DEGREE,
            $iconType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_DEGREE),
            'courses',
        ],
        [
            $llBackendType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_DEPARTMENT),
            \FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_DEPARTMENT,
            $iconType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_DEPARTMENT),
            'courses',
        ],
        [
            $llBackendType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_STANDARD_PERIOD),
            \FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_STANDARD_PERIOD,
            $iconType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_STANDARD_PERIOD),
            'courses',
        ],
        [
            $llBackendType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_COURSE_TYPE),
            \FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_COURSE_TYPE,
            $iconType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_COURSE_TYPE),
            'courses',
        ],
        [
            $llBackendType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_TEACHING_LANGUAGE),
            \FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_TEACHING_LANGUAGE,
            $iconType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_TEACHING_LANGUAGE),
            'courses',
        ],
        [
            $llBackendType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_TOPIC),
            \FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_TOPIC,
            $iconType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_TOPIC),
            'courses',
        ],
    ];

    // create new group
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItemGroup(
        'sys_category',
        'type',
        'courses',
        'LLL:EXT:educational_course/Resources/Private/Language/locallang.xlf:sys_category.courses',
    );

    foreach ($addItems as $addItem) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
            'sys_category',
            'type',
            $addItem
        );
    }

    \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
        $GLOBALS['TCA']['sys_category'],
        $sysCategoryTcaTypeIconOverrides
    );

    $GLOBALS['TCA']['sys_category']['columns']['tx_migrations_version'] = [
        'config' => [
            'type' => 'passthrough',
        ],
    ];
})();
