<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die;

(static function (): void {
    $ll = static fn (string $key): string => sprintf('LLL:EXT:academic_programs/Resources/Private/Language/locallang_be.xlf:%s', $key);

    $pluginSignature = ExtensionUtility::registerPlugin(
        'AcademicPrograms',
        'ProgramList',
        $ll('plugin.program_list.title'),
        'EXT:academic_programs/Resources/Public/Icons/Extension.svg',
        'plugins',
        $ll('plugin.program_list.decription'),
    );

    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        '--div--;' . $ll('plugin.program_list.configuration') . ',pi_flexform,',
        $pluginSignature,
        'after:subheader',
    );

    ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        'FILE:EXT:academic_programs/Configuration/FlexForms/ProgramListSettings.xml',
        $pluginSignature,
    );

    $pluginSignature = ExtensionUtility::registerPlugin(
        'AcademicPrograms',
        'ProgramDetails',
        $ll('plugin.program_details.title'),
        'EXT:academic_programs/Resources/Public/Icons/Extension.svg',
        'plugins',
        $ll('plugin.program_details.decription'),
    );
})();
