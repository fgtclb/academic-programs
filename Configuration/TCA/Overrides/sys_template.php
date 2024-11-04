<?php

declare(strict_types=1);

(static function (): void {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        'academic_programs',
        'Configuration/TypoScript/',
        'Educational Program page Setup'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        'academic_programs',
        'Configuration/TypoScript/Example/',
        'Educational Program Example Page (Add before Page Setup)'
    );
})();
