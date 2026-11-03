<?php declare(strict_types = 1);

/*
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301  USA
 */

namespace Dravencms\AdminModule\Components\FileDownload\DownloadFileGrid;

use Dravencms\Components\BaseControl\BaseControl;
use Dravencms\Components\BaseGrid\BaseGridFactory;
use Dravencms\Components\BaseGrid\Grid;
use Dravencms\Locale\CurrentLocaleResolver;
use Dravencms\Model\FileDownload\Entities\Download;
use Dravencms\Model\FileDownload\Repository\DownloadFileRepository;
use Dravencms\Database\EntityManager;
use Nette\Security\User;
use Contributte\Datagrid\Column\Action\Confirmation\StringConfirmation;

/**
 * Description of DownloadFileGrid
 *
 * @author Adam Schubert <adam.schubert@sg1-game.net>
 */
class DownloadFileGrid extends BaseControl
{
    /** @var BaseGridFactory */
    private $baseGridFactory;

    /** @var DownloadFileRepository */
    private $downloadFileRepository;

    /** @var EntityManager */
    private $entityManager;
    
    /** @var User */
    private $user;

    /** @var CurrentLocale */
    private $currentLocale;

    /** @var Download */
    private $download;

    /**
     * @var array
     */
    public $onDelete = [];

    /**
     * @param Download $download
     * @param DownloadFileRepository $downloadFileRepository
     * @param BaseGridFactory $baseGridFactory
     * @param EntityManager $entityManager
     * @param User $user
     * @param CurrentLocaleResolver $currentLocaleResolver
     */
    public function __construct(
        Download $download,
        DownloadFileRepository $downloadFileRepository,
        BaseGridFactory $baseGridFactory,
        EntityManager $entityManager,
        User $user,
        CurrentLocaleResolver $currentLocaleResolver
    )
    {
        $this->baseGridFactory = $baseGridFactory;
        $this->downloadFileRepository = $downloadFileRepository;
        $this->entityManager = $entityManager;
        $this->user = $user;
        $this->currentLocale = $currentLocaleResolver->getCurrentLocale();
        $this->download = $download;
    }


    /**
     * @param string $name
     * @return Grid
     */
    public function createComponentGrid(string $name): Grid
    {
        /** @var DataGrid $grid */
        $grid = $this->baseGridFactory->create($this, $name);
        $grid->setWritePermission('fileDownload', 'edit');

        $grid->setDefaultSort(['position' => 'ASC']);

        $grid->setDataSource($this->downloadFileRepository->getDownloadFileQueryBuilder($this->download));


        $grid->addColumnText('identifier', 'fileDownload.admin.field.identifier', 'identifier')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnNumber('downloadCount', 'fileDownload.admin.field.downloadCount')
            ->setAlign('center')
            ->setFilterRange();

        $grid->addColumnDateTime('updatedAt', 'fileDownload.admin.field.updated')
            ->setAlign('center')
            ->setFormat($this->currentLocale->getDateTimeFormat())
            ->setSortable()
            ->setFilterDate();

        $grid->addColumnNumber('position', 'fileDownload.admin.field.position')
            ->setAlign('center')
            ->setFilterRange();


        if ($this->user->isAllowed('fileDownload', 'edit')) {
            
            $grid->addAction('editFile', '', 'editFile', ['fileId' => 'id', 'downloadId' => 'download.id'])
                ->setIcon('pencil')
                ->setTitle('fileDownload.admin.action.edit')
                ->setClass('btn btn-sm btn-primary');
        }

        if ($this->user->isAllowed('fileDownload', 'delete')) {
            $grid->addAction('delete', '', 'delete!')
                ->setIcon('trash')
                ->setTitle('fileDownload.admin.action.delete')
                ->setClass('btn btn-sm btn-danger ajax')
                ->setConfirmation(new StringConfirmation('fileDownload.admin.confirm.delete', 'identifier'));
            
            $grid->addGroupAction('fileDownload.admin.action.delete')->onSelect[] = [$this, 'gridGroupActionDelete'];
        }

        return $grid;
    }

    /**
     * @param array $ids
     */
    public function gridGroupActionDelete(array $ids): void
    {
        $this->handleDelete($ids);
    }

    /**
     * @param $id
     * @throws \Exception
     */
    #[\Dravencms\User\Attributes\IsAllowed('fileDownload', 'delete')]
    #[\Dravencms\Security\Mutation]
    public function handleDelete($id): void
    {
        $this->requirePermission('fileDownload', 'delete');
        $downloadFiles = $this->downloadFileRepository->getById($id);
        foreach ($downloadFiles AS $downloadFile)
        {
            $this->entityManager->remove($downloadFile);
        }

        $this->entityManager->flush();
        
        
        if ($this->getPresenter()->isAjax()) {
            $this['grid']->reload();
        } else {
            $this->onDelete();
        }
    }

    public function render(): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/DownloadFileGrid.latte');
        $template->render();
    }
}
