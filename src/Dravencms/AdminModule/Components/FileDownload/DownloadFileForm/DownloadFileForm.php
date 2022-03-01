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
use Dravencms\Model\File\Repository\StructureFileRepository;
use Dravencms\Model\FileDownload\Repository\DownloadFileTranslationRepository;
use Dravencms\Model\Locale\Repository\LocaleRepository;
use Dravencms\Database\EntityManager;
use Dravencms\Components\BaseForm\Form;
use Nette\Http\FileUpload;
use Dravencms\File\File;
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

    /** @var DownloadFileRepository */
    private $fileRepository;
    
    /** @var StructureFileRepository */
    private $structureFileRepository;

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
     * @param StructureFileRepository $structureFileRepository
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
        DownloadFileRepository $fileRepository,
        DownloadFileTranslationRepository $downloadFileTranslationRepository,
        StructureFileRepository $structureFileRepository,
        StructureRepository $structureRepository,
        LocaleRepository $localeRepository,
        FileStorage $fileStorage,
        File $fileFile,
        Download $download,
        DownloadFile $file = null
    ) {
        $this->download = $download;
        $this->fileFile = $fileFile;

        $this->baseFormFactory = $baseFormFactory;
        $this->entityManager = $entityManager;
        $this->fileRepository = $fileRepository;
        $this->localeRepository = $localeRepository;
        $this->structureFileRepository = $structureFileRepository;
        $this->structureRepository = $structureRepository;
        $this->fileStorage = $fileStorage;
        $this->file = $file;
        $this->downloadFileTranslationRepository = $downloadFileTranslationRepository;


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

        $this['form']->setDefaults($defaults);
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
                ->setRequired('Please enter file name.')
                ->addRule(Form::MAX_LENGTH, 'File name is too long.', 255);

            $container->addTextArea('description');

            $container->addText('structureFile')
                ->setType('number');

            $container->addUpload('file');
        }
        
        $form->addText('identifier')
            ->setRequired('Please fill in unique identifier');

        $form->addNumber('position')
            ->setDisabled(is_null($this->file));

        $form->addSubmit('send');

        $form->onValidate[] = [$this, 'editFormValidate'];
        $form->onSuccess[] = [$this, 'editFormSucceeded'];

        return $form;
    }

    /**
     * @param Form $form
     */
    public function editFormValidate(Form $form): void
    {
        $values = $form->getValues();

        if (!$this->fileRepository->isIdentifierFree($values->identifier, $this->download, $this->file)) {
            $form->addError('Tento identifier je již zabrán.');
        }

        if (!$this->presenter->isAllowed('fileDownload', 'edit')) {
            $form->addError('Nemáte oprávění editovat download file.');
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

            $structureFile = $this->structureFileRepository->getOneById($values->{$activeLocale->getLanguageCode()}->structureFile);

            /** @var FileUpload $fileUpload */
            $fileUpload = $values->{$activeLocale->getLanguageCode()}->file;
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
        $template->fileSelectorPath = $this->fileFile->getFileSelectorPath();
        $template->setFile(__DIR__ . '/DownloadFileForm.latte');
        $template->render();
    }
}