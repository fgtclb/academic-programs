<?php

$EM_CONF[$_EXTKEY] = [
    'title' => '(FGTCLB) University Educational Program',
    'description' => 'Educational Program page for TYPO3 with structured data based on sys_category',
    'category' => 'fe',
    'state' => 'beta',
    'version' => '2.0.1',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
            'backend' => '12.4.0-13.4.99',
            'extbase' => '12.4.0-13.4.99',
            'frontend' => '12.4.0-13.4.99',
            'fluid' => '12.4.0-13.4.99',
            'category_types' => '2.0.1',
        ],
        'conflicts' => [],
        'suggests' => [
            'page_backend_layout' => '2.0.0-2.99.99',
        ],
    ],
];
