<?php

(static function (): void {
    $studyProgrammeDokType = \FGTCLB\EducationalCourse\Domain\Enumeration\Page::TYPE_EDUCATIONAL_COURSE;

    $GLOBALS['PAGES_TYPES'][$studyProgrammeDokType] = [
        'type' => 'web',
        'allowedTables' => '*',
    ];
})();
