<?php

(static function (): void {
    $studyProgrammeDokType = \FGTCLB\EducationalCourse\Enumeration\PageTypes::TYPE_EDUCATIONAL_COURSE;

    $GLOBALS['PAGES_TYPES'][$studyProgrammeDokType] = [
        'type' => 'web',
        'allowedTables' => '*',
    ];
})();
