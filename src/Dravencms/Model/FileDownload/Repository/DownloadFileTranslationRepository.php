<?php declare(strict_types = 1);
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\FileDownload\Repository;

use Dravencms\Model\FileDownload\Entities\DownloadFile;
use Dravencms\Model\FileDownload\Entities\DownloadFileTranslation;
use Dravencms\Model\Locale\Entities\ILocale;
use Dravencms\Database\EntityManager;

class DownloadFileTranslationRepository
{
    /** @var \Doctrine\Persistence\ObjectRepository|DownloadFileTranslation */
    private $downloadFileTranslationRepository;

    /** @var EntityManager */
    private $entityManager;

    /**
     * DownloadFileTranslationRepository constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->downloadFileTranslationRepository = $entityManager->getRepository(DownloadFileTranslation::class);
    }

    /**
     * @param $id
     * @return null|DownloadFileTranslation
     */
    public function getOneById(int $id): ?DownloadFileTranslation
    {
        return $this->downloadFileTranslationRepository->find($id);
    }

    /**
     * @param DownloadFile $downloadFile
     * @param ILocale $locale
     * @return null|DownloadFileTranslation
     */
    public function getTranslation(DownloadFile $downloadFile, ILocale $locale): ?DownloadFileTranslation
    {
        return $this->downloadFileTranslationRepository->findOneBy(['downloadFile' => $downloadFile, 'locale' => $locale]);
    }
}