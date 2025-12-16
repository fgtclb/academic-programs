<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die;

(static function (): void {
    $typo3MajorVersion = (new Typo3Version())->getMajorVersion();

    ExtensionManagementUtility::addPlugin(
        [
            'label' => 'LLL:EXT:academic_programs/Resources/Private/Language/locallang_be.xlf:plugin.program_list.title',
            'value' => 'academicprograms_programlist',
            'icon' => 'EXT:academic_programs/Resources/Public/Icons/Extension.svg',
            'group' => 'academic',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
        'academic_programs'
    );

    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        implode(',', [
            '--div--;LLL:EXT:academic_programs/Resources/Private/Language/locallang_be.xlf:element.tab.configuration',
            'pi_flexform',
            'pages',
            'recursive',
        ]),
        'academicprograms_programlist',
        'after:subheader',
    );

    ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        sprintf('FILE:EXT:academic_programs/Configuration/FlexForms/Core%s/ProgramListSettings.xml', $typo3MajorVersion),
        'academicprograms_programlist',
    );

    ExtensionManagementUtility::addPlugin(
        [
            'label' => 'LLL:EXT:academic_programs/Resources/Private/Language/locallang_be.xlf:plugin.program_details.title',
            'value' => 'academicprograms_programdetails',
            'icon' => 'EXT:academic_programs/Resources/Public/Icons/Extension.svg',
            'group' => 'academic',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
        'academic_programs'
    );
})();
