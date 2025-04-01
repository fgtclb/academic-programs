<?php

use FGTCLB\AcademicPrograms\Enumeration\PageTypes;
use TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

(static function (): void {
    GeneralUtility::makeInstance(PageDoktypeRegistry::class)
        ->add(PageTypes::TYPE_ACADEMIC_PROGRAM, [
            'allowedTables' => '*',
        ]);
})();
