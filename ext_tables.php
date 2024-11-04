<?php

(static function (): void {
    $studyProgrammeDokType = \FGTCLB\AcademicPrograms\Enumeration\PageTypes::TYPE_EDUCATIONAL_COURSE;

    $GLOBALS['PAGES_TYPES'][$studyProgrammeDokType] = [
        'type' => 'web',
        'allowedTables' => '*',
    ];
})();
