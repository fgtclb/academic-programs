<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'FGTCLB: University Educational Program',
    'description' => 'Educational Program page for TYPO3 with structured data based on sys_category',
    'version' => '3.0.0',
    'category' => 'fe',
    'state' => 'beta',
    'author' => 'FGTCLB',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-13.4.99',
            'backend' => '13.4.0-13.4.99',
            'extbase' => '13.4.0-13.4.99',
            'frontend' => '13.4.0-13.4.99',
            'fluid' => '13.4.0-13.4.99',
            'academic_base' => '3.0.0',
            'category_types' => '3.0.0',
        ],
        'suggests' => [
            'page_backend_layout' => '2.0.0-2.99.99',
        ],
    ],
];
