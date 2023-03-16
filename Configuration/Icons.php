<?php

declare(strict_types=1);

$sourceString = function (string $icon) {
    return sprintf('EXT:educational_course/Resources/Public/Icons/%s', $icon);
};

return [
    'educational-course-applicationPeriod' => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString('applicationPeriod.svg'),
    ],
    'educational-course-beginCourse' => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString('beginCourse.svg'),
    ],
    'educational-course-costs' => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString('costs.svg'),
    ],
    'educational-course-degree' => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString('degree.svg'),
    ],
    'educational-course-jobProfile' => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString('jobProfile.svg'),
    ],
    'educational-course-performanceScope' => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString('performanceScope.svg'),
    ],
    'educational-course-prerequisites' => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString('prerequisites.svg'),
    ],
    'educational-course-standardPeriod' => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString('standardPeriod.svg'),
    ],
    'educational-course-courseType' => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString('courseType.svg'),
    ],
    'educational-course-teachingLanguage' => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString('teachingLanguage.svg'),
    ],
];
