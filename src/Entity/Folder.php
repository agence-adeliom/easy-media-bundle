<?php

namespace Adeliom\EasyMediaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[ORM\MappedSuperclass]
class Folder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected int $id;

    #[ORM\Column(type: 'string', length: 255)]
    protected string $name;

    #[Gedmo\Slug(fields:["name"], updatable:false)]
    #[ORM\Column(length: 100)]
    protected ?string $slug;

    protected ?Folder $parent;
    /**
     * @var ArrayCollection<Folder>
     */
    protected ArrayCollection $children;
    /**
     * @var ArrayCollection<Media>
     */
    protected ArrayCollection $medias;

    public function __construct() {
        $this->children = new ArrayCollection();
        $this->medias = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function setName(string $name): void {
        $this->name = $name;

        if(!$this->slug){
            $this->slug = (new AsciiSlugger())->slug(strtolower($this->name))->toString();
        }
    }

    public function getSlug(): ?string {
        return $this->slug;
    }

    public function setSlug(string $slug): void {
        $this->slug = $slug;
    }

    public function getParent(): ?Folder {
        return $this->parent;
    }

    public function getChildren() {
        return $this->children;
    }

    public function addChild(Folder $child): void {
        $this->children[] = $child;
        $child->setParent($this);
    }

    public function getMedias() {
        return $this->medias;
    }

    public function addMedia(Media $media): void {
        $this->medias[] = $media;
        $media->setFolder($this);
    }

    public function setParent(?Folder $parent = null): void {
        $this->parent = $parent;
    }

    public function getPath($separator = "/"): string {
        $tree = '';
        $current = $this;
        do {
            $tree = $current->getSlug().$separator.$tree;
            $current = $current->getParent();
        }
        while ($current);
        return trim($tree, $separator);
    }
}
