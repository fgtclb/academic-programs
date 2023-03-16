<?php

declare(strict_types=1);

(static function (): void {
    // first add doktype to select
    $studyProgrammeDokType = \FGTCLB\EducationalCourse\Domain\Enumeration\Page::TYPE_EDUCATIONAL_COURSE;

    // create new group
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItemGroup(
        'pages',
        'doktype',
        'study',
        'LLL:EXT:educational_course/Resources/Private/Language/locallang.xlf:pages.study',
        'after:default'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
        'pages',
        'doktype',
        [
            'LLL:EXT:educational_course/Resources/Private/Language/locallang.xlf:pages.educational_course',
            $studyProgrammeDokType,
            'educational-course-degree',
            'study',
        ]
    );
    // add type and typeicon
    \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
        $GLOBALS['TCA']['pages'],
        [
            'ctrl' => [
                'typeicon_classes' => [
                    $studyProgrammeDokType => 'educational-course-degree',
                ],
            ],
            'types' => [
                $studyProgrammeDokType => [
                    'showitem' => $GLOBALS['TCA']['pages']['types'][\TYPO3\CMS\Core\Domain\Repository\PageRepository::DOKTYPE_DEFAULT]['showitem'],
                ],
            ],
        ]
    );

    // adds study programme fields
    $newTcaFields = [
        'job_profile' => [
            'label' => 'LLL:EXT:educational_course/Resources/Private/Language/locallang.xlf:pages.job_profile',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
            ],
        ],
        'performance_scope' => [
            'label' => 'LLL:EXT:educational_course/Resources/Private/Language/locallang.xlf:pages.performance_scope',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
            ],
        ],
        'prerequisites' => [
            'label' => 'LLL:EXT:educational_course/Resources/Private/Language/locallang.xlf:pages.prerequisites',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
            ],
        ],
    ];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
        'pages',
        $newTcaFields
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'pages',
        '--div--;LLL:EXT:educational_course/Resources/Private/Language/locallang.xlf:pages.div.course_information,job_profile,performance_scope,prerequisites',
        '20',
        'after:rowDescription'
    );
})();
