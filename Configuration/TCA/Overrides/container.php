<?php

declare(strict_types=1);

(static function (): void {
    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('container')) {
        require_once 'Container/all_semesters.php';
        require_once 'Container/semester.php';
    }
})();
