<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'FGTCLB: University Educational Program',
    'description' => 'Educational Program page for TYPO3 with structured data based on sys_category',
    'version' => '2.3.3',
    'category' => 'fe',
    'state' => 'beta',
    'author' => 'FGTCLB',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.22-13.4.99',
            'backend' => '12.4.22-13.4.99',
            'extbase' => '12.4.22-13.4.99',
            'frontend' => '12.4.22-13.4.99',
            'fluid' => '12.4.22-13.4.99',
            'academic_base' => '2.3.3',
            'category_types' => '2.3.3',
        ],
        'suggests' => [
            'page_backend_layout' => '2.0.0-2.99.99',
        ],
    ],
];
