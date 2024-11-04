<?php

declare(strict_types=1);

use FGTCLB\AcademicPrograms\Domain\Model\Program;

return [
    Program::class => [
        'tableName' => 'pages',
        'properties' => [
            'doktype' => [
                'fieldName' => 'doktype',
            ],
            'lastUpdated' => [
                'fieldName' => 'lastUpdated',
            ],
            'media' => [
                'fieldName' => 'media',
            ],
            'job_profile' => [
                'fieldName' => 'job_profile',
            ],
            'performanceScope' => [
                'fieldName' => 'performance_scope',
            ],
            'prerequisites' => [
                'fieldName' => 'prerequisites',
            ],
        ],
    ],
];
