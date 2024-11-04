<?php

declare(strict_types=1);

(static function (): void {
    \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\B13\Container\Tca\Registry::class)->configureContainer(
        (
        new \B13\Container\Tca\ContainerConfiguration(
            'all_semesters',
            'LLL:EXT:academic_programs/Resources/Private/Language/locallang_be.xlf:container.all_semesters.label',
            'LLL:EXT:academic_programs/Resources/Private/Language/locallang_be.xlf:container.all_semesters.description',
            [
                [
                    [
                        'name' => 'LLL:EXT:academic_programs/Resources/Private/Language/locallang_be.xlf:container.all_semesters.column.title',
                        'colPos' => 900,
                        'maxitems' => 14,
                        'allowed' => [
                            'CType' => 'semester',
                        ],
                    ],
                ],
            ]
        ))
            ->setIcon('content-container-columns-4')
            ->setSaveAndCloseInNewContentElementWizard(false)
            ->setBackendTemplate('EXT:academic_programs/Resources/Private/Backend/Templates/Container.html')
    );
})();
