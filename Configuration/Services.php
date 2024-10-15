<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use FGTCLB\EducationalCourse\Collection\FilterCollection;
use FGTCLB\EducationalCourse\Domain\Repository\CategoryRepository;
use FGTCLB\EducationalCourse\Domain\Repository\CourseRepository;

return function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator
        ->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services
        ->load('FGTCLB\\EducationalCourse\\', '../Classes/')
        ->exclude('../Classes/Domain/Model/');

    $services
        ->set(FilterCollection::class)
        ->public();

    $services
        ->set(CategoryRepository::class)
        ->public();

    $services
        ->set(CourseRepository::class)
        ->public();
};
