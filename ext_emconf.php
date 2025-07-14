<?php

$EM_CONF[$_EXTKEY] = [
    'author' => 'FGTCLB',
    'author_company' => 'FGTCLB GmbH',
    'author_email' => 'hello@fgtclb.com',
    'category' => 'fe',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.22-13.4.99',
            'backend' => '12.4.22-13.4.99',
            'extbase' => '12.4.22-13.4.99',
            'frontend' => '12.4.22-13.4.99',
            'fluid' => '12.4.22-13.4.99',
            'category_types' => '2.0.2',
        ],
        'conflicts' => [],
        'suggests' => [
            'page_backend_layout' => '2.0.0-2.99.99',
        ],
    ],
    'description' => 'Educational Program page for TYPO3 with structured data based on sys_category',
    'state' => 'beta',
    'title' => 'FGTCLB: University Educational Program',
    'version' => '2.0.2',
];
