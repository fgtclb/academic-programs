<?php

(static function (): void {
    $academicProgramDoktype = \FGTCLB\AcademicPrograms\Enumeration\PageTypes::TYPE_ACADEMIC_PROGRAM;
    $versionInformation = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Information\Typo3Version::class);

    if ($versionInformation->getMajorVersion() < 12) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig(
            sprintf(
                'options.pageTree.doktypesToShowInNewPageDragArea := addToList(%d)',
                $academicProgramDoktype
            )
        );

        // Starting with TYPO3 v12.0 Configuration/page.tsconfig in an Extension is automatically loaded during build time
        // @see https://docs.typo3.org/m/typo3/reference-tsconfig/12.4/en-us/UsingSetting/PageTSconfig.html#pagesettingdefaultpagetsconfig
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
            @import \'EXT:academic_programs/Configuration/page.tsconfig\'
        ');
    }

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'AcademicPrograms',
        'ProgramList',
        [
            \FGTCLB\AcademicPrograms\Controller\ProgramController::class => 'list',
        ],
        [
            \FGTCLB\AcademicPrograms\Controller\ProgramController::class => 'list',
        ]
    );
})();
