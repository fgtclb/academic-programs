<?php

declare(strict_types=1);

$EM_CONF['academic_programs'] = [
    'title' => '(FGTCLB) University Educational Program',
    'description' => 'Educational Program page for TYPO3 with structured data based on sys_category',
    'category' => 'fe',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-13.4.99',
            'page_backend_layout' => '*',
            'category_types' => '*',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
