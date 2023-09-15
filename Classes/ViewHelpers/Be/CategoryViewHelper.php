<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\ViewHelpers\Be;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use FGTCLB\EducationalCourse\Domain\Enumeration\Category;
use FGTCLB\EducationalCourse\Domain\Repository\CourseCategoryRepository;
use FGTCLB\EducationalCourse\Exception\CategoryTypeException;
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
            'courseCategory'
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

        $repository = GeneralUtility::makeInstance(CourseCategoryRepository::class);
        try {
            if (Category::typeExist($arguments['type'])) {
                $categoryType = Category::cast($arguments['type']);
                $attributes = $repository->findByType($arguments['page'], $categoryType);
            } else {
                throw new CategoryTypeException(sprintf('The category type "%s" not exist', $arguments['type']), 1694770343759);
            }
        } catch (\Exception $exception) {
            $attributes = $repository->findAllByPageId($arguments['page']);
        }

        $templateVariableContainer->add($arguments['as'], $attributes);

        $output = $renderChildrenClosure();

        $templateVariableContainer->remove($arguments['as']);

        return $output;
    }
}
