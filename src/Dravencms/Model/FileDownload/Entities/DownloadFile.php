<?php declare(strict_types = 1);
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\FileDownload\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Dravencms\Model\Locale\Entities\ILocale;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Dravencms\Database\Attributes\Identifier;
use Nette;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sortable\Sortable;

/**
 * Class DownloadFile
 * @package Dravencms\Model\File\Entities
 * @ORM\Entity
 * @ORM\Table(name="fileDownloadFile",
 *      uniqueConstraints={
 *        @UniqueConstraint(name="identifier_unique",
 *            columns={"identifier", "download_id"})
 *    }))
 */
class DownloadFile
{
    use Nette\SmartObject;
    use Identifier;
    use TimestampableEntity;

    /**
     * @var string
     * @ORM\Column(type="string",length=255,nullable=false)
     */
    private $identifier;
    
    /**
     * @var Download
     * @Gedmo\SortableGroup
     * @ORM\ManyToOne(targetEntity="Download", inversedBy="downloadFiles")
     * @ORM\JoinColumn(name="download_id", referencedColumnName="id")
     */
    private $download;

    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    private $downloadCount;

    /**
     * @var integer
     * @Gedmo\SortablePosition
     * @ORM\Column(type="integer")
     */
    private $position;

    /**
     * @var ArrayCollection|DownloadFileTranslation[]
     * @ORM\OneToMany(targetEntity="DownloadFileTranslation", mappedBy="downloadFile",cascade={"persist", "remove"})
     */
    private $translations;

    /**
     * DownloadFile constructor.
     * @param Download $download
     * @param $identifier
     */
    public function __construct(Download $download, string $identifier)
    {
        $this->download = $download;
        $this->identifier = $identifier;
        $this->downloadCount = 0;
        $this->translations = new ArrayCollection();
    }

    /**
     * @param int $downloadCount
     */
    public function setDownloadCount(int $downloadCount): void
    {
        $this->downloadCount = $downloadCount;
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    /**
     * @param int $position
     */
    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    /**
     * @return int
     */
    public function getDownloadCount(): int
    {
        return $this->downloadCount;
    }

    /**
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @return Download
     */
    public function getDownload(): Download
    {
        return $this->download;
    }

    /**
     * @return ArrayCollection|DownloadFileTranslation[]
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * @param ILocale $locale
     * @return DownloadFileTranslation
     */
    public function getTranslation(ILocale $locale)
    {
        $criteria = Criteria::create()->where(Criteria::expr()->eq("locale", $locale));
        return $this->getTranslations()->matching($criteria)->first();
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }
}
