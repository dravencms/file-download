<?php declare(strict_types = 1);
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\FileDownload\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Dravencms\Model\Locale\Entities\Locale;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Dravencms\Database\Attributes\Identifier;
use Nette;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Class DownloadTranslation
 * @package Dravencms\Model\File\Entities
 * @ORM\Entity
 * @ORM\Table(name="fileDownloadTranslation")
 */
class DownloadTranslation
{
    use Nette\SmartObject;
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
     * @var Download
     * @ORM\ManyToOne(targetEntity="Download", inversedBy="translations")
     * @ORM\JoinColumn(name="download_id", referencedColumnName="id")
     */
    private $download;

    /**
     * @var Locale
     * @ORM\ManyToOne(targetEntity="Dravencms\Model\Locale\Entities\Locale")
     * @ORM\JoinColumn(name="locale_id", referencedColumnName="id")
     */
    private $locale;

    /**
     * DownloadTranslation constructor.
     * @param string $name
     * @param string $description
     * @param Download $download
     * @param Locale $locale
     */
    public function __construct(Download $download, Locale $locale, string $name, string $description)
    {
        $this->name = $name;
        $this->description = $description;
        $this->download = $download;
        $this->locale = $locale;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @param Download $download
     */
    public function setDownload(Download $download): void
    {
        $this->download = $download;
    }

    /**
     * @param Locale $locale
     */
    public function setLocale(Locale $locale): void
    {
        $this->locale = $locale;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return Download
     */
    public function getDownload(): Download
    {
        return $this->download;
    }

    /**
     * @return Locale
     */
    public function getLocale(): Locale
    {
        return $this->locale;
    }
}