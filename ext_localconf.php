<?php

declare(strict_types=1);

use FGTCLB\AcademicPrograms\Controller\DetailsController;
use FGTCLB\AcademicPrograms\Controller\ProgramController;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die;

(static function (): void {
    $versionInformation = new Typo3Version();

    // Starting with TYPO3 v13.0 Configuration/user.tsconfig in an Extension is automatically loaded during build time
    // @see https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/13.0/Deprecation-101807-ExtensionManagementUtilityaddUserTSConfig.html
    if ($versionInformation->getMajorVersion() < 13) {
        // Allow backend users to drag and drop the new page type:
        ExtensionManagementUtility::addUserTSConfig('
            @import \'EXT:academic_programs/Configuration/user.tsconfig\'
        ');
    }

    ExtensionUtility::configurePlugin(
        'AcademicPrograms',
        'ProgramList',
        [
            ProgramController::class => 'list',
        ],
        [
            ProgramController::class => 'list',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
    );

    ExtensionUtility::configurePlugin(
        'AcademicPrograms',
        'ProgramDetails',
        [
            DetailsController::class => 'show',
        ],
        [],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
    );
})();
