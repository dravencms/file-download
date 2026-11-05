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

namespace Dravencms\AdminModule\Components\FileDownload\DownloadFileForm;

use Dravencms\Components\BaseControl\BaseControl;
use Dravencms\Components\BaseForm\BaseFormFactory;
use Dravencms\Model\File\Entities\Structure;
use Dravencms\Model\File\Repository\StructureRepository;
use Dravencms\Model\FileDownload\Entities\Download;
use Dravencms\Model\FileDownload\Entities\DownloadFile;
use Dravencms\Model\FileDownload\Entities\DownloadFileTranslation;
use Dravencms\Model\FileDownload\Repository\DownloadFileRepository;
use Dravencms\Model\FileDownload\Repository\DownloadFileTranslationRepository;
use Dravencms\Model\Locale\Repository\LocaleRepository;
use Dravencms\Database\EntityManager;
use Dravencms\Components\BaseForm\Form;
use Nette\Http\FileUpload;
use Dravencms\File\File;
use Nette\Security\User;

use Salamek\Files\FileStorage;

/**
 * Description of DownloadFileForm
 *
 * @author Adam Schubert <adam.schubert@sg1-game.net>
 */
class DownloadFileForm extends BaseControl
{
    /** @var BaseFormFactory */
    private $baseFormFactory;

    /** @var EntityManager */
    private $entityManager;
    
    /** @var User */
    private $user;

    /** @var DownloadFileRepository */
    private $fileRepository;
    
    /** @var LocaleRepository */
    private $localeRepository;

    /** @var DownloadFileTranslationRepository */
    private $downloadFileTranslationRepository;

    /** @var StructureRepository */
    private $structureRepository;

    /** @var Download */
    private $download;

    /** @var FileStorage */
    private $fileStorage;

    /** @var File */
    private $fileFile;

    /** @var DownloadFile|null */
    private $file = null;

    /** @var array */
    public $onSuccess = [];

    /**
     * DownloadFileForm constructor.
     * @param BaseFormFactory $baseFormFactory
     * @param EntityManager $entityManager
     * @param DownloadFileRepository $fileRepository
     * @param DownloadFileTranslationRepository $downloadFileTranslationRepository
     * @param StructureRepository $structureRepository
     * @param LocaleRepository $localeRepository
     * @param FileStorage $fileStorage
     * @param File $fileFile
     * @param Download $download
     * @param DownloadFile|null $file
     */
    public function __construct(
        BaseFormFactory $baseFormFactory,
        EntityManager $entityManager,
        User $user,
        DownloadFileRepository $fileRepository,
        DownloadFileTranslationRepository $downloadFileTranslationRepository,
        StructureRepository $structureRepository,
        LocaleRepository $localeRepository,
        FileStorage $fileStorage,
        File $fileFile,
        Download $download,
        ?DownloadFile $file = null
    ) {
        $this->download = $download;
        $this->fileFile = $fileFile;

        $this->baseFormFactory = $baseFormFactory;
        $this->entityManager = $entityManager;
        $this->user = $user;
        $this->fileRepository = $fileRepository;
        $this->localeRepository = $localeRepository;
        $this->structureRepository = $structureRepository;
        $this->fileStorage = $fileStorage;
        $this->file = $file;
        $this->downloadFileTranslationRepository = $downloadFileTranslationRepository;
    }

    /**
     * @return Form
     */
    protected function createComponentForm(): Form
    {
        $form = $this->baseFormFactory->create();

        foreach ($this->localeRepository->getActive() as $activeLocale) {
            $container = $form->addContainer($activeLocale->getLanguageCode());
            $container->addText('name')
                ->setRequired('fileDownload.admin.validation.fileNameRequired')
                ->addRule(Form::MAX_LENGTH, 'fileDownload.admin.validation.fileNameTooLong', 255);

            $container->addTextArea('description');

            $this->fileFile->addSelector($container, 'structureFile', 'file.selectFile', null, true)->setExistingFile($this->file ? $this->downloadFileTranslationRepository->getTranslation($this->file, $activeLocale)?->getStructureFile() : null);

        }
        
        $form->addText('identifier')
            ->setRequired('fileDownload.admin.validation.identifierRequired');

        $form->addText('position')->setNullable()->addRule(Form::INTEGER, 'fileDownload.admin.validation.positionInteger')
            ->setDisabled(is_null($this->file));

        $form->addSubmit('send', 'fileDownload.admin.action.save');

        $form->onValidate[] = [$this, 'editFormValidate'];
        $form->onSuccess[] = [$this, 'editFormSucceeded'];

        if ($this->file) {

            $defaults = [
                'position' => $this->file->getPosition(),
                'identifier' => $this->file->getIdentifier()
            ];

            foreach ($this->file->getTranslations() AS $translation)
            {
                $defaults[$translation->getLocale()->getLanguageCode()]['name'] = $translation->getName();
                $defaults[$translation->getLocale()->getLanguageCode()]['description'] = $translation->getDescription();
                $defaults[$translation->getLocale()->getLanguageCode()]['structureFile'] = ($translation->getStructureFile() ? $translation->getStructureFile()->getId() : null);
            }

        }
        else{
            $defaults = [];
        }

        $form->setDefaults($defaults);

        return $form;
    }

    /**
     * @param Form $form
     */
    public function editFormValidate(Form $form): void
    {
        $values = $form->getValues();

        if (!$this->fileRepository->isIdentifierFree($values->identifier, $this->download, $this->file)) {
            $form->addError('fileDownload.admin.validation.identifierTaken');
        }

        if (!$this->user->isAllowed('fileDownload', 'edit')) {
            $form->addError('fileDownload.admin.validation.fileForbidden');
        }
    }

    /**
     * @param Form $form
     * @throws \Exception
     */
    public function editFormSucceeded(Form $form): void
    {
        $values = $form->getValues();
        
        if ($this->file) {
            $file = $this->file;
            $file->setPosition($values->position);
            $file->setIdentifier($values->identifier);
        } else {
            $file = new DownloadFile($this->download, $values->identifier);
        }

        $this->entityManager->persist($file);

        $this->entityManager->flush();

        foreach ($this->localeRepository->getActive() AS $activeLocale) {

            $selection = $values->{$activeLocale->getLanguageCode()}->structureFile;
            $structureFile = $selection->file;

            /** @var FileUpload $fileUpload */
            $fileUpload = $selection->upload;
            if ($fileUpload->isOk()) {
                $structureName = 'Download';
                if (!$structure = $this->structureRepository->getOneByName($structureName)) {
                    $structure = new Structure($structureName);
                    $this->entityManager->persist($structure);
                    $this->entityManager->flush();
                }
                $structureFile = $this->fileStorage->processFile($fileUpload, $structure);
            }


            if ($downloadFileTranslation = $this->downloadFileTranslationRepository->getTranslation($file, $activeLocale))
            {
                $downloadFileTranslation->setName($values->{$activeLocale->getLanguageCode()}->name);
                $downloadFileTranslation->setDescription($values->{$activeLocale->getLanguageCode()}->description);
                $downloadFileTranslation->setStructureFile($structureFile);
            }
            else
            {
                $downloadFileTranslation = new DownloadFileTranslation(
                    $file,
                    $activeLocale,
                    $values->{$activeLocale->getLanguageCode()}->name,
                    $values->{$activeLocale->getLanguageCode()}->description,
                    $structureFile
                );
            }

            $this->entityManager->persist($downloadFileTranslation);
        }

        $this->entityManager->flush();

        $this->onSuccess($file);
    }


    public function render(): void
    {
        $template = $this->template;
        $template->activeLocales = $this->localeRepository->getActive();

        $template->setFile(__DIR__ . '/DownloadFileForm.latte');
        $template->render();
    }
}
