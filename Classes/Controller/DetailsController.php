<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Controller;

use FGTCLB\AcademicPrograms\Domain\Repository\ProgramRepository;
use FGTCLB\AcademicPrograms\Factory\DemandFactory;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class DetailsController extends ActionController
{
    public function __construct(
        protected ProgramRepository $programRepository,
        protected DemandFactory $programDemandFactory
    ) {}

    /**
     * @return ResponseInterface
     */
    public function showAction(): ResponseInterface
    {
        $contentElementData = $this->getContentObject()?->data ?? [];
        $program = $this->programRepository->findByUid((int)($contentElementData['pid'] ?? 0));

        $this->view->assignMultiple([
            'data' => $contentElementData,
            'program' => $program,
        ]);

        return $this->htmlResponse();
    }

    private function getContentObject(): ?ContentObjectRenderer
    {
        return $this->request->getAttribute('currentContentObject');
    }
}
