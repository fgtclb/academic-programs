<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die;

(static function (): void {
    ExtensionManagementUtility::addTcaSelectItemGroup(
        'tt_content',
        'CType',
        'academic',
        'LLL:EXT:academic_programs/Resources/Private/Language/locallang_be.xlf:content.ctype.group.label',
    );

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
        '--div--;LLL:EXT:academic_programs/Resources/Private/Language/locallang_be.xlf:plugin.program_list.configuration,pi_flexform,',
        'academicprograms_programlist',
        'after:subheader',
    );
    ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        'FILE:EXT:academic_programs/Configuration/FlexForms/ProgramListSettings.xml',
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
