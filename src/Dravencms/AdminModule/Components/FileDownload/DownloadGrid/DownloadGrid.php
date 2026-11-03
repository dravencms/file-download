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

namespace Dravencms\AdminModule\Components\FileDownload\DownloadGrid;

use Dravencms\Components\BaseControl\BaseControl;
use Dravencms\Components\BaseGrid\BaseGridFactory;
use Dravencms\Components\BaseGrid\Grid;
use Dravencms\Locale\CurrentLocaleResolver;
use Dravencms\Model\FileDownload\Repository\DownloadRepository;
use Dravencms\Database\EntityManager;
use Nette\Security\User;
use Contributte\Datagrid\Column\Action\Confirmation\StringConfirmation;

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
    
     /** @var User */
    private $user;

    /** @var CurrentLocale */
    private $currentLocale;

    /**
     * @var array
     */
    public $onDelete = [];

    /**
     * @param DownloadRepository $downloadRepository
     * @param BaseGridFactory $baseGridFactory
     * @param EntityManager $entityManager
     * @param User $user
     * @param CurrentLocaleResolver $currentLocaleResolver
     */
    public function __construct(
        DownloadRepository $downloadRepository,
        BaseGridFactory $baseGridFactory,
        EntityManager $entityManager,
        User $user,
        CurrentLocaleResolver $currentLocaleResolver
    )
    {
        $this->baseGridFactory = $baseGridFactory;
        $this->downloadRepository = $downloadRepository;
        $this->entityManager = $entityManager;
        $this->user = $user;
        $this->currentLocale = $currentLocaleResolver->getCurrentLocale();
    }


    /**
     * @param type $name
     * @return Grid
     */
    public function createComponentDataGrid($name): Grid
    {
        /** @var DataGrid $grid */
        $grid = $this->baseGridFactory->create($this, $name);
        $grid->setWritePermission('fileDownload', 'edit');

        $grid->setDataSource($this->downloadRepository->getDownloadQueryBuilder());


        $grid->addColumnText('identifier', 'fileDownload.admin.field.identifier', 'identifier')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnNumber('pagination', 'fileDownload.admin.field.pagination')
            ->setAlign('center')
            ->setRenderer(function($item) use ($grid){
                return $item->getPagination() ? $item->getPagination() : $grid->getTranslator()->translate('fileDownload.admin.option.noPagination');
            })
            ->setSortable()
            ->setFilterRange();

        $grid->addColumnDateTime('updatedAt', 'fileDownload.admin.field.updated')
            ->setAlign('center')
            ->setFormat($this->currentLocale->getDateTimeFormat())
            ->setSortable()
            ->setFilterDate();


        if ($this->user->isAllowed('fileDownload', 'edit')) {

            $grid->addAction('files', 'fileDownload.admin.action.files', 'files')
                ->setIcon('folder-open')
                ->setTitle('fileDownload.admin.action.files')
                ->setClass('btn btn-sm btn-primary');

            $grid->addAction('edit', '', 'edit')
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
        }

        $grid->addGroupAction('fileDownload.admin.action.delete')->onSelect[] = [$this, 'gridGroupActionDelete'];


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
        $downloads = $this->downloadRepository->getById($id);
        foreach ($downloads AS $download)
        {
            $this->entityManager->remove($download);
        }

        $this->entityManager->flush();

        $this->onDelete();
    }

    public function render(): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/DownloadGrid.latte');
        $template->render();
    }
}
