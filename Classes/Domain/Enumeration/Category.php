<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Domain\Enumeration;

use TYPO3\CMS\Core\Type\Enumeration;

class Category extends Enumeration
{
    public const TYPE_APPLICATION_PERIOD = 'applicationPeriod';

    public const TYPE_BEGIN_COURSE = 'beginCourse';

    public const TYPE_COSTS = 'costs';

    public const TYPE_DEGREE = 'degree';

    public const TYPE_STANDARD_PERIOD = 'standardPeriod';

    public const TYPE_COURSE_TYPE = 'courseType';

    public const TYPE_TEACHING_LANGUAGE = 'teachingLanguage';
}
