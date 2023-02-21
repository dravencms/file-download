<?php declare(strict_types = 1);

namespace Dravencms\FrontModule\Components\FileDownload\Download\Detail;

use Dravencms\Components\BaseControl\BaseControl;
use Dravencms\Locale\CurrentLocaleResolver;
use Dravencms\Model\FileDownload\Repository\DownloadFileRepository;
use Dravencms\Model\FileDownload\Repository\DownloadFileTranslationRepository;
use Dravencms\Model\FileDownload\Repository\DownloadRepository;
use Dravencms\Model\FileDownload\Repository\DownloadTranslationRepository;
use Dravencms\Components\BasePaginator\BasePaginatorFactory;
use Dravencms\Components\BasePaginator\BasePaginator;
use Dravencms\Structure\ICmsActionOption;
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

    /** @var BasePaginatorFactory */
    private $basePaginatorFactory;

    /** @var FileStorage */
    private $fileStorage;

    /**
     * Detail constructor.
     * @param ICmsActionOption $cmsActionOption
     * @param DownloadRepository $downloadRepository
     * @param DownloadTranslationRepository $downloadTranslationRepository
     * @param DownloadFileRepository $downloadFileRepository
     * @param DownloadFileTranslationRepository $downloadFileTranslationRepository
     * @param CurrentLocaleResolver $currentLocaleResolver
     * @param FileStorage $fileStorage
     */
    public function __construct(
        ICmsActionOption $cmsActionOption,
        DownloadRepository $downloadRepository,
        DownloadTranslationRepository $downloadTranslationRepository,
        DownloadFileRepository $downloadFileRepository,
        DownloadFileTranslationRepository $downloadFileTranslationRepository,
        CurrentLocaleResolver $currentLocaleResolver,
        BasePaginatorFactory $basePaginatorFactory,
        FileStorage $fileStorage
    )
    {
        $this->cmsActionOption = $cmsActionOption;
        $this->downloadRepository = $downloadRepository;
        $this->downloadFileRepository = $downloadFileRepository;
        $this->downloadTranslationRepository = $downloadTranslationRepository;
        $this->downloadFileTranslationRepository = $downloadFileTranslationRepository;
        $this->fileStorage = $fileStorage;
        $this->basePaginatorFactory = $basePaginatorFactory;
        $this->currentLocale = $currentLocaleResolver->getCurrentLocale();
    }


    public function render(): void
    {
        $template = $this->template;
        $download = $this->downloadRepository->getOneById($this->cmsActionOption->getParameter('id'));

        $pagination = $download->getPagination();
        if ($pagination)
        {
            $visualPaginator = $this['visualPaginator'];

            $paginator = $visualPaginator->getPaginator();
            $paginator->itemsPerPage = $pagination;
            $paginator->itemCount = $download->getDownloadFiles()->count();

            $template->downloadFiles = $this->downloadFileRepository->getByDownload($download, $paginator->itemsPerPage, $paginator->offset);
        }
        else
        {
            $template->downloadFiles = $download->getDownloadFiles();
        }
        
        $template->download = $download;
        $template->currentLocale = $this->currentLocale;



        $template->setFile($this->cmsActionOption->getTemplatePath(__DIR__.'/detail.latte'));
        $template->render();
    }

    /**
     * @param $downloadFileTranslationId
     */
    public function handleDownload(int $downloadFileTranslationId): void
    {
        $downloadFileTranslation = $this->downloadFileTranslationRepository->getOneById($downloadFileTranslationId);
        if (!$downloadFileTranslation)
        {
            $this->presenter->flashMessage('File not found!', 'alert-danger');
        }
        else
        {
            $this->downloadFileRepository->logDownload($downloadFileTranslation->getDownloadFile());
            $response = $this->fileStorage->downloadFile($downloadFileTranslation->getStructureFile());
            $this->presenter->sendResponse($response);
        }
    }

    /**
     * @return BasePaginator
     */
    protected function createComponentVisualPaginator(): BasePaginator
    {
        $control = $this->basePaginatorFactory->create();
        $control->setTemplateFile(__DIR__.'/paginator.latte');
        //$control->setTemplateFile('bootstrap.latte');

        $control->onShowPage[] = (function ($component, $page) {
            if ($this->presenter->isAjax()){
                $this->redrawControl('detail');
            }
        });

        return $control;
    }
}
