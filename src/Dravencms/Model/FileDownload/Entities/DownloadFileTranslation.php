<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\FileDownload\Entities;

use Doctrine\ORM\Mapping as ORM;
use Dravencms\Model\File\Entities\StructureFile;
use Dravencms\Model\Locale\Entities\Locale;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Nette;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;
use Gedmo\Sortable\Sortable;

/**
 * Class DownloadFileTranslation
 * @package Dravencms\Model\File\Entities
 * @ORM\Entity
 * @ORM\Table(name="fileDownloadFileTranslation")
 */
class DownloadFileTranslation extends Nette\Object
{
    use Identifier;
    use TimestampableEntity;

    /**
     * @var string
     * @ORM\Column(type="string",length=255,nullable=false)
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(type="string",length=255,nullable=false)
     */
    private $description;

    /**
     * @var DownloadFile
     * @ORM\ManyToOne(targetEntity="DownloadFile", inversedBy="translations")
     * @ORM\JoinColumn(name="download_file_id", referencedColumnName="id")
     */
    private $downloadFile;

    /**
     * @var Locale
     * @ORM\ManyToOne(targetEntity="Dravencms\Model\Locale\Entities\Locale")
     * @ORM\JoinColumn(name="locale_id", referencedColumnName="id")
     */
    private $locale;

    /**
     * DownloadFileTranslation constructor.
     * @param string $name
     * @param string $description
     * @param DownloadFile $downloadFile
     * @param Locale $locale
     */
    public function __construct(DownloadFile $downloadFile, Locale $locale, $name, $description)
    {
        $this->name = $name;
        $this->description = $description;
        $this->downloadFile = $downloadFile;
        $this->locale = $locale;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @param DownloadFile $downloadFile
     */
    public function setDownloadFile($downloadFile)
    {
        $this->downloadFile = $downloadFile;
    }

    /**
     * @param Locale $locale
     */
    public function setLocale(Locale $locale)
    {
        $this->locale = $locale;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return DownloadFile
     */
    public function getDownloadFile()
    {
        return $this->downloadFile;
    }

    /**
     * @return Locale
     */
    public function getLocale()
    {
        return $this->locale;
    }
}