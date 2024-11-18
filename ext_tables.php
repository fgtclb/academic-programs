<?php

use FGTCLB\AcademicPrograms\Enumeration\PageTypes;

(static function (): void {
    $studyProgrammeDokType = PageTypes::TYPE_ACADEMIC_PROGRAM;

    $GLOBALS['PAGES_TYPES'][$studyProgrammeDokType] = [
        'type' => 'web',
        'allowedTables' => '*',
    ];
})();
