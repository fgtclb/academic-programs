<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Enumeration;

/**
 * Where the facts of a program are rendered. The place decides which facts an empty field
 * list stands for - see {@see \FGTCLB\AcademicPrograms\Service\ProgramFactsBuilder}.
 */
enum ProgramFactsPlace: string
{
    /**
     * The program page.
     */
    case Page = 'page';

    /**
     * The program details content element.
     */
    case Details = 'details';

    /**
     * A program card of the program list.
     */
    case Card = 'card';
}
