<?php

(static function (): void {
    $studyProgrammeDokType = \FGTCLB\AcademicPrograms\Enumeration\PageTypes::TYPE_EDUCATIONAL_COURSE;
    // Allow backend users to drag and drop the new page type:
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig(
        sprintf(
            'options.pageTree.doktypesToShowInNewPageDragArea := addToList(%d)',
            $studyProgrammeDokType
        )
    );

    $versionInformation = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Information\Typo3Version::class);
    // Only include page.tsconfig if TYPO3 version is below 12 so that it is not imported twice.
    if ($versionInformation->getMajorVersion() < 12) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
      @import "EXT:academic_programs/Configuration/page.tsconfig"
   ');
    }

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'AcademicPrograms',
        'CourseList',
        [
            \FGTCLB\AcademicPrograms\Controller\CourseController::class => 'list',
        ],
        [
            \FGTCLB\AcademicPrograms\Controller\CourseController::class => 'list',
        ]
    );
})();
