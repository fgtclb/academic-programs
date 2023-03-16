<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Domain\Enumeration;

use TYPO3\CMS\Core\Type\Enumeration;

class Category extends Enumeration
{
    public const TYPE_APPLICATION_PERIOD = 'application_period';

    public const TYPE_BEGIN_COURSE = 'begin_course';

    public const TYPE_COSTS = 'costs';

    public const TYPE_DEGREE = 'degree';

    public const TYPE_STANDARD_PERIOD = 'standard_period';

    public const TYPE_COURSE_TYPE = 'course_type';

    public const TYPE_TEACHING_LANGUAGE = 'teaching_language';
}
