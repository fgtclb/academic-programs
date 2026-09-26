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
                    // TYPO3 v14+ resolves the tables allowed on a page type from this
                    // TCA option. TYPO3 v13 has no such option and needs the
                    // PageDoktypeRegistry, which is fed by the RegisterAcademicPageDoktype
                    // event listener - see its docblock for why it cannot happen here.
                    'allowedRecordTypes' => ['*'],
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

    // The media of a program page offers named crop variants, which templates request by name.
    // They are set on the page type, so the media of a standard page keeps what TYPO3 offers.
    // FormEngine merges "columnsOverrides" into the field, so the "overrideChildTca" TYPO3 v13
    // still configures on it stays. "default" repeats what the cropper offers when no variant
    // is configured, and stays first, so a crop stored before the variants existed keeps its
    // meaning.
    $GLOBALS['TCA']['pages']['types'][PageTypes::TYPE_ACADEMIC_PROGRAM]['columnsOverrides']['media']['config']['overrideChildTca']['columns']['crop']['config']['cropVariants'] = [
        'default' => [
            'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.crop_variant.default',
            'allowedAspectRatios' => [
                '16:9' => [
                    'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.ratio.16_9',
                    'value' => 16 / 9,
                ],
                '3:2' => [
                    'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.ratio.3_2',
                    'value' => 3 / 2,
                ],
                '4:3' => [
                    'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.ratio.4_3',
                    'value' => 4 / 3,
                ],
                '1:1' => [
                    'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.ratio.1_1',
                    'value' => 1.0,
                ],
                'NaN' => [
                    'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.ratio.free',
                    'value' => 0.0,
                ],
            ],
            'selectedRatio' => 'NaN',
            'cropArea' => [
                'x' => 0.0,
                'y' => 0.0,
                'width' => 1.0,
                'height' => 1.0,
            ],
        ],
        'landscape' => [
            'title' => 'LLL:EXT:academic_programs/Resources/Private/Language/locallang_be.xlf:pages.media.crop_variant.landscape',
            'allowedAspectRatios' => [
                '16:9' => [
                    'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.ratio.16_9',
                    'value' => 16 / 9,
                ],
            ],
            'selectedRatio' => '16:9',
        ],
        'portrait' => [
            'title' => 'LLL:EXT:academic_programs/Resources/Private/Language/locallang_be.xlf:pages.media.crop_variant.portrait',
            'allowedAspectRatios' => [
                '3:4' => [
                    'title' => 'LLL:EXT:academic_programs/Resources/Private/Language/locallang_be.xlf:pages.media.aspect_ratio.3_4',
                    'value' => 3 / 4,
                ],
            ],
            'selectedRatio' => '3:4',
        ],
    ];

    //==================================================================================================================
    // Page TSconfig, selectable in the page field "Page TSconfig" for installations that do not use site sets.
    //
    // The files are the same ones the sets of this extension deliver. Use one mechanism per site, not both.
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
        'Configuration/TSconfig/ProgramFinder/page.tsconfig',
        'Academic Programs: Program Finder',
    );

    ExtensionManagementUtility::registerPageTSConfigFile(
        'academic_programs',
        'Configuration/TSconfig/Full/page.tsconfig',
        'Academic Programs: All components',
    );
})();
