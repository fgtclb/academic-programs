<?php

declare(strict_types=1);

(static function (): void {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'EducationalCourse',
        'CourseList',
        'Educational Course'
    );
})();
