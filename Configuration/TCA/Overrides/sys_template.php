<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

ExtensionManagementUtility::addStaticFile(
    'academic_programs',
    'Configuration/TypoScript/',
    'Academic Programs Page Setup'
);
