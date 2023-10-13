<?php

declare(strict_types=1);

use FGTCLB\EducationalCourse\Domain\Enumeration\Category;

$sourceString = function (string $icon) {
    return sprintf(
        'EXT:educational_course/Resources/Public/Icons/%s.svg',
        \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToLowerCamelCase($icon)
    );
};

$identifierString = function (string $identifier) {
    return sprintf(
        'educational-course-%s',
        $identifier
    );
};

return [
    $identifierString(Category::TYPE_ADMISSION_RESTRICTION) => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString(Category::TYPE_ADMISSION_RESTRICTION),
    ],
    $identifierString(Category::TYPE_APPLICATION_PERIOD) => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString(Category::TYPE_APPLICATION_PERIOD),
    ],
    $identifierString(Category::TYPE_BEGIN_COURSE) => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString(Category::TYPE_BEGIN_COURSE),
    ],
    $identifierString(Category::TYPE_COSTS) => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString(Category::TYPE_COSTS),
    ],
    $identifierString(Category::TYPE_DEGREE) => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString(Category::TYPE_DEGREE),
    ],
    $identifierString(Category::TYPE_DEPARTMENT) => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString(Category::TYPE_DEPARTMENT),
    ],
    $identifierString(Category::TYPE_STANDARD_PERIOD) => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString(Category::TYPE_STANDARD_PERIOD),
    ],
    $identifierString(Category::TYPE_COURSE_TYPE) => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString(Category::TYPE_COURSE_TYPE),
    ],
    $identifierString(Category::TYPE_TEACHING_LANGUAGE) => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString(Category::TYPE_TEACHING_LANGUAGE),
    ],
    $identifierString(Category::TYPE_TOPIC) => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString(Category::TYPE_TOPIC),
    ],
    $identifierString(Category::TYPE_LOCATION) => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString(Category::TYPE_LOCATION),
    ],
];
