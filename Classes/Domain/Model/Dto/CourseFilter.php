<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Domain\Model\Dto;

use FGTCLB\EducationalCourse\Domain\Model\EducationalCategory;

class CourseFilter
{
    protected ?EducationalCategory $applicationPeriod = null;

    protected ?EducationalCategory $costs = null;

    /**
     * @return EducationalCategory|null
     */
    public function getApplicationPeriod(): ?EducationalCategory
    {
        return $this->applicationPeriod;
    }

    /**
     * @return EducationalCategory|null
     */
    public function getCosts(): ?EducationalCategory
    {
        return $this->costs;
    }
}
