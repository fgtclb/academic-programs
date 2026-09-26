<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Controller;

use FGTCLB\AcademicPrograms\Domain\Repository\ProgramRepository;
use FGTCLB\AcademicPrograms\Factory\DemandFactory;
use FGTCLB\CategoryTypes\Collection\CategoryCollection;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use FGTCLB\CategoryTypes\Filter\FilterTypeResolver;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class ProgramController extends ActionController
{
    /**
     * The selects of a finder whose element and site name no filter types.
     */
    private const FINDER_DEFAULT_CATEGORY_TYPES = 'degree,topic';

    private ExtensionService $filterRedirectExtensionService;

    private FilterTypeResolver $filterTypeResolver;

    public function __construct(
        protected ProgramRepository $programRepository,
        protected CategoryRepository $categoryRepository,
        protected DemandFactory $programDemandFactory
    ) {}

    /**
     * @param array<string, mixed>|null $demand
     * @return ResponseInterface
     */
    public function listAction(?array $demand = null): ResponseInterface
    {
        /** @var array<string, mixed> $contentElementData */
        $contentElementData = $this->getCurrentContentObjectRenderer()?->data ?? [];
        $this->redirectFilterSubmission($contentElementData);
        $demandObject = $this->programDemandFactory->createDemandObject(
            $demand,
            $this->settings,
            $contentElementData
        );

        $programs = $this->programRepository->findByDemand($demandObject);
        $categories = $this->categoryRepository->findAllApplicable('programs', ...array_values($programs->toArray()));

        $this->view->assignMultiple([
            'programs' => $programs,
            'data' => $contentElementData,
            'demand' => $demandObject,
            'categories' => $categories,
            'filterTypes' => $this->filterTypeResolver->resolveFromSettings($categories, $this->settings),
        ]);

        return $this->htmlResponse();
    }

    /**
     * The program finder: a select per category type that submits to the list plugin on the
     * page `settings.listPid`, in the argument shape of the list's own filter form. The
     * options are those the list would offer for the programs in the finder's storage, the
     * ones no program carries disabled, or left out when `settings.filter.hideDisabledOptions`
     * is set.
     */
    public function finderAction(): ResponseInterface
    {
        /** @var array<string, mixed> $contentElementData */
        $contentElementData = $this->getCurrentContentObjectRenderer()?->data ?? [];
        $demandObject = $this->programDemandFactory->createDemandObject(null, $this->settings, $contentElementData);

        $programs = $this->programRepository->findByDemand($demandObject);
        $categories = $this->categoryRepository->findAllApplicable('programs', ...array_values($programs->toArray()));
        $filterTypes = $this->filterTypeResolver->resolve(
            $categories,
            $this->filterCategoryTypes() ?: self::FINDER_DEFAULT_CATEGORY_TYPES,
        );

        $this->view->assignMultiple([
            'data' => $contentElementData,
            'categories' => $categories,
            'filterTypes' => $filterTypes,
            'listUri' => $this->finderListUri(),
            'preselection' => $this->finderPreselection($categories, $filterTypes->getVisible()),
        ]);

        return $this->htmlResponse();
    }

    /**
     * Method injection keeps the constructor, which project subclasses call, unchanged.
     * Final, and named after what it is for, so a subclass cannot collide with it.
     */
    final public function injectFilterRedirectExtensionService(ExtensionService $extensionService): void
    {
        $this->filterRedirectExtensionService = $extensionService;
    }

    /**
     * Method injection for the same reason as {@see injectFilterRedirectExtensionService()}.
     */
    final public function injectFilterTypeResolver(FilterTypeResolver $filterTypeResolver): void
    {
        $this->filterTypeResolver = $filterTypeResolver;
    }

    /**
     * The filter types of the element, or the site-wide ones when the element leaves its
     * field empty - Extbase has already dropped an empty field, see
     * `ignoreFlexFormSettingsIfEmpty` in `setup.typoscript`.
     */
    private function filterCategoryTypes(): string
    {
        $filter = $this->settings['filter'] ?? null;
        $categoryTypes = is_array($filter) ? ($filter['categoryTypes'] ?? '') : '';

        return is_string($categoryTypes) ? $categoryTypes : '';
    }

    /**
     * The URI the finder submits to: the list action on the target page, an empty string
     * when there is none. FormEngine submits the page as `pages_<uid>`, and the DataHandler
     * stores the bare uid of the single allowed table.
     *
     * Built here rather than by the form, because a page that cannot be linked - hidden,
     * deleted, access restricted - yields an empty URI without an exception, and a form
     * with an empty action posts to the page it is on, where nothing answers it.
     */
    private function finderListUri(): string
    {
        $listPid = $this->settings['listPid'] ?? 0;
        if (!is_numeric($listPid) || (int)$listPid <= 0) {
            return '';
        }

        return $this->uriBuilder
            ->reset()
            ->setTargetPageUid((int)$listPid)
            ->uriFor('list', [], 'Program', 'AcademicPrograms', 'ProgramList');
    }

    /**
     * The preselected category of each offered type, as the value its select shows. A select
     * holds one value, so the first preselected category of a type in the stored list wins -
     * the category tree of the field stores them in tree order. A category of a type the
     * finder does not offer, and a disabled one - no program in storage carries it -
     * preselect nothing.
     *
     * @param list<string> $offeredTypes
     * @return array<string, string> Type identifier => category uid
     */
    private function finderPreselection(CategoryCollection $categories, array $offeredTypes): array
    {
        $preselectedCategories = $this->settings['preselectedCategories'] ?? '';
        if (!is_scalar($preselectedCategories)) {
            return [];
        }

        $typeOfSelectable = [];
        $categoriesByType = $categories->getAllCategoriesByType();
        foreach ($offeredTypes as $typeIdentifier) {
            foreach ($categoriesByType[$typeIdentifier] ?? [] as $category) {
                if (!$category->isDisabled()) {
                    $typeOfSelectable[$category->getUid()] = $typeIdentifier;
                }
            }
        }

        $preselection = [];
        foreach (GeneralUtility::intExplode(',', (string)$preselectedCategories, true) as $uid) {
            $typeIdentifier = $typeOfSelectable[$uid] ?? null;
            if ($typeIdentifier !== null && !isset($preselection[$typeIdentifier])) {
                $preselection[$typeIdentifier] = (string)$uid;
            }
        }

        return $preselection;
    }

    /**
     * Answers a submission of the filter and sorting form with a `303` to the same action,
     * carrying the selection as GET arguments, so a filtered list has a URL that can be
     * bookmarked, shared and reloaded.
     *
     * The demand is read from the body of the POST only. Extbase merges the arguments of
     * the URL into those of the body, and the URL of a filtered list carries a demand of
     * its own: merged, a category the visitor cleared would come back from the URL. A POST
     * whose body carries no demand of this plugin is another plugin's form and is left
     * alone.
     *
     * The redirect is thrown rather than returned. A returned one reaches the browser on
     * TYPO3 v13 only through `header()`: the response the middlewares see is a `200`, and
     * the whole page renders for nothing. The exception ends the request at the
     * `ResponsePropagation` middleware on v13 and v14 alike.
     *
     * Protected, so a subclass that overrides an action can keep the redirect.
     *
     * @param array<string, mixed> $contentElementData
     * @throws PropagateResponseException
     */
    protected function redirectFilterSubmission(array $contentElementData): void
    {
        if ($this->request->getMethod() !== 'POST') {
            return;
        }
        $pluginNamespace = $this->filterRedirectExtensionService->getPluginNamespace(
            $this->request->getControllerExtensionName(),
            $this->request->getPluginName(),
        );
        $parsedBody = $this->request->getParsedBody();
        $pluginArguments = is_array($parsedBody) ? ($parsedBody[$pluginNamespace] ?? null) : null;
        $demand = is_array($pluginArguments) ? ($pluginArguments['demand'] ?? null) : null;
        if (!is_array($demand)) {
            return;
        }

        /** @var array<string, mixed> $demand */
        $demandObject = $this->programDemandFactory->createDemandObject($demand, $this->settings, $contentElementData);
        throw new PropagateResponseException(
            $this->redirect(
                $this->request->getControllerActionName(),
                null,
                null,
                ['demand' => $this->programDemandFactory->createDemandArguments($demandObject)],
            ),
            1790226084,
        );
    }

    private function getCurrentContentObjectRenderer(): ?ContentObjectRenderer
    {
        return $this->request->getAttribute('currentContentObject');
    }
}
