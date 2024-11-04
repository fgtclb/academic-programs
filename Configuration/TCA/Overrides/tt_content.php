<?php

declare(strict_types=1);

(static function (): void {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'AcademicPrograms',
        'CourseList',
        'Educational Course',
        'academic-programs-studiesIcon'
    );

    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['AcademicPrograms_courselist'] = 'layout,recursive';
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['AcademicPrograms_courselist'] = 'pi_flexform';

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
        'AcademicPrograms_courselist',
        'FILE:EXT:educational_course/Configuration/FlexForms/CourseSettings.xml'
    );
})();
