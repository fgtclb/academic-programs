<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use FGTCLB\AcademicPrograms\Collection\FilterCollection;
use FGTCLB\AcademicPrograms\DataProcessing\ProgramDataProcessor;
use FGTCLB\AcademicPrograms\Domain\Repository\CategoryRepository;
use FGTCLB\AcademicPrograms\Domain\Repository\ProgramRepository;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

return function (ContainerConfigurator $containerConfigurator) {
    $versionInformation = GeneralUtility::makeInstance(Typo3Version::class);

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

    // With TYPO3 version 12.1 an alias can be registered for the data processor
    if (version_compare($versionInformation->getVersion(), '12.1.0', '>=')) {
        $services
            ->set(ProgramDataProcessor::class)
            ->tag(
                'data.processor',
                [
                    'identifier' => 'program-data',
                    'label' => 'Program Data Processor',
                ]
            );
    }
};
