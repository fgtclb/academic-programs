<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Migrations\Mysql;

use Doctrine\DBAL\Schema\Schema;
use FGTCLB\AcademicPrograms\Enumeration\CategoryTypes;
use FGTCLB\AcademicPrograms\Enumeration\PageTypes;
use KayStrobach\Migrations\Migration\AbstractDataHandlerMigration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\StringUtility;

class Version20230824122000 extends AbstractDataHandlerMigration
{
    public function getDescription(): string
    {
        return 'Create program pages structure';
    }

    public function up(Schema $schema): void
    {
        // Skip migrations when is in produktion or development mode
        // because the content to created is only for local development
        // when you will use then disable the skip conditions.
        $this->skipIf((Environment::getContext()->isProduction() || Environment::getContext()->isDevelopment()));

        $faker = \Faker\Factory::create('de_DE');

        $buildCategory = function (string $type, string $storagePageId, string $parent) use ($faker): array {
            return [
                'pid' => $storagePageId,
                'type' => $type,
                'title' => 'Type' . CategoryTypes::getHumanReadableName($type),
                'description' => $faker->text(),
                'parent' => $parent,
            ];
        };

        $categoryTypes = CategoryTypes::getConstants();

        $categories = [
            'NEW567' => [
                'pid' => 'NEW124',
                'title' => 'Program Extension Categories',
                'description' => '',
            ],
        ];

        foreach ($categoryTypes as $categoryType) {
            $categories[StringUtility::getUniqueId('NEW')] = $buildCategory($categoryType, 'NEW124', 'NEW567');
        }

        $this->dataMap = [
            'pages' => [
                'NEW111' => [
                    'pid' => 0,
                    'title' => 'Home',
                    'slug' => '/',
                    'doktype' => PageRepository::DOKTYPE_DEFAULT,
                    'hidden' => 0,
                    'is_siteroot' => 1,
                ],
                'NEW123' => [
                    'pid' => 'NEW111',
                    'title' => 'Studiengänge',
                    'slug' => '/studiengaenge',
                    'doktype' => PageRepository::DOKTYPE_DEFAULT,
                    'hidden' => 0,
                ],
                'NEW124' => [
                    'pid' => 'NEW123',
                    'title' => 'Storage',
                    'slug' => '/studiengaenge/storage',
                    'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
                    'hidden' => 0,
                ],
                'NEW125' => [
                    'pid' => 'NEW124',
                    'title' => 'Studiengang 1',
                    'slug' => $faker->slug(),
                    'doktype' => PageTypes::TYPE_EDUCATIONAL_COURSE,
                    'backend_layout' => 'pagets__AcademicPrograms',
                    'hidden' => 0,
                    'job_profile' => $faker->text,
                    'performance_scope' => $faker->text,
                    'prerequisites' => $faker->text,
                ],
                'NEW126' => [
                    'pid' => 'NEW124',
                    'title' => 'Studiengang 2',
                    'slug' => $faker->slug(),
                    'doktype' => PageTypes::TYPE_EDUCATIONAL_COURSE,
                    'backend_layout' => 'pagets__AcademicPrograms',
                    'hidden' => 0,
                    'job_profile' => $faker->text,
                    'performance_scope' => $faker->text,
                    'prerequisites' => $faker->text,
                ],
                'NEW127' => [
                    'pid' => 'NEW124',
                    'title' => 'Studiengang 3',
                    'slug' => $faker->slug(),
                    'doktype' => PageTypes::TYPE_EDUCATIONAL_COURSE,
                    'backend_layout' => 'pagets__AcademicPrograms',
                    'hidden' => 0,
                    'job_profile' => $faker->text,
                    'performance_scope' => $faker->text,
                    'prerequisites' => $faker->text,
                ],
                'NEW128' => [
                    'pid' => 'NEW124',
                    'title' => 'Studiengang 4',
                    'slug' => $faker->slug(),
                    'doktype' => PageTypes::TYPE_EDUCATIONAL_COURSE,
                    'backend_layout' => 'pagets__AcademicPrograms',
                    'hidden' => 0,
                    'job_profile' => $faker->text,
                    'performance_scope' => $faker->text,
                    'prerequisites' => $faker->text,
                ],
            ],
            'tt_content' => [
                'NEW234' => [
                    'pid' => 'NEW123',
                    'header' => 'Studiengänge List',
                    'header_layout' => 100,
                    'colPos' => 0,
                    'CType' => 'list',
                    'list_type' => 'AcademicPrograms_programlist',
                    'pages' => 'NEW124',
                ],
            ],
            'sys_category' => $categories,
        ];

        parent::up($schema);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM pages WHERE tx_migrations_version = :tx_migrations_version', [
            'tx_migrations_version' => '20230824122000',
        ]);

        $this->addSql('DELETE FROM tt_content WHERE tx_migrations_version = :tx_migrations_version', [
            'tx_migrations_version' => '20230824122000',
        ]);

        $this->addSql('DELETE FROM sys_category WHERE tx_migrations_version = :tx_migrations_version', [
            'tx_migrations_version' => '20230824122000',
        ]);
    }
}
