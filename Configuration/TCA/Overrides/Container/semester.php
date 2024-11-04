<?php

declare(strict_types=1);

(static function (): void {
    \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\B13\Container\Tca\Registry::class)->configureContainer(
        (
        new \B13\Container\Tca\ContainerConfiguration(
            'semester',
            'LLL:EXT:academic_programs/Resources/Private/Language/locallang_be.xlf:container.semester.label',
            'LLL:EXT:academic_programs/Resources/Private/Language/locallang_be.xlf:container.semester.description',
            [
                [
                    [
                        'name' => 'LLL:EXT:academic_programs/Resources/Private/Language/locallang_be.xlf:container.semester.column.title',
                        'colPos' => 950,
                        'disallowed' => [
                            'CType' => 'all_semesters,semester',
                        ],
                    ],
                ],
            ]
        ))
            ->setIcon('content-container-columns-4')
            ->setSaveAndCloseInNewContentElementWizard(false)
            ->setBackendTemplate('EXT:academic_programs/Resources/Private/Backend/Templates/Container.html')
    );
    $GLOBALS['TCA']['tt_content']['types']['semester']['columnsOverrides'] ??= [];
    $GLOBALS['TCA']['tt_content']['types']['semester']['columnsOverrides'] = [
        'header' => [
            'config' => [
                'max' => 10,
                'eval' => 'required',
            ],
        ],
    ];
})();
