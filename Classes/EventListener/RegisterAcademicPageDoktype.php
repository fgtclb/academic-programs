<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\EventListener;

use FGTCLB\AcademicPrograms\Enumeration\PageTypes;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Core\Event\BootCompletedEvent;
use TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry;
use TYPO3\CMS\Core\Information\Typo3Version;

/**
 * Registers the page type of this extension with the PageDoktypeRegistry for TYPO3 v13.
 *
 * TYPO3 v14 resolves the tables allowed on a page type from the TCA option
 * `allowedRecordTypes`, which `Configuration/TCA/Overrides/pages.php` sets. TYPO3 v13
 * has no such option and still asks the registry, and the registry cannot be fed from a
 * TCA override file: on v13 the first `add()` latches an allow list collected from a
 * `TcaSchemaFactory` that is not loaded yet while the overrides run, which costs the
 * `default` page type its `tt_content` entry and makes the DataHandler refuse content
 * elements on every standard page (ACE-462). This event is dispatched one line after
 * `TcaSchemaFactory::load()` and on every request, warm cache included, which is what
 * both halves of that defect need.
 *
 * The version check guards a method that exists on both versions - there is nothing to
 * feature-detect - and calling it on v14 raises the deprecation announcing its removal
 * in TYPO3 v15.
 *
 * @see \TYPO3\CMS\Core\Core\Bootstrap::init()
 * @todo Remove once TYPO3 v13 support is dropped.
 */
#[AsEventListener(identifier: 'academic-programs/register-page-doktype')]
final readonly class RegisterAcademicPageDoktype
{
    public function __construct(
        private PageDoktypeRegistry $pageDoktypeRegistry,
    ) {}

    public function __invoke(BootCompletedEvent $event): void
    {
        if ((new Typo3Version())->getMajorVersion() >= 14) {
            return;
        }

        $this->pageDoktypeRegistry->add(PageTypes::TYPE_ACADEMIC_PROGRAM, ['allowedTables' => '*']);
    }
}
