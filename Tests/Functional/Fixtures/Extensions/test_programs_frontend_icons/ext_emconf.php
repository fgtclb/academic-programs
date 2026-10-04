<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Programs Frontend Icons',
    'description' => 'A site package that replaces program fact icons and declares a category type with a frontend icon, for tests',
    'version' => '3.0.0',
    'category' => 'misc',
    'state' => 'beta',
    'author' => 'Stefan Bürk',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.35-14.3.99',
            'core' => '13.4.35-14.3.99',
            'academic_programs' => '3.0.0',
            'category_types' => '3.0.0',
        ],
    ],
];
