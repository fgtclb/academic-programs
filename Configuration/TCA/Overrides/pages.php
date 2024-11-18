<?php

declare(strict_types=1);

use FGTCLB\AcademicPrograms\Enumeration\PageTypes;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die;

$ll = static fn (string $key): string => sprintf('LLL:EXT:academic_programs/Resources/Private/Language/locallang.xlf:%s', $key);

// Add doktype to select
$academicProgramDoktype = PageTypes::TYPE_ACADEMIC_PROGRAM;

// Add academic option group to doktype select
ExtensionManagementUtility::addTcaSelectItemGroup(
    'pages',
    'doktype',
    'academic',
    $ll('pages.doktype.groups.academic'),
    'after:default'
);

// Add academic programs doktype to doktype select
ExtensionManagementUtility::addTcaSelectItem(
    'pages',
    'doktype',
    [
        $ll('pages.doktype.items.academic_program'),
        $academicProgramDoktype,
        'academic-programs',
        'academic',
    ]
);

// Add type and typeicon
ArrayUtility::mergeRecursiveWithOverrule(
    $GLOBALS['TCA']['pages'],
    [
        'ctrl' => [
            'typeicon_classes' => [
                $academicProgramDoktype => 'academic-programs',
            ],
        ],
        'types' => [
            $academicProgramDoktype => [
                'showitem' => $GLOBALS['TCA']['pages']['types'][PageRepository::DOKTYPE_DEFAULT]['showitem'],
            ],
        ],
    ]
);

// Define academic programs specific columns
$additionalTCAcolumns = [
    'job_profile' => [
        'label' => $ll('pages.job_profile'),
        'config' => [
            'type' => 'text',
            'enableRichtext' => true,
        ],
    ],
    'performance_scope' => [
        'label' => $ll('pages.performance_scope'),
        'config' => [
            'type' => 'text',
            'enableRichtext' => true,
        ],
    ],
    'prerequisites' => [
        'label' => $ll('pages.prerequisites'),
        'config' => [
            'type' => 'text',
            'enableRichtext' => true,
        ],
    ],
];

ExtensionManagementUtility::addTCAcolumns(
    'pages',
    $additionalTCAcolumns
);

ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    '--div--;'
        . $ll('pages.div.program_information')
        . ','
        . implode(',', [
            'job_profile',
            'performance_scope',
            'prerequisites'
        ]),
    (string)$academicProgramDoktype,
    'after:rowDescription'
);
