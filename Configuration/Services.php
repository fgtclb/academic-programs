<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use FGTCLB\EducationalCourse\Collection\FilterCollection;
use FGTCLB\EducationalCourse\DataProcessor\CategoryProcessor;
use FGTCLB\EducationalCourse\Domain\Model\Dto\CourseDemand;
use FGTCLB\EducationalCourse\Domain\Repository\CategoryRepository;

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
        ->set(CategoryProcessor::class)
        ->public();

    $services
        ->set(CategoryRepository::class)
        ->public();

    $services
        ->set(CourseDemand::class)
        ->public();

    $services
        ->set(FilterCollection::class)
        ->public();
};
