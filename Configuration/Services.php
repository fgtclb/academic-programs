<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use FGTCLB\AcademicPrograms\Collection\FilterCollection;
use FGTCLB\AcademicPrograms\Domain\Repository\CategoryRepository;
use FGTCLB\AcademicPrograms\Domain\Repository\ProgramRepository;

return function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator
        ->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services
        ->load('FGTCLB\\AcademicPrograms\\', '../Classes/')
        ->exclude('../Classes/Domain/Model/');

    $services
        ->set(FilterCollection::class)
        ->public();

    $services
        ->set(CategoryRepository::class)
        ->public();

    $services
        ->set(ProgramRepository::class)
        ->public();
};
