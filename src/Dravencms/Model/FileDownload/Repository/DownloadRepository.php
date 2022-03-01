<?php declare(strict_types = 1);
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\FileDownload\Repository;

use Dravencms\Model\FileDownload\Entities\Download;
use Dravencms\Database\EntityManager;

class DownloadRepository
{
    /** @var \Doctrine\Persistence\ObjectRepository|Download */
    private $downloadRepository;

    /** @var EntityManager */
    private $entityManager;

    /**
     * DownloadRepository constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->downloadRepository = $entityManager->getRepository(Download::class);
    }

    /**
     * @param $id
     * @return null|Download
     */
    public function getOneById(int $id): ?Download
    {
        return $this->downloadRepository->find($id);
    }

    /**
     * @param $id
     * @return Download[]
     */
    public function getById($id)
    {
        return $this->downloadRepository->findBy(['id' => $id]);
    }

    /**
     * @param array $parameters
     * @return Download|null
     */
    public function getOneByParameters(array $parameters): ?Download
    {
        return $this->downloadRepository->findOneBy($parameters);
    }

    /**
     * @return \Kdyby\Doctrine\QueryBuilder
     */
    public function getDownloadQueryBuilder()
    {
        $qb = $this->downloadRepository->createQueryBuilder('d')
            ->select('d');
        return $qb;
    }

    /**
     * @return array
     */
    public function getAll()
    {
        return $this->downloadRepository->findAll();
    }

    /**
     * @param $identifier
     * @param Download|null $downloadIgnore
     * @return boolean
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function isIdentifierFree(string $identifier, Download $downloadIgnore = null): bool
    {
        $qb = $this->downloadRepository->createQueryBuilder('d')
            ->select('d')
            ->where('d.identifier = :identifier')
            ->setParameters([
                'identifier' => $identifier,
            ]);

        if ($downloadIgnore)
        {
            $qb->andWhere('d != :downloadIgnore')
                ->setParameter('downloadIgnore', $downloadIgnore);
        }

        $query = $qb->getQuery();
        return (is_null($query->getOneOrNullResult()));
    }
}