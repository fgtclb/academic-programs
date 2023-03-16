<?php

declare(strict_types=1);

(static function (): void {
    $llBackend = function ($label) {
        return sprintf('LLL:EXT:educational_course/Resources/Private/Language/locallang.xlf:sys_category.%s', $label);
    };
    $llBackendType = function ($label) {
        return sprintf('LLL:EXT:educational_course/Resources/Private/Language/locallang.xlf:sys_category.type.%s', $label);
    };

    $iconType = function ($iconType) {
        return sprintf(
            'educational-course-%s',
            $iconType
        );
    };

    $sysCategoryTca = [
        'ctrl' => [
            'type' => 'type',
            'typeicon_classes' => [
                \FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_APPLICATION_PERIOD
                    => $iconType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_APPLICATION_PERIOD),
                \FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_BEGIN_COURSE
                    => $iconType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_BEGIN_COURSE),
                \FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_COSTS
                    => $iconType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_COSTS),
                \FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_DEGREE
                    => $iconType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_DEGREE),
                \FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_STANDARD_PERIOD
                    => $iconType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_STANDARD_PERIOD),
                \FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_COURSE_TYPE
                    => $iconType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_COURSE_TYPE),
                \FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_TEACHING_LANGUAGE
                    => $iconType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_TEACHING_LANGUAGE),
            ],
            'typeicon_column' => 'type',
        ],
        'columns' => [
            'type' => [
                'label' => $llBackend('type'),
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [
                        [
                            $llBackendType('default'),
                            '',
                            'mimetypes-x-sys_category',
                        ],
                        [
                            $llBackendType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_APPLICATION_PERIOD),
                            \FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_APPLICATION_PERIOD,
                            $iconType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_APPLICATION_PERIOD),
                        ],
                        [
                            $llBackendType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_BEGIN_COURSE),
                            \FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_BEGIN_COURSE,
                            $iconType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_BEGIN_COURSE),
                        ],
                        [
                            $llBackendType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_COSTS),
                            \FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_COSTS,
                            $iconType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_COSTS),
                        ],
                        [
                            $llBackendType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_DEGREE),
                            \FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_DEGREE,
                            $iconType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_DEGREE),
                        ],
                        [
                            $llBackendType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_STANDARD_PERIOD),
                            \FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_STANDARD_PERIOD,
                            $iconType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_STANDARD_PERIOD),
                        ],
                        [
                            $llBackendType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_COURSE_TYPE),
                            \FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_COURSE_TYPE,
                            $iconType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_COURSE_TYPE),
                        ],
                        [
                            $llBackendType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_TEACHING_LANGUAGE),
                            \FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_TEACHING_LANGUAGE,
                            $iconType(\FGTCLB\EducationalCourse\Domain\Enumeration\Category::TYPE_TEACHING_LANGUAGE),
                        ],
                    ],
                ],
            ],
        ],
    ];
    $GLOBALS['TCA']['sys_category'] = array_merge_recursive($GLOBALS['TCA']['sys_category'], $sysCategoryTca);
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'sys_category',
        'type',
        '',
        'before:title'
    );
})();
