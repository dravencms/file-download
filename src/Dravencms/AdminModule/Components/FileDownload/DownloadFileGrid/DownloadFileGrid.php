<?php

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
use Dravencms\Locale\CurrentLocaleResolver;
use Dravencms\Model\FileDownload\Entities\Download;
use Dravencms\Model\FileDownload\Repository\DownloadFileRepository;
use Kdyby\Doctrine\EntityManager;
use Ublaboo\DataGrid\DataGrid;

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

    /** @var CurrentLocale */
    private $currentLocale;

    /** @var Download */
    private $download;

    /**
     * @var array
     */
    public $onDelete = [];

    /**
     * DownloadFileGrid constructor.
     * @param Download $download
     * @param DownloadFileRepository $downloadFileRepository
     * @param BaseGridFactory $baseGridFactory
     * @param EntityManager $entityManager
     * @param CurrentLocaleResolver $currentLocaleResolver
     */
    public function __construct(
        Download $download,
        DownloadFileRepository $downloadFileRepository,
        BaseGridFactory $baseGridFactory,
        EntityManager $entityManager,
        CurrentLocaleResolver $currentLocaleResolver
    )
    {
        parent::__construct();

        $this->baseGridFactory = $baseGridFactory;
        $this->downloadFileRepository = $downloadFileRepository;
        $this->entityManager = $entityManager;
        $this->currentLocale = $currentLocaleResolver->getCurrentLocale();
        $this->download = $download;
    }


    /**
     * @param $name
     * @return \Dravencms\Components\BaseGrid\BaseGrid
     */
    public function createComponentGrid($name)
    {
        /** @var DataGrid $grid */
        $grid = $this->baseGridFactory->create($this, $name);

        $grid->setDefaultSort(['position' => 'ASC']);

        $grid->setDataSource($this->downloadFileRepository->getDownloadFileQueryBuilder($this->download));


        $grid->addColumnText('identifier', 'Identifier', 'identifier')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnNumber('downloadCount', 'Download count')
            ->setAlign('center')
            ->setFilterRange();

        $grid->addColumnDateTime('updatedAt', 'Last edit')
            ->addAttributes(['class' => 'text-center'])
            ->setFormat($this->currentLocale->getDateTimeFormat())
            ->setSortable()
            ->setFilterDate();

        $grid->addColumnNumber('position', 'Position')
            ->setAlign('center')
            ->setFilterRange();


        if ($this->presenter->isAllowed('fileDownload', 'edit')) {
            
            $grid->addAction('edit', '', 'edit', ['fileId' => 'id', 'downloadId' => 'download.id'])
                ->setIcon('pencil')
                ->setTitle('Upravit')
                ->setClass('btn btn-xs btn-primary');
        }

        if ($this->presenter->isAllowed('fileDownload', 'delete')) {
            $grid->addAction('delete', '', 'delete!')
                ->setIcon('trash')
                ->setTitle('Smazat')
                ->setClass('btn btn-xs btn-danger ajax')
                ->setConfirm('Do you really want to delete row %s?', 'identifier');
            
            $grid->addGroupAction('Smazat')->onSelect[] = [$this, 'gridGroupActionDelete'];
        }

        return $grid;
    }

    /**
     * @param array $ids
     */
    public function gridGroupActionDelete(array $ids)
    {
        $this->handleDelete($ids);
    }

    /**
     * @param $id
     * @throws \Exception
     */
    public function handleDelete($id)
    {
        $downloadFiles = $this->downloadFileRepository->getById($id);
        foreach ($downloadFiles AS $downloadFile)
        {
            $this->entityManager->remove($downloadFile);
        }

        $this->entityManager->flush();
        
        
        if ($this->presenter->isAjax()) {
            $this['grid']->reload();
        } else {
            $this->onDelete();
        }
    }

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/DownloadFileGrid.latte');
        $template->render();
    }
}
