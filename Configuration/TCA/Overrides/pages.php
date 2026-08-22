<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\TcaManipulator;
use FGTCLB\AcademicPrograms\Enumeration\PageTypes;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

defined('TYPO3') or die;

(static function (): void {
    // Add academic option group to doktype select
    ExtensionManagementUtility::addTcaSelectItemGroup(
        'pages',
        'doktype',
        'academic',
        'LLL:EXT:academic_programs/Resources/Private/Language/locallang_be.xlf:pages.doktype.item_group.academic',
        'after:default'
    );

    // Add academic programs doktype to doktype select
    ExtensionManagementUtility::addTcaSelectItem(
        'pages',
        'doktype',
        [
            'label' => 'LLL:EXT:academic_programs/Resources/Private/Language/locallang_be.xlf:pages.doktype.item.academic_program',
            'value' => PageTypes::TYPE_ACADEMIC_PROGRAM,
            'icon' => 'academic-programs',
            'group' => 'academic',
        ]
    );

    // Add type and typeicon
    ArrayUtility::mergeRecursiveWithOverrule(
        $GLOBALS['TCA']['pages'],
        [
            'ctrl' => [
                'typeicon_classes' => [
                    PageTypes::TYPE_ACADEMIC_PROGRAM => 'academic-programs',
                ],
            ],
            'types' => [
                PageTypes::TYPE_ACADEMIC_PROGRAM => [
                    'showitem' => $GLOBALS['TCA']['pages']['types'][PageRepository::DOKTYPE_DEFAULT]['showitem'],
                ],
            ],
        ]
    );

    // Define academic programs specific columns
    $additionalTCAcolumns = [
        'credit_points' => [
            'label' => 'LLL:EXT:academic_programs/Resources/Private/Language/locallang_be.xlf:pages.credit_points',
            'config' => [
                'type' => 'number',
                'eval' => 'trim',
            ],
        ],
        'job_profile' => [
            'label' => 'LLL:EXT:academic_programs/Resources/Private/Language/locallang_be.xlf:pages.job_profile',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
            ],
        ],
        'performance_scope' => [
            'label' => 'LLL:EXT:academic_programs/Resources/Private/Language/locallang_be.xlf:pages.performance_scope',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
            ],
        ],
        'prerequisites' => [
            'label' => 'LLL:EXT:academic_programs/Resources/Private/Language/locallang_be.xlf:pages.prerequisites',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
            ],
        ],
    ];

    ExtensionManagementUtility::addTCAcolumns(
        'pages',
        $additionalTCAcolumns
    );

    $GLOBALS['TCA'] = GeneralUtility::makeInstance(TcaManipulator::class)->addToPageTypesGeneralTab(
        $GLOBALS['TCA'],
        implode(',', [
            '--div--;LLL:EXT:academic_programs/Resources/Private/Language/locallang_be.xlf:pages.div.program',
            'credit_points',
            'job_profile',
            'performance_scope',
            'prerequisites',
        ]),
        [PageTypes::TYPE_ACADEMIC_PROGRAM]
    );

    //==================================================================================================================
    // Page TSconfig, selectable in the page field "Page TSconfig" for installations that do not use site sets.
    //
    // The files are the same ones the sets of this extension deliver. Use one mechanism per site, not both. On TYPO3
    // v12 this is the only mechanism there is - site sets arrived in TYPO3 v13.1.
    //
    // The page type registered above and its backend layout are deliberately NOT part of this: they are stored on page
    // records, so they have to resolve on every installation. The page type is registered in TCA and the backend layout
    // is imported by the always included "Configuration/page.tsconfig".
    //==================================================================================================================
    ExtensionManagementUtility::registerPageTSConfigFile(
        'academic_programs',
        'Configuration/TSconfig/ProgramList/page.tsconfig',
        'Academic Programs: Program List',
    );

    ExtensionManagementUtility::registerPageTSConfigFile(
        'academic_programs',
        'Configuration/TSconfig/ProgramDetails/page.tsconfig',
        'Academic Programs: Program Details',
    );

    ExtensionManagementUtility::registerPageTSConfigFile(
        'academic_programs',
        'Configuration/TSconfig/Full/page.tsconfig',
        'Academic Programs: All components',
    );
})();
