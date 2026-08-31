<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Collection;

use FGTCLB\AcademicPrograms\Collection\FileReferenceCollection;
use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\FileReference;

/**
 * `FileReferenceCollection::getCollectionByPageIdAndField()` reads `sys_file_reference`
 * by hand instead of going through `FileRepository::findByRelation()`, so the three
 * constraints of the relation - the record uid, the table and the field - are its own
 * code and are what is pinned down here.
 *
 * The near misses in the fixture are the point: uid 3 is referenced from the same page
 * through another field and from `tt_content` uid 1 through the same field name. Both
 * would come back if one of the three conditions were dropped.
 *
 * The collection carries the **core** `TYPO3\CMS\Core\Resource\FileReference`, not the
 * Extbase one, so its metadata getters resolve rather than returning empty strings -
 * `getTitle()` reads the reference's own value and falls back to the file metadata when
 * the reference leaves it `NULL`. That fallback is the behaviour a caller gets for free
 * here and would not get from the Extbase model.
 *
 * The collection follows `sorting_foreign` - the order the editor arranged the images
 * in - with `uid` settling ties, since ACE-491; before that the query carried no
 * `ORDER BY` and the order was whatever the DBMS returned.
 */
final class FileReferenceCollectionTest extends AbstractAcademicProgramsTestCase
{
    private const PAGE_WITH_MEDIA = 1;
    private const PAGE_WITHOUT_MEDIA = 2;
    private const PAGE_WITH_REMOVED_MEDIA = 3;
    private const PAGE_WITH_TRANSLATED_MEDIA = 4;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FileReferenceCollection/fileReferences.csv');
    }

    #[Test]
    public function countReportsTheNumberOfMatchingReferences(): void
    {
        $collection = FileReferenceCollection::getCollectionByPageIdAndField(self::PAGE_WITH_MEDIA, 'media');

        $this->assertCount(2, $collection);
        $this->assertSame(2, $collection->count());
    }

    #[Test]
    public function everyMatchingReferenceIsPartOfTheCollection(): void
    {
        $collection = FileReferenceCollection::getCollectionByPageIdAndField(self::PAGE_WITH_MEDIA, 'media');

        $this->assertSame([1, 2], $this->referenceUids($collection));
    }

    /**
     * The fixture contradicts creation order on purpose: reference 2 carries
     * `sorting_foreign` 1 and reference 1 carries 2, so this fails on every DBMS -
     * SQLite included - as soon as the ordering is dropped (ACE-491).
     */
    #[Test]
    public function referencesFollowTheOrderTheEditorArranged(): void
    {
        $uids = [];
        foreach (FileReferenceCollection::getCollectionByPageIdAndField(self::PAGE_WITH_MEDIA, 'media') as $reference) {
            $uids[] = $reference->getUid();
        }

        $this->assertSame([2, 1], $uids);
    }

    /**
     * The collection hands out reference objects, not file objects: the uid of the
     * reference and the uid of the file it points at are different numbers, and a caller
     * rendering the image needs the second one.
     */
    #[Test]
    public function eachReferencePointsAtItsOriginalFile(): void
    {
        $collection = FileReferenceCollection::getCollectionByPageIdAndField(self::PAGE_WITH_MEDIA, 'media');

        $names = [];
        foreach ($collection as $reference) {
            $this->assertInstanceOf(FileReference::class, $reference);
            $names[$reference->getOriginalFile()->getUid()] = $reference->getName();
        }
        ksort($names);

        $this->assertSame([1 => 'program-flyer.png', 2 => 'campus.png'], $names);
    }

    /**
     * The same page references uid 3 through `og_image`. Without the `fieldname`
     * condition every file attached to the page anywhere would end up in the collection.
     */
    #[Test]
    public function referencesOfAnotherFieldOfTheSamePageAreNotPartOfTheCollection(): void
    {
        $this->assertSame([1, 2], $this->referenceUids(
            FileReferenceCollection::getCollectionByPageIdAndField(self::PAGE_WITH_MEDIA, 'media')
        ));
        $this->assertSame([3], $this->referenceUids(
            FileReferenceCollection::getCollectionByPageIdAndField(self::PAGE_WITH_MEDIA, 'og_image')
        ));
    }

    /**
     * `uid_foreign` is only unique per table, so a `tt_content` record that happens to
     * carry the same uid as the page would leak into the result without the
     * `tablenames = 'pages'` condition - which is exactly what the fixture sets up.
     */
    #[Test]
    public function referencesOfAnotherTableAreNotPartOfTheCollection(): void
    {
        $collection = FileReferenceCollection::getCollectionByPageIdAndField(self::PAGE_WITH_MEDIA, 'media');

        $this->assertNotContains(4, $this->referenceUids($collection));
    }

    #[Test]
    public function aFieldWithoutAnyReferenceYieldsAnEmptyCollection(): void
    {
        $collection = FileReferenceCollection::getCollectionByPageIdAndField(self::PAGE_WITH_MEDIA, 'teaser_image');

        $this->assertCount(0, $collection);
        $this->assertSame([], $this->referenceUids($collection));
    }

    #[Test]
    public function anUnknownPageIdYieldsAnEmptyCollection(): void
    {
        $collection = FileReferenceCollection::getCollectionByPageIdAndField(999, 'media');

        $this->assertCount(0, $collection);
    }

    #[Test]
    public function aPageWithoutAnyReferenceYieldsAnEmptyCollection(): void
    {
        $collection = FileReferenceCollection::getCollectionByPageIdAndField(self::PAGE_WITHOUT_MEDIA, 'media');

        $this->assertCount(0, $collection);
    }

    /**
     * The query builder comes from the connection pool and therefore carries the default
     * restrictions. `sys_file_reference` declares both `deleted` and `hidden`, so an
     * editor removing or disabling a reference takes it out of the collection - without a
     * single line in this class saying so, which is why it is worth a test.
     */
    #[Test]
    public function deletedAndHiddenReferencesAreNotPartOfTheCollection(): void
    {
        $collection = FileReferenceCollection::getCollectionByPageIdAndField(self::PAGE_WITH_REMOVED_MEDIA, 'media');

        $this->assertCount(0, $collection);
    }

    /**
     * An empty collection has to survive being asked for its current entry: `current()`
     * on an empty array is `false`, which is what `valid()` reports on and what stops a
     * `foreach` before its first iteration.
     */
    #[Test]
    public function anEmptyCollectionIsNotValidAndHasNoCurrentEntry(): void
    {
        $collection = FileReferenceCollection::getCollectionByPageIdAndField(self::PAGE_WITHOUT_MEDIA, 'media');

        $this->assertFalse($collection->valid());
        $this->assertFalse($collection->current());
        $this->assertNull($collection->key());

        $seen = 0;
        foreach ($collection as $unused) {
            $seen++;
        }
        $this->assertSame(0, $seen);
    }

    /**
     * The iterator is the internal array pointer, so a second pass only works because
     * `rewind()` resets it. A template that lists the references twice - once as a
     * gallery, once as a download list - is the ordinary case for that.
     */
    #[Test]
    public function theCollectionCanBeIteratedMoreThanOnce(): void
    {
        $collection = FileReferenceCollection::getCollectionByPageIdAndField(self::PAGE_WITH_MEDIA, 'media');

        $firstPass = $this->referenceUids($collection);
        $secondPass = $this->referenceUids($collection);

        $this->assertSame([1, 2], $firstPass);
        $this->assertSame($firstPass, $secondPass);
    }

    /**
     * The keys are the positions of the internal list, not the reference uids - a
     * template addressing an entry by index gets the position.
     */
    #[Test]
    public function iterationYieldsTheSequentialArrayPositionsAsKeys(): void
    {
        $collection = FileReferenceCollection::getCollectionByPageIdAndField(self::PAGE_WITH_MEDIA, 'media');

        $this->assertSame([0, 1], array_keys(iterator_to_array($collection)));
    }

    /**
     * What a file reference exists for: the values entered on the relation win over the
     * metadata of the file they point at.
     */
    #[Test]
    public function aReferenceOverridesTheMetadataOfItsFile(): void
    {
        $reference = $this->referenceWithUid(1);

        $this->assertSame('Flyer title from the reference', $reference->getTitle());
        $this->assertSame('Flyer alternative from the reference', $reference->getAlternative());
    }

    /**
     * Reference uid 2 leaves title, alternative and description `NULL`, which is what the
     * backend stores for an untouched field, so the values of `sys_file_metadata` show
     * through. Reading this off the Extbase `FileReference` would yield empty strings
     * instead - the core object is what makes the fallback observable.
     */
    #[Test]
    public function aReferenceWithoutOwnMetadataFallsBackToTheFileMetadata(): void
    {
        $reference = $this->referenceWithUid(2);

        $this->assertSame('Campus from the file metadata', $reference->getTitle());
        $this->assertSame('Campus alternative from the file metadata', $reference->getAlternative());
        $this->assertSame('Campus description from the file metadata', $reference->getDescription());
    }

    /**
     * The query constrains neither `sys_language_uid` nor `l10n_parent`, so the
     * translated reference of page 4 is returned next to its default language original.
     * A caller therefore has to pass the uid of the translated page, not the uid of the
     * default language one - the method does no overlay of any kind.
     */
    #[Test]
    public function referencesAreNotFilteredByLanguage(): void
    {
        $collection = FileReferenceCollection::getCollectionByPageIdAndField(self::PAGE_WITH_TRANSLATED_MEDIA, 'media');

        $this->assertSame([7, 8], $this->referenceUids($collection));
    }

    /**
     * @return array<int, int>
     */
    private function referenceUids(FileReferenceCollection $collection): array
    {
        $uids = [];
        foreach ($collection as $reference) {
            $uids[] = $reference->getUid();
        }
        sort($uids);

        return $uids;
    }

    private function referenceWithUid(int $uid): FileReference
    {
        foreach (FileReferenceCollection::getCollectionByPageIdAndField(self::PAGE_WITH_MEDIA, 'media') as $reference) {
            if ($reference->getUid() === $uid) {
                return $reference;
            }
        }
        $this->fail(sprintf('No file reference with uid %d in the collection.', $uid));
    }
}
