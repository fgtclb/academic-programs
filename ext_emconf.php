<?php

$EM_CONF[$_EXTKEY] = [
    'title' => '(FGTCLB) University Educational Program',
    'description' => 'Educational Program page for TYPO3 with structured data based on sys_category',
    'category' => 'fe',
    'state' => 'beta',
    'version' => '1.1.4',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-12.4.99',
            'backend' => '11.5.0-12.4.99',
            'extbase' => '11.5.0-12.4.99',
            'frontend' => '11.5.0-12.4.99',
            'fluid' => '11.5.0-12.4.99',
            'category_types' => '1.1.0-1.99.99',
        ],
        'conflicts' => [],
        'suggests' => [
            'page_backend_layout' => '*',
        ],
    ],
];
