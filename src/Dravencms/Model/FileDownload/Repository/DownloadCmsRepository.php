<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\FileDownload\Repository;

use Dravencms\Locale\TLocalizedRepository;
use Dravencms\Model\FileDownload\Entities\Download;
use Gedmo\Translatable\TranslatableListener;
use Kdyby\Doctrine\EntityManager;
use Nette;
use Salamek\Cms\CmsActionOption;
use Salamek\Cms\ICmsActionOption;
use Salamek\Cms\ICmsComponentRepository;
use Salamek\Cms\Models\ILocale;

class DownloadCmsRepository implements ICmsComponentRepository
{
    private $downloadRepository;

    /**
     * DownloadCmsRepository constructor.
     * @param DownloadRepository $downloadRepository
     */
    public function __construct(DownloadRepository $downloadRepository)
    {
        $this->downloadRepository = $downloadRepository;
    }

    /**
     * @param string $componentAction
     * @return ICmsActionOption[]
     */
    public function getActionOptions($componentAction)
    {
        switch ($componentAction)
        {
            case 'Detail':
                $return = [];
                /** @var Download $download */
                foreach ($this->downloadRepository->getAll() AS $download) {
                    $return[] = new CmsActionOption($download->getName(), ['id' => $download->getId()]);
                }
                break;

            default:
                return false;
                break;
        }

        return $return;
    }

    /**
     * @param string $componentAction
     * @param array $parameters
     * @param ILocale $locale
     * @return null|CmsActionOption
     */
    public function getActionOption($componentAction, array $parameters, ILocale $locale)
    {
        $found = $this->downloadRepository->findTranslatedOneBy($this->downloadRepository, $locale, $parameters);

        if ($found)
        {
            return new CmsActionOption($found->getName(), $parameters);
        }

        return null;
    }
}