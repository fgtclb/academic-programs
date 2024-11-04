<?php

declare(strict_types=1);

use FGTCLB\AcademicPrograms\Enumeration\CategoryTypes;

return (function () {
    $sourceString = function (string $icon) {
        return sprintf(
            'EXT:educational_course/Resources/Public/Icons/%s.svg',
            \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToLowerCamelCase($icon)
        );
    };

    $identifierString = function (string $identifier) {
        return sprintf(
            'academic-programs-%s',
            $identifier
        );
    };

    $icons = [];
    foreach (CategoryTypes::getConstants() as $constant) {
        $icons[$identifierString($constant)] = [
            'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            'source' => $sourceString($constant),
        ];
    }

    return $icons;
})();
