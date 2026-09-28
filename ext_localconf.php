<?php

declare(strict_types=1);

use FGTCLB\AcademicPrograms\Backend\FormDataProvider\CategoryTreeRoot;
use FGTCLB\AcademicPrograms\Controller\DetailsController;
use FGTCLB\AcademicPrograms\Controller\ProgramController;
use TYPO3\CMS\Backend\Form\FormDataProvider\SiteResolving;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaCategory;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsOverrides;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die;

(static function (): void {
    ExtensionUtility::configurePlugin(
        'AcademicPrograms',
        'ProgramList',
        [
            ProgramController::class => 'list',
        ],
        [
            ProgramController::class => 'list',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
    );

    ExtensionUtility::configurePlugin(
        'AcademicPrograms',
        'ProgramDetails',
        [
            DetailsController::class => 'show',
        ],
        [],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
    );

    // Not cacheable for the same reason as the list: the options depend on program pages
    // elsewhere in the tree, and no cache tag ties the page of the finder to them.
    ExtensionUtility::configurePlugin(
        'AcademicPrograms',
        'ProgramFinder',
        [
            ProgramController::class => 'finder',
        ],
        [
            ProgramController::class => 'finder',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
    );

    // The list actions are not cacheable, so the cached page around them never depends on
    // the demand. Kept out of the cache hash, every filter URL of a list shares that one page
    // cache entry, rather than the redirect of a filter submission signing one entry per
    // combination of categories and sorting that anybody cares to submit.
    $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = '^tx_academicprograms_programlist[demand]';

    // The category trees of program pages, of the list and of the finder: the record form,
    // a FlexForm field of it, and the tree the form loads afterwards. The marker of a program
    // page arrives with the columns overrides of its page type, which "flexFormSegment" does
    // not run, and a dependency that is not part of a group is ignored there.
    foreach (['tcaDatabaseRecord', 'flexFormSegment', 'tcaSelectTreeAjaxFieldData'] as $formDataGroup) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup'][$formDataGroup][CategoryTreeRoot::class] = [
            'depends' => [
                SiteResolving::class,
                TcaColumnsOverrides::class,
            ],
            'before' => [
                TcaCategory::class,
            ],
        ];
    }
})();
