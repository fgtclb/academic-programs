<?php

declare(strict_types=1);

$EM_CONF['academic_programs'] = [
    'title' => '(FGTCLB) University Educational Program',
    'description' => 'Educational Program page for TYPO3 with structured data based on sys_category',
    'category' => 'fe',
    'state' => 'beta',
    'version' => '2.1.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-11.5.99',
            'category_types' => '*',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
