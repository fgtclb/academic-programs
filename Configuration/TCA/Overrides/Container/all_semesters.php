<?php

declare(strict_types=1);

(static function (): void {
    \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\B13\Container\Tca\Registry::class)->configureContainer(
        (
        new \B13\Container\Tca\ContainerConfiguration(
            'all_semesters',
            'LLL:EXT:educational_course/Resources/Private/Language/locallang_be.xlf:container.all_semesters.label',
            'LLL:EXT:educational_course/Resources/Private/Language/locallang_be.xlf:container.all_semesters.description',
            [
                [
                    [
                        'name' => 'LLL:EXT:educational_course/Resources/Private/Language/locallang_be.xlf:container.all_semesters.column.title',
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
            ->setBackendTemplate('EXT:educational_course/Resources/Private/Backend/Templates/Container.html')
    );
})();
