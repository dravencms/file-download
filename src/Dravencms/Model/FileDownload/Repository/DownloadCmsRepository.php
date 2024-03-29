<?php declare(strict_types = 1);
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\FileDownload\Repository;

use Dravencms\Model\FileDownload\Entities\Download;
use Dravencms\Structure\CmsActionOption;
use Dravencms\Structure\ICmsActionOption;
use Dravencms\Structure\ICmsComponentRepository;

class DownloadCmsRepository implements ICmsComponentRepository
{
    /**
     * @var DownloadRepository
     */
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
    public function getActionOptions(string $componentAction)
    {
        switch ($componentAction)
        {
            case 'Detail':
                $return = [];
                /** @var Download $download */
                foreach ($this->downloadRepository->getAll() AS $download) {
                    $return[] = new CmsActionOption($download->getIdentifier(), ['id' => $download->getId()]);
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
     * @return null|CmsActionOption
     */
    public function getActionOption(string $componentAction, array $parameters)
    {
        $found = $this->downloadRepository->getOneByParameters($parameters);

        if ($found)
        {
            return new CmsActionOption($found->getIdentifier(), $parameters);
        }

        return null;
    }
}