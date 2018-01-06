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

namespace Dravencms\AdminModule\Components\FileDownload\DownloadGrid;

use Dravencms\Components\BaseControl\BaseControl;
use Dravencms\Components\BaseGrid\BaseGridFactory;
use Dravencms\Locale\CurrentLocaleResolver;
use Dravencms\Model\FileDownload\Repository\DownloadRepository;
use Kdyby\Doctrine\EntityManager;
use Ublaboo\DataGrid\Column\ColumnText;
use Ublaboo\DataGrid\DataGrid;

/**
 * Description of DownloadGrid
 *
 * @author Adam Schubert <adam.schubert@sg1-game.net>
 */
class DownloadGrid extends BaseControl
{

    /** @var BaseGridFactory */
    private $baseGridFactory;

    /** @var DownloadRepository */
    private $downloadRepository;

    /** @var EntityManager */
    private $entityManager;

    /** @var CurrentLocale */
    private $currentLocale;

    /**
     * @var array
     */
    public $onDelete = [];

    /**
     * DownloadGrid constructor.
     * @param DownloadRepository $downloadRepository
     * @param BaseGridFactory $baseGridFactory
     * @param EntityManager $entityManager
     * @param CurrentLocaleResolver $currentLocaleResolver
     */
    public function __construct(
        DownloadRepository $downloadRepository,
        BaseGridFactory $baseGridFactory,
        EntityManager $entityManager,
        CurrentLocaleResolver $currentLocaleResolver
    )
    {
        parent::__construct();

        $this->baseGridFactory = $baseGridFactory;
        $this->downloadRepository = $downloadRepository;
        $this->entityManager = $entityManager;
        $this->currentLocale = $currentLocaleResolver->getCurrentLocale();
    }


    /**
     * @param $name
     * @return \Dravencms\Components\BaseGrid\BaseGrid
     */
    public function createComponentDataGrid($name)
    {
        /** @var DataGrid $grid */
        $grid = $this->baseGridFactory->create($this, $name);

        $grid->setDataSource($this->downloadRepository->getDownloadQueryBuilder());


        $grid->addColumnText('identifier', 'Identifier', 'identifier')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnDateTime('updatedAt', 'Last edit')
            ->addAttributes(['class' => 'text-center'])
            ->setFormat($this->currentLocale->getDateTimeFormat())
            ->setSortable()
            ->setFilterDate();


        if ($this->presenter->isAllowed('fileDownload', 'edit')) {

            $grid->addAction('files', 'Files', 'files')
                ->setIcon('folder-open')
                ->setTitle('Files')
                ->setClass('btn btn-xs btn-primary');

            $grid->addAction('edit', '', 'edit')
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
        }

        $grid->addGroupAction('Smazat')->onSelect[] = [$this, 'gridGroupActionDelete'];


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
        $downloads = $this->downloadRepository->getById($id);
        foreach ($downloads AS $download)
        {
            $this->entityManager->remove($download);
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
        $template->setFile(__DIR__ . '/DownloadGrid.latte');
        $template->render();
    }
}
