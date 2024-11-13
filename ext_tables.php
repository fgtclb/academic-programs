<?php

(static function (): void {
    $studyProgrammeDokType = \FGTCLB\AcademicPrograms\Enumeration\PageTypes::TYPE_ACADEMIC_PROGRAM;

    $GLOBALS['PAGES_TYPES'][$studyProgrammeDokType] = [
        'type' => 'web',
        'allowedTables' => '*',
    ];
})();
