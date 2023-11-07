<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Database\Query\Restriction;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Restriction to respect the language functionality of TYPO3.
 * Filters out records, that were marked as deleted.
 *
 * ToDo: Check Fallback Language
 * ToDo: Strick Mode
 */
class LanguageRestriction implements QueryRestrictionInterface
{
    /**
     * Main method to build expressions for given tables
     * Evaluates the ctrl/languageField flag of the table and adds the according restriction if set
     *
     * @param array<string, mixed> $queriedTables Array of tables, where array key is table alias and value is a table name
     * @param ExpressionBuilder $expressionBuilder Expression builder instance to add restrictions with
     * @return CompositeExpression The result of query builder expression(s)
     */
    public function buildExpression(array $queriedTables, ExpressionBuilder $expressionBuilder): CompositeExpression
    {
        $constraints = [];

        /** @var LanguageAspect $languageAspect */
        $languageAspect = GeneralUtility::makeInstance(Context::class)->getAspect('language');

        foreach ($queriedTables as $tableAlias => $tableName) {
            $languageFieldName = $GLOBALS['TCA'][$tableName]['ctrl']['languageField'] ?? null;
            if (!empty($languageFieldName)) {
                $constraints[] = $expressionBuilder->eq(
                    $tableAlias . '.' . $languageFieldName,
                    $languageAspect->getId()
                );
            }
        }

        return  $expressionBuilder->andX(...$constraints);
    }
}
