<?php declare(strict_types = 1);
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\AdminModule\FileModule;


use Dravencms\AdminModule\Components\FileDownload\DownloadFileForm\DownloadFileFormFactory;
use Dravencms\AdminModule\Components\FileDownload\DownloadFileForm\DownloadFileForm;
use Dravencms\AdminModule\Components\FileDownload\DownloadFileGrid\DownloadFileGridFactory;
use Dravencms\AdminModule\Components\FileDownload\DownloadFileGrid\DownloadFileGrid;
use Dravencms\AdminModule\Components\FileDownload\DownloadForm\DownloadFormFactory;
use Dravencms\AdminModule\Components\FileDownload\DownloadForm\DownloadForm;
use Dravencms\AdminModule\Components\FileDownload\DownloadGrid\DownloadGridFactory;
use Dravencms\AdminModule\Components\FileDownload\DownloadGrid\DownloadGrid;
use Dravencms\AdminModule\SecuredPresenter;
use Dravencms\Model\FileDownload\Entities\Download;
use Dravencms\Model\FileDownload\Entities\DownloadFile;
use Dravencms\Model\FileDownload\Repository\DownloadFileRepository;
use Dravencms\Model\FileDownload\Repository\DownloadRepository;

#[\Dravencms\User\Attributes\IsAllowed('fileDownload', 'edit')]
class DownloadPresenter extends SecuredPresenter
{
    /** @var DownloadRepository @inject */
    public $downloadRepository;

    /** @var DownloadFileRepository @inject */
    public $fileRepository;

    /** @var DownloadFormFactory @inject */
    public $downloadFormFactory;

    /** @var DownloadGridFactory @inject */
    public $downloadGridFactory;

    /** @var DownloadFileFormFactory @inject */
    public $fileFormFactory;

    /** @var DownloadFileGridFactory @inject */
    public $fileGridFactory;


    /** @var null|Download */
    private $download = null;

    /** @var null|DownloadFile */
    private $file = null;

    #[\Dravencms\User\Attributes\IsAllowed('fileDownload', 'edit')]
    public function renderDefault(): void
    {
        $this->template->h1 = $this->translator->translate('fileDownload.admin.title.downloads');
    }

    #[\Dravencms\User\Attributes\IsAllowed('fileDownload', 'edit')]
    public function actionEdit(?int $id = null): void
    {
        if ($id) {
            $this->template->h1 = $this->translator->translate('fileDownload.admin.title.editDownload');
            $download = $this->downloadRepository->getOneById($id);
            if (!$download) {
                $this->error();
            }

            $this->download = $download;
        } else {
            $this->template->h1 = $this->translator->translate('fileDownload.admin.title.newDownload');
        }
    }

    /**
     * @param $id
     */
    #[\Dravencms\User\Attributes\IsAllowed('fileDownload', 'edit')]
    public function actionFiles(int $id): void
    {
        $this->download = $this->downloadRepository->getOneById($id);
        $this->template->download = $this->download;
        $this->template->h1 = $this->translator->translate('fileDownload.admin.title.files');
    }

    /**
     * @param $downloadId
     * @param null $fileId
     * @throws \Nette\Application\BadRequestException
     */
    #[\Dravencms\User\Attributes\IsAllowed('fileDownload', 'edit')]
    public function actionEditFile(int $downloadId, ?int $fileId = null): void
    {
        $this->download = $this->downloadRepository->getOneById($downloadId);
        if ($fileId)
        {
            $file = $this->fileRepository->getOneById($fileId);
            if (!$file) {
                $this->error();
            }

            $this->file = $file;
            $this->template->h1 = $this->translator->translate('fileDownload.admin.title.editFile');
        }
        else
        {
            $this->template->h1 = $this->translator->translate('fileDownload.admin.title.newFile');
        }
    }

    /**
     * @return DownloadForm
     */
    public function createComponentFormDownload(): DownloadForm
    {
        $control = $this->downloadFormFactory->create($this->download);
        $control->onSuccess[] = function()
        {
            $this->flashMessage($this->translator->translate('fileDownload.admin.flash.downloadSaved'), 'alert-success');
            $this->redirect('Download:');
        };
        return $control;
    }

    /**
     * @return DownloadGrid
     */
    public function createComponentGridDownload(): DownloadGrid
    {
        $control = $this->downloadGridFactory->create();
        $control->onDelete[] = function()
        {
            $this->flashMessage($this->translator->translate('fileDownload.admin.flash.downloadDeleted'), 'alert-success');
            $this->redirect('Download:');
        };
        return $control;
    }

    /**
     * @return DownloadFileForm
     */
    public function createComponentFormFile(): DownloadFileForm
    {
        $control = $this->fileFormFactory->create($this->download, $this->file);
        $control->onSuccess[] = function()
        {
            $this->flashMessage($this->translator->translate('fileDownload.admin.flash.fileSaved'), 'alert-success');
            $this->redirect('Download:files', $this->download->getId());
        };
        return $control;
    }

    /**
     * @return DownloadFileGrid
     */
    public function createComponentGridFile(): DownloadFileGrid
    {
        $control = $this->fileGridFactory->create($this->download);
        $control->onDelete[] = function()
        {
            $this->flashMessage($this->translator->translate('fileDownload.admin.flash.fileDeleted'), 'alert-success');
            $this->redirect('Download:files', $this->download->getId());
        };
        return $control;
    }
}
