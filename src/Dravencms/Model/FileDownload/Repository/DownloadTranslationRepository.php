<?php declare(strict_types = 1);
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\FileDownload\Repository;

use Dravencms\Model\FileDownload\Entities\Download;
use Dravencms\Model\FileDownload\Entities\DownloadTranslation;
use Dravencms\Database\EntityManager;
use Dravencms\Model\Locale\Entities\ILocale;

class DownloadTranslationRepository
{

    /** @var \Doctrine\Persistence\ObjectRepository|DownloadTranslation */
    private $downloadTranslationRepository;

    /** @var EntityManager */
    private $entityManager;

    /**
     * DownloadRepository constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->downloadTranslationRepository = $entityManager->getRepository(DownloadTranslation::class);
    }

    /**
     * @param Download $download
     * @param ILocale $locale
     * @return null|DownloadTranslation
     */
    public function getTranslation(Download $download, ILocale $locale): ?DownloadTranslation
    {
        return $this->downloadTranslationRepository->findOneBy(['download' => $download, 'locale' => $locale]);
    }
}