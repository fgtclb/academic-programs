<?php

declare(strict_types=1);

$EM_CONF['educational_course'] = [
    'title' => '(FGTCLB) University Educational Course',
    'description' => 'Educational Course page for TYPO3 with structured data based on sys_category',
    'category' => 'fe',
    'state' => 'beta',
    'version' => '1.3.1',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-11.5.99',
            'category_types' => '*',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
