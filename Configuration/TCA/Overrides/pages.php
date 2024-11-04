<?php

declare(strict_types=1);

(static function (): void {
    // Add doktype to select
    $studyProgrammeDokType = \FGTCLB\AcademicPrograms\Enumeration\PageTypes::TYPE_EDUCATIONAL_COURSE;

    // Create new option group
    // TODO: Harmonize with otheer academical extensions
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
            'actions-graduation-cap',
            'study',
        ]
    );

    // Add type and typeicon
    \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
        $GLOBALS['TCA']['pages'],
        [
            'ctrl' => [
                'typeicon_classes' => [
                    $studyProgrammeDokType => 'actions-graduation-cap',
                ],
            ],
            'types' => [
                $studyProgrammeDokType => [
                    'showitem' => $GLOBALS['TCA']['pages']['types'][\TYPO3\CMS\Core\Domain\Repository\PageRepository::DOKTYPE_DEFAULT]['showitem'],
                ],
            ],
        ]
    );

    // Adds study programme fields
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
        'shortcut_overwrite' => [
            'label' => 'LLL:EXT:educational_course/Resources/Private/Language/locallang.xlf:pages.shortcut_overwrite',
            'config' => [
                'type' => 'check',
            ],
            'exclude' => 1,
            'default' => 0,
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
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'pages',
        'shortcut_overwrite',
        '4',
        'after:title'
    );
})();
