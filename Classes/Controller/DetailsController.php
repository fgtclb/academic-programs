<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Controller;

use FGTCLB\AcademicPrograms\Domain\Repository\CategoryRepository;
use FGTCLB\AcademicPrograms\Domain\Repository\ProgramRepository;
use FGTCLB\AcademicPrograms\Factory\DemandFactory;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class DetailsController extends ActionController
{
    public function __construct(
        protected ProgramRepository $programRepository,
        protected CategoryRepository $categoryRepository,
        protected DemandFactory $programDemandFactory
    ) {
    }

    /**
     * @return ResponseInterface
     */
    public function showAction(): ResponseInterface
    {
        $versionInformation = GeneralUtility::makeInstance(Typo3Version::class);

        // With version TYPO3 v12 the access to the content object renderer has changed
        // @see https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/ApiOverview/RequestLifeCycle/RequestAttributes/CurrentContentObject.html
        if (version_compare($versionInformation->getVersion(), '12.0.0', '>=')) {
            $contentObjectRenderer = $this->request->getAttribute('currentContentObject');
        } else {
            $contentObjectRenderer = $this->configurationManager->getContentObject();
        }

        $contentElementData = $contentObjectRenderer->data;
        $program = $this->programRepository->findByUid($contentElementData['pid']);

        $this->view->assignMultiple([
            'program' => $program,
        ]);

        return $this->htmlResponse();
    }
}
