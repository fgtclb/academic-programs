<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Domain\Enumeration;

use TYPO3\CMS\Core\Type\Enumeration;

class Category extends Enumeration
{
    public const TYPE_ADMISSION_RESTRICTION = 'admission_restriction';

    public const TYPE_APPLICATION_PERIOD = 'application_period';

    public const TYPE_BEGIN_COURSE = 'begin_course';

    public const TYPE_COSTS = 'costs';

    public const TYPE_DEGREE = 'degree';

    public const TYPE_DEPARTMENT = 'department';

    public const TYPE_STANDARD_PERIOD = 'standard_period';

    public const TYPE_LOCATION = 'location';

    public const TYPE_COURSE_TYPE = 'course_type';

    public const TYPE_TEACHING_LANGUAGE = 'teaching_language';

    public const TYPE_TOPIC = 'topic';

    public static function typeExist(string $value): bool
    {
        foreach (Category::getConstants() as $constantValue) {
            if ($value === (string)$constantValue) {
                return true;
            }
        }

        return false;
    }
}
