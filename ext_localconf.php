<?php

declare(strict_types=1);

use FGTCLB\AcademicPrograms\Controller\DetailsController;
use FGTCLB\AcademicPrograms\Controller\ProgramController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die;

(static function (): void {
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
