<?php

declare(strict_types=1);

$sourceString = function ($icon) {
    return sprintf('EXT:educational_course/Resources/Public/Icons/%s', $icon);
};

return [
    'educational-course-application-period' => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString('applicationPeriod.svg'),
    ],
    'educational-course-begin-course' => [
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
    'educational-course-job-profile' => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString('jobProfile.svg'),
    ],
    'educational-course-performance-scope' => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString('performanceScope.svg'),
    ],
    'educational-course-prerequisites' => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString('prerequisites.svg'),
    ],
    'educational-course-standard-period' => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString('standardPeriod.svg'),
    ],
    'educational-course-course-type' => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString('courseType.svg'),
    ],
    'educational-course-teaching-language' => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString('teachingLanguage.svg'),
    ],
];
