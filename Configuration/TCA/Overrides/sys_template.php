<?php

declare(strict_types=1);

(static function (): void {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        'educational_course',
        'Configuration/TypoScript/',
        'Educational Course page Setup'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        'educational_course',
        'Configuration/TypoScript/Example/',
        'Educational Course Example Page (Add before Page Setup)'
    );
})();
