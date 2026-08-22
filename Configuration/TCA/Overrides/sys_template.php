<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die;

(static function (): void {

    //==================================================================================================================
    // Static TypoScript templates, selectable in a "sys_template" record for installations that do not use site sets.
    //
    // The registered folders are the same ones the sets of this extension deliver through their "typoscript" key.
    // Use one mechanism per site, not both - see the extension documentation, chapter "Configuration".
    //==================================================================================================================
    ExtensionManagementUtility::addStaticFile(
        'academic_programs',
        'Configuration/TypoScript/ProgramList',
        'Academic Programs: Program List',
    );

    ExtensionManagementUtility::addStaticFile(
        'academic_programs',
        'Configuration/TypoScript/ProgramDetails',
        'Academic Programs: Program Details',
    );

    ExtensionManagementUtility::addStaticFile(
        'academic_programs',
        'Configuration/TypoScript/ContentLoad',
        'Academic Programs: Content load override',
    );

    ExtensionManagementUtility::addStaticFile(
        'academic_programs',
        'Configuration/TypoScript/Full',
        'Academic Programs: All components',
    );

    //==================================================================================================================
    // The entry below keeps the value that installations already store in "sys_template.include_static_file".
    //
    // It is the shared "plugin.tx_academicprograms" block every component folder includes, plus the page object of the
    // page type this extension registers. Selecting it is equivalent to what the single entry of this extension
    // delivered before the configuration was cut per component, minus the "styles.content" override above - but it
    // does not make any content element selectable, which the page TSconfig does.
    //==================================================================================================================
    ExtensionManagementUtility::addStaticFile(
        'academic_programs',
        'Configuration/TypoScript/',
        'Academic Programs: Shared plugin settings and page rendering',
    );

})();
