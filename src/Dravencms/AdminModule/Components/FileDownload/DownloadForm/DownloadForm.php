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

namespace Dravencms\AdminModule\Components\FileDownload\DownloadForm;

use Dravencms\Components\BaseControl\BaseControl;
use Dravencms\Components\BaseForm\BaseFormFactory;
use Dravencms\Model\FileDownload\Entities\Download;
use Dravencms\Model\FileDownload\Entities\DownloadTranslation;
use Dravencms\Model\FileDownload\Repository\DownloadRepository;
use Dravencms\Model\FileDownload\Repository\DownloadTranslationRepository;
use Dravencms\Model\Locale\Repository\LocaleRepository;
use Dravencms\Database\EntityManager;
use Dravencms\Components\BaseForm\Form;
use Nette\Security\User;

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
    
    /** @var User */
    private $user;

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
        User $user,
        DownloadRepository $downloadRepository,
        DownloadTranslationRepository $downloadTranslationRepository,
        LocaleRepository $localeRepository,
        Download $download = null
    ) {
        $this->download = $download;

        $this->baseFormFactory = $baseFormFactory;
        $this->entityManager = $entityManager;
        $this->user = $user;
        $this->downloadRepository = $downloadRepository;
        $this->downloadTranslationRepository = $downloadTranslationRepository;
        $this->localeRepository = $localeRepository;


        if ($this->download) {
            $defaults = [
                'isShowName' => $this->download->isShowName(),
                'identifier' => $this->download->getIdentifier(),
                'pagination' => $this->download->getPagination() 
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
     * @return Form
     */
    protected function createComponentForm(): Form
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

        $form->addInteger('pagination');

        $form->addCheckbox('isShowName');

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
        if (!$this->downloadRepository->isIdentifierFree($values->identifier, $this->download)) {
            $form->addError('Tento identifier je již zabrán.');
        }

        if (!$this->user->isAllowed('fileDownload', 'edit')) {
            $form->addError('Nemáte oprávění editovat download.');
        }
    }

    /**
     * @param Form $form
     * @throws \Exception
     */
    public function editFormSucceeded(Form $form): void
    {
        $values = $form->getValues();

        if ($this->download) {
            $download = $this->download;
            $download->setIsShowName($values->isShowName);
            $download->setPagination($values->pagination ? $values->pagination : null);
        } else {
            $download = new Download($values->identifier, $values->pagination ? $values->pagination : null, $values->isShowName);
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


    public function render(): void
    {
        $template = $this->template;
        $template->activeLocales = $this->localeRepository->getActive();
        $template->setFile(__DIR__ . '/DownloadForm.latte');
        $template->render();
    }
}