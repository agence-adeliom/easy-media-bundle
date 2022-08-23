<?php

namespace Adeliom\EasyMediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[ORM\MappedSuperclass]
class Media
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected int $id;

    #[ORM\Column(type: 'string', length: 255)]
    protected string $name;

    #[ORM\Column(length: 100)]
    protected ?string $slug;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected bool $mime = true;

    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $size;

    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $lastModified;

    #[ORM\Column(type: 'json')]
    protected array $metas = [];

    protected ?Folder $folder;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
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

    public function getMime(): bool
    {
        return $this->mime;
    }

    public function setMime(?bool $mime = null): void
    {
        $this->mime = $mime;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): void
    {
        $this->size = $size;
    }

    public function getLastModified(): ?int
    {
        return $this->lastModified;
    }

    public function setLastModified(?int $lastModified): void
    {
        $this->lastModified = $lastModified;
    }

    public function getMetas(): array
    {
        return $this->metas;
    }

    public function setMetas(array $metas): void
    {
        $this->metas = $metas;
    }

    public function getFolder(): ?Folder
    {
        return $this->folder;
    }

    public function setFolder(?Folder $folder): void
    {
        $this->folder = $folder;
    }

    public function getPath(string $separator = "/"): string {
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
