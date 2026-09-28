<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Backend\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Starts the category tree of a program page, of the program list and of the program
 * finder at the categories the site setting `plugin.tx_academicprograms.categoryRootUids`
 * names.
 *
 * The fields declare the core marker `###SITE:…###` of that setting as their starting
 * points, and this provider replaces it before core `TcaCategory` resolves it: with the
 * category uids of the setting, or, when the setting names none, by removing the
 * starting points. Core would resolve an empty setting, a text or a record outside of
 * every site to a single starting point `0`, and a single starting point makes the top
 * of the tree selectable. Without a category in the setting the field therefore stays
 * exactly what it is without the marker.
 *
 * A form data group this provider is not registered in keeps the marker, which core
 * resolves the same way for a setting that names categories. Registered after
 * `SiteResolving` and `TcaColumnsOverrides`, which brings the marker of a program page
 * with the columns overrides of its page type, and before `TcaCategory` in
 * `ext_localconf.php`.
 *
 * @internal Registered as form data provider, not part of the public API.
 */
final readonly class CategoryTreeRoot implements FormDataProviderInterface
{
    public const SETTING = 'plugin.tx_academicprograms.categoryRootUids';
    public const MARKER = '###SITE:settings.' . self::SETTING . '###';

    /**
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    public function addData(array $result): array
    {
        $columns = $result['processedTca']['columns'] ?? null;
        if (!is_array($columns)) {
            return $result;
        }
        $startingPoints = null;
        foreach ($columns as $fieldName => $fieldConfig) {
            if (($fieldConfig['config']['type'] ?? '') !== 'category'
                || ($fieldConfig['config']['treeConfig']['startingPoints'] ?? null) !== self::MARKER
            ) {
                continue;
            }
            $startingPoints ??= $this->categoryRootUids($result['site'] ?? null);
            if ($startingPoints === '') {
                unset($result['processedTca']['columns'][$fieldName]['config']['treeConfig']['startingPoints']);
            } else {
                $result['processedTca']['columns'][$fieldName]['config']['treeConfig']['startingPoints'] = $startingPoints;
            }
        }

        return $result;
    }

    /**
     * The positive uids of the setting, comma-separated, or an empty string. The value is
     * read where the core marker reads it and taken in the same forms, a comma-separated
     * string, an integer or a list, so a site that does not declare the setting provides
     * it only when its settings name it as a tree, in both cases.
     */
    private function categoryRootUids(mixed $site): string
    {
        if (!$site instanceof Site) {
            return '';
        }
        try {
            $value = ArrayUtility::getValueByPath($site->getConfiguration(), 'settings.' . self::SETTING, '.');
        } catch (MissingArrayPathException) {
            return '';
        }
        if (is_array($value)) {
            $uids = array_map(intval(...), $value);
        } elseif (is_string($value) || is_int($value)) {
            $uids = GeneralUtility::intExplode(',', (string)$value, true);
        } else {
            return '';
        }
        $uids = array_filter($uids, static fn(int $uid): bool => $uid > 0);

        return implode(',', array_unique($uids));
    }
}
