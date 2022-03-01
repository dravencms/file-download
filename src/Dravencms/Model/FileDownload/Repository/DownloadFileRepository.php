<?php declare(strict_types = 1);
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\FileDownload\Repository;

use Dravencms\Model\FileDownload\Entities\Download;
use Dravencms\Model\FileDownload\Entities\DownloadFile;
use Dravencms\Database\EntityManager;


class DownloadFileRepository
{
    /** @var \Doctrine\Persistence\ObjectRepository|DownloadFile */
    private $downloadFileRepository;

    /** @var EntityManager */
    private $entityManager;

    /**
     * DownloadRepository constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->downloadFileRepository = $entityManager->getRepository(DownloadFile::class);
    }

    /**
     * @param $id
     * @return null|DownloadFile
     */
    public function getOneById(int $id): ?DownloadFile
    {
        return $this->downloadFileRepository->find($id);
    }

    /**
     * @param $id
     * @return DownloadFile[]
     */
    public function getById($id)
    {
        return $this->downloadFileRepository->findBy(['id' => $id]);
    }

    /**
     * @param Download $download
     * @param null $limit
     * @param null $offset
     * @return DownloadFile[]
     */
    public function getByDownload(Download $download, int $limit = null, int $offset = null)
    {
        return $this->downloadFileRepository->findBy(['download' => $download],[], $limit, $offset);
    }

    /**
     * @param Download $download
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getDownloadFileQueryBuilder(Download $download)
    {
        $qb = $this->downloadFileRepository->createQueryBuilder('df')
            ->select('df')
            ->where('df.download = :download')
            ->setParameter('download', $download);
        return $qb;
    }

    /**
     * @param $identifier
     * @param Download $download
     * @param DownloadFile|null $downloadFileIgnore
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function isIdentifierFree(string $identifier, Download $download, DownloadFile $downloadFileIgnore = null): bool
    {
        $qb = $this->downloadFileRepository->createQueryBuilder('df')
            ->select('df')
            ->where('df.identifier = :identifier')
            ->andWhere('df.download = :download')
            ->setParameters([
                'identifier' => $identifier,
                'download' => $download
            ]);

        if ($downloadFileIgnore)
        {
            $qb->andWhere('df != :downloadFileIgnore')
                ->setParameter('downloadFileIgnore', $downloadFileIgnore);
        }

        $query = $qb->getQuery();

        return (is_null($query->getOneOrNullResult()));
    }

    /**
     * @param DownloadFile $downloadFile
     * @throws \Exception
     */
    public function logDownload(DownloadFile $downloadFile): void
    {
        $downloadFile->setDownloadCount($downloadFile->getDownloadCount() + 1);
        $this->entityManager->persist($downloadFile);
        $this->entityManager->flush();
    }
}