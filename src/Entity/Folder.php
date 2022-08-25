<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[ORM\MappedSuperclass]
class Folder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected ?int $id = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    protected ?string $name = null;

    /**
     * @Gedmo\Slug(fields={"name"}, updatable=false)
     */
    #[ORM\Column(length: 100)]
    protected ?string $slug = null;

    protected ?Folder $parent = null;

    /**
     * @var Collection<Folder>
     */
    protected Collection $children;

    /**
     * @var Collection<Media>
     */
    protected Collection $medias;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->medias = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;

        if (! $this->slug) {
            $this->slug = (new AsciiSlugger())->slug(strtolower($this->name))->toString();
        }
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function addChild(Folder $child): void
    {
        $this->children[] = $child;
        $child->setParent($this);
    }

    public function getMedias()
    {
        return $this->medias;
    }

    public function addMedia(Media $media): void
    {
        $this->medias[] = $media;
        $media->setFolder($this);
    }

    public function setParent(?Folder $parent = null): void
    {
        $this->parent = $parent;
    }

    public function getPath($separator = '/')
    {
        $tree = '';
        $current = $this;
        do {
            $tree = $current->getSlug() . $separator . $tree;
            $current = $current->getParent();
        } while ($current);

        return trim($tree, $separator);
    }
}
