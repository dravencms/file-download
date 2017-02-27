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

namespace Dravencms\AdminModule\Components\FileDownload\DownloadForm;

use Dravencms\Components\BaseControl\BaseControl;
use Dravencms\Components\BaseForm\BaseFormFactory;
use Dravencms\Model\FileDownload\Entities\Download;
use Dravencms\Model\FileDownload\Entities\DownloadTranslation;
use Dravencms\Model\FileDownload\Repository\DownloadRepository;
use Dravencms\Model\FileDownload\Repository\DownloadTranslationRepository;
use Dravencms\Model\Locale\Repository\LocaleRepository;
use Kdyby\Doctrine\EntityManager;
use Nette\Application\UI\Form;

/**
 * Description of DownloadForm
 *
 * @author Adam Schubert <adam.schubert@sg1-game.net>
 */
class DownloadForm extends BaseControl
{
    /** @var BaseFormFactory */
    private $baseFormFactory;

    /** @var EntityManager */
    private $entityManager;

    /** @var DownloadRepository */
    private $downloadRepository;

    /** @var DownloadTranslationRepository */
    private $downloadTranslationRepository;

    /** @var LocaleRepository */
    private $localeRepository;

    /** @var Download|null */
    private $download = null;

    /** @var array */
    public $onSuccess = [];

    /**
     * DownloadForm constructor.
     * @param BaseFormFactory $baseFormFactory
     * @param EntityManager $entityManager
     * @param DownloadRepository $downloadRepository
     * @param DownloadTranslationRepository $downloadTranslationRepository
     * @param LocaleRepository $localeRepository
     * @param Download|null $download
     */
    public function __construct(
        BaseFormFactory $baseFormFactory,
        EntityManager $entityManager,
        DownloadRepository $downloadRepository,
        DownloadTranslationRepository $downloadTranslationRepository,
        LocaleRepository $localeRepository,
        Download $download = null
    ) {
        parent::__construct();

        $this->download = $download;

        $this->baseFormFactory = $baseFormFactory;
        $this->entityManager = $entityManager;
        $this->downloadRepository = $downloadRepository;
        $this->downloadTranslationRepository = $downloadTranslationRepository;
        $this->localeRepository = $localeRepository;


        if ($this->download) {
            $defaults = [
                'isShowName' => $this->download->isShowName(),
                'identifier' => $this->download->getIdentifier(),
            ];

            foreach ($this->download->getTranslations() AS $translation)
            {
                $defaults[$translation->getLocale()->getLanguageCode()]['name'] = $translation->getName();
                $defaults[$translation->getLocale()->getLanguageCode()]['description'] = $translation->getDescription();
            }
        }
        else{
            $defaults = [
                'isShowName' => false
            ];
        }

        $this['form']->setDefaults($defaults);
    }

    /**
     * @return \Dravencms\Components\BaseForm\BaseForm
     */
    protected function createComponentForm()
    {
        $form = $this->baseFormFactory->create();

        foreach ($this->localeRepository->getActive() as $activeLocale) {
            $container = $form->addContainer($activeLocale->getLanguageCode());
            $container->addText('name')
                ->setRequired('Please enter download name.')
                ->addRule(Form::MAX_LENGTH, 'Download name is too long.', 255);

            $container->addTextArea('description');
        }

        $form->addText('identifier')
            ->setRequired('Please fill in unique identifier');
        $form->addCheckbox('isShowName');

        $form->addSubmit('send');

        $form->onValidate[] = [$this, 'editFormValidate'];
        $form->onSuccess[] = [$this, 'editFormSucceeded'];

        return $form;
    }

    /**
     * @param Form $form
     */
    public function editFormValidate(Form $form)
    {
        $values = $form->getValues();
        if (!$this->downloadRepository->isIdentifierFree($values->identifier, $this->download)) {
            $form->addError('Tento identifier je již zabrán.');
        }

        if (!$this->presenter->isAllowed('fileDownload', 'edit')) {
            $form->addError('Nemáte oprávění editovat download.');
        }
    }

    /**
     * @param Form $form
     * @throws \Exception
     */
    public function editFormSucceeded(Form $form)
    {
        $values = $form->getValues();

        if ($this->download) {
            $download = $this->download;
            $download->setIsShowName($values->isShowName);
        } else {
            $download = new Download($values->identifier, $values->isShowName);
        }

        $this->entityManager->persist($download);
        $this->entityManager->flush();

        foreach ($this->localeRepository->getActive() AS $activeLocale) {
            if ($downloadTranslation = $this->downloadTranslationRepository->getTranslation($download, $activeLocale))
            {
                $downloadTranslation->setName($values->{$activeLocale->getLanguageCode()}->name);
                $downloadTranslation->setDescription($values->{$activeLocale->getLanguageCode()}->description);
            }
            else
            {
                $downloadTranslation = new DownloadTranslation(
                    $download,
                    $activeLocale,
                    $values->{$activeLocale->getLanguageCode()}->name,
                    $values->{$activeLocale->getLanguageCode()}->description
                );
            }

            $this->entityManager->persist($downloadTranslation);
        }

        $this->entityManager->flush();

        $this->onSuccess();
    }


    public function render()
    {
        $template = $this->template;
        $template->activeLocales = $this->localeRepository->getActive();
        $template->setFile(__DIR__ . '/DownloadForm.latte');
        $template->render();
    }
}