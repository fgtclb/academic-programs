<?php

declare(strict_types=1);

/**
 * Import map of the frontend modules of this extension.
 *
 * The compiled modules below "Resources/Public/JavaScript/frontend/" are ES
 * modules and are addressed by the bare specifier below, never by a path. Only
 * a specifier resolved through the import map receives TYPO3's "?bust=" cache
 * key. Only the "frontend/" prefix is mapped, so a backend module added later
 * cannot be reached from a frontend page.
 *
 * The "core" dependency makes the modules EXT:core declares resolvable here.
 */
return [
    'dependencies' => [
        'core',
    ],
    'imports' => [
        '@fgtclb/academic-programs/frontend/' => 'EXT:academic_programs/Resources/Public/JavaScript/frontend/',
    ],
];
