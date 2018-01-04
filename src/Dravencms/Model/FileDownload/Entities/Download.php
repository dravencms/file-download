<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\FileDownload\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Dravencms\Model\Locale\Entities\ILocale;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Nette;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * Class Download
 * @package Dravencms\Model\File\Entities
 * @ORM\Entity
 * @ORM\Table(name="fileDownload")
 */
class Download
{
    use Nette\SmartObject;
    use Identifier;
    use TimestampableEntity;

    /**
     * @var string
     * @ORM\Column(type="string",length=255,nullable=false, unique=true)
     */
    private $identifier;

    /**
     * @var ArrayCollection|DownloadFile[]
     * @ORM\OneToMany(targetEntity="DownloadFile", mappedBy="download",cascade={"persist"})
     */
    private $downloadFiles;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $isShowName;

    /**
     * @var ArrayCollection|DownloadTranslation[]
     * @ORM\OneToMany(targetEntity="DownloadTranslation", mappedBy="download",cascade={"persist", "remove"})
     */
    private $translations;

    /**
     * Download constructor.
     * @param $identifier
     * @param bool $isShowName
     */
    public function __construct($identifier, $isShowName = false)
    {
        $this->identifier = $identifier;
        $this->isShowName = $isShowName;
        $this->downloadFiles = new ArrayCollection();
        $this->translations = new ArrayCollection();
    }

    /**
     * @param boolean $isShowName
     */
    public function setIsShowName($isShowName)
    {
        $this->isShowName = $isShowName;
    }

    /**
     * @return DownloadFile[]|ArrayCollection
     */
    public function getDownloadFiles()
    {
        return $this->downloadFiles;
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }
    

    /**
     * @return boolean
     */
    public function isShowName()
    {
        return $this->isShowName;
    }

    /**
     * @return ArrayCollection|DownloadTranslation[]
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * @param ILocale $locale
     * @return DownloadTranslation
     */
    public function getTranslation(ILocale $locale)
    {
        $criteria = Criteria::create()->where(Criteria::expr()->eq("locale", $locale));
        return $this->getTranslations()->matching($criteria)->first();
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

}