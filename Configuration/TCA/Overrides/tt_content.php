<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\TcaManipulator;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die;

(static function (): void {
    (new TcaManipulator())->addContentElementPlugin(
        [
            'label' => 'LLL:EXT:academic_programs/Resources/Private/Language/locallang_be.xlf:plugin.program_list.title',
            'value' => 'academicprograms_programlist',
            'icon' => 'EXT:academic_programs/Resources/Public/Icons/Extension.svg',
            'group' => 'academic',
        ],
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

    (new TcaManipulator())->addContentElementPluginFlexForm(
        'academicprograms_programlist',
        'FILE:EXT:academic_programs/Configuration/FlexForms/ProgramListSettings.xml',
    );

    (new TcaManipulator())->addContentElementPlugin(
        [
            'label' => 'LLL:EXT:academic_programs/Resources/Private/Language/locallang_be.xlf:plugin.program_details.title',
            'value' => 'academicprograms_programdetails',
            'icon' => 'EXT:academic_programs/Resources/Public/Icons/Extension.svg',
            'group' => 'academic',
        ],
        'academic_programs'
    );
})();
