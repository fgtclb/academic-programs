<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

$iconList['academic-programs'] = [
    'provider' => SvgIconProvider::class,
    'source' => 'EXT:academic_programs/Resources/Public/Icons/Extension.svg',
];

return $iconList;
