<?php

namespace Dravencms\FrontModule\Components\FileDownload\Download\Detail;

use Dravencms\Components\BaseControl\BaseControl;
use Dravencms\Locale\CurrentLocale;
use Dravencms\Model\FileDownload\Entities\DownloadFileTranslation;
use Dravencms\Model\FileDownload\Repository\DownloadFileRepository;
use Dravencms\Model\FileDownload\Repository\DownloadFileTranslationRepository;
use Dravencms\Model\FileDownload\Repository\DownloadRepository;
use Dravencms\Model\FileDownload\Repository\DownloadTranslationRepository;
use IPub\VisualPaginator\Components\Control;
use Salamek\Cms\ICmsActionOption;
use Salamek\Files\FileStorage;

class Detail extends BaseControl
{
    /** @var ICmsActionOption */
    private $cmsActionOption;

    /** @var DownloadRepository */
    private $downloadRepository;

    /** @var DownloadTranslationRepository */
    private $downloadTranslationRepository;

    /** @var DownloadFileTranslationRepository */
    private $downloadFileTranslationRepository;

    /** @var DownloadFileRepository */
    private $downloadFileRepository;

    /** @var CurrentLocale */
    private $currentLocale;

    /** @var FileStorage */
    private $fileStorage;

    public function __construct(
        ICmsActionOption $cmsActionOption,
        DownloadRepository $downloadRepository,
        DownloadTranslationRepository $downloadTranslationRepository,
        DownloadFileRepository $downloadFileRepository,
        DownloadFileTranslationRepository $downloadFileTranslationRepository,
        CurrentLocale $currentLocale,
        FileStorage $fileStorage
    )
    {
        parent::__construct();
        $this->cmsActionOption = $cmsActionOption;
        $this->downloadRepository = $downloadRepository;
        $this->downloadFileRepository = $downloadFileRepository;
        $this->downloadTranslationRepository = $downloadTranslationRepository;
        $this->downloadFileTranslationRepository = $downloadFileTranslationRepository;
        $this->fileStorage = $fileStorage;
        $this->currentLocale = $currentLocale;
    }


    public function render()
    {
        $template = $this->template;
        $download = $this->downloadRepository->getOneById($this->cmsActionOption->getParameter('id'));

        $visualPaginator = $this['visualPaginator'];

        $paginator = $visualPaginator->getPaginator();
        $paginator->itemsPerPage = 10;
        $paginator->itemCount = $download->getDownloadFiles()->count();

        $template->downloadTranslation = $this->downloadTranslationRepository->getTranslation($download, $this->currentLocale);

        $fileTranslations = [];
        foreach ($this->downloadFileRepository->getByDownload($download, $paginator->itemsPerPage, $paginator->offset) AS $item)
        {
            $fileTranslations[] = $this->downloadFileTranslationRepository->getTranslation($item, $this->currentLocale);
        }
        
        $template->fileTranslations = $fileTranslations;

        $template->setFile(__DIR__.'/detail.latte');
        $template->render();
    }

    /**
     * @param $downloadFileId
     */
    public function handleDownload($downloadFileId)
    {
        $downloadFile = $this->downloadFileRepository->getOneById($downloadFileId);
        if (!$downloadFile)
        {
            $this->presenter->flashMessage('File not found!', 'alert-danger');
        }
        else
        {
            $this->downloadFileRepository->logDownload($downloadFile);
            $response = $this->fileStorage->downloadFile($downloadFile->getStructureFile());
            $this->presenter->sendResponse($response);
        }
    }

    /**
     * @return Control
     */
    protected function createComponentVisualPaginator()
    {
        // Init visual paginator
        $control = new Control();
        $control->setTemplateFile('bootstrap.latte');

        $control->onShowPage[] = (function ($component, $page) {
            if ($this->presenter->isAjax()){
                $this->redrawControl('detail');
            }
        });

        return $control;
    }
}
