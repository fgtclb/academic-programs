<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\ViewHelpers\Be;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use FGTCLB\AcademicPrograms\Domain\Repository\CategoryRepository;
use FGTCLB\AcademicPrograms\Enumeration\CategoryTypes;
use FGTCLB\AcademicPrograms\Exception\CategoryTypeException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

class CategoryViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument(
            'page',
            'int',
            'Page ID',
            true
        );
        $this->registerArgument(
            'type',
            'string',
            'The type, none given returns all in associative array'
        );
        $this->registerArgument(
            'as',
            'string',
            'The variable name',
            false,
            'category'
        );
    }

    /**
     * @param array{
     *     page: int,
     *     type: string,
     *     as: string
     * } $arguments
     * @throws DBALException
     * @throws Exception
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): string {
        $templateVariableContainer = $renderingContext->getVariableProvider();

        /** @var CategoryRepository $repository */
        $repository = GeneralUtility::makeInstance(CategoryRepository::class);
        try {
            if ($arguments['type'] !== null && CategoryTypes::typeExist($arguments['type'])) {
                $categoryType = CategoryTypes::cast($arguments['type']);
                $categories = $repository->findByType($arguments['page'], $categoryType);
            } else {
                throw new CategoryTypeException(sprintf('The category type "%s" not exist', $arguments['type']), 1694770343759);
            }
        } catch (\Exception) {
            $categories = $repository->findAllByPageId($arguments['page']);
        }

        $templateVariableContainer->add($arguments['as'], $categories);

        $output = $renderChildrenClosure();

        $templateVariableContainer->remove($arguments['as']);

        return $output;
    }
}
