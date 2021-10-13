<?php

namespace Adeliom\EasyMediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * @ORM\MappedSuperclass
 */
class Media
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $name;

    /**
     * @var string|null
     * @ORM\Column(length=100)
     */
    protected $slug;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $mime = true;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $size = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $lastModified = null;

    /**
     * @ORM\Column(type="json")
     */
    protected $metas = [];

    /**
     * @var Folder|null
     */
    protected $folder = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        $this->name = $name;

        if(!$this->slug){
            $this->slug = (new AsciiSlugger())->slug($this->name)->toString();
        }
    }

    public function getSlug() {
        return $this->slug;
    }

    public function setSlug(string $slug) {
        $this->slug = $slug;
    }

    /**
     * @return mixed
     */
    public function getMime()
    {
        return $this->mime;
    }

    /**
     * @param mixed $mime
     */
    public function setMime($mime = null): void
    {
        $this->mime = $mime;
    }

    /**
     * @return int|null
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * @param int $size
     */
    public function setSize(?int $size): void
    {
        $this->size = $size;
    }

    /**
     * @return null
     */
    public function getLastModified()
    {
        return $this->lastModified;
    }

    /**
     * @param null $lastModified
     */
    public function setLastModified($lastModified): void
    {
        $this->lastModified = $lastModified;
    }

    /**
     * @return array
     */
    public function getMetas(): array
    {
        return $this->metas;
    }

    /**
     * @param array $metas
     */
    public function setMetas(array $metas): void
    {
        $this->metas = $metas;
    }

    /**
     * @return Folder|null
     */
    public function getFolder(): ?Folder
    {
        return $this->folder;
    }

    /**
     * @param Folder|null $folder
     */
    public function setFolder(?Folder $folder): void
    {
        $this->folder = $folder;
    }



    public function getPath($separator = "/") {
        $tree = $this->getSlug();
        $current = $this->getFolder();
        if($current) {
            do {
                $tree = $current->getSlug() . $separator . $tree;
                $current = $current->getParent();
            } while ($current);
        }
        return trim($tree, $separator);
    }

}
