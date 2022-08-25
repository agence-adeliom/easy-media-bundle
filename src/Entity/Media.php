<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[ORM\MappedSuperclass]
class Media
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected ?int $id = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    protected ?string $name = null;

    #[ORM\Column(length: 100)]
    protected ?string $slug = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true)]
    protected ?string $mime = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    protected ?int $size = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    protected ?int $lastModified = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::JSON)]
    protected $metas = [];

    protected ?Folder $folder = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): mixed
    {
        return $this->name;
    }

    public function setName(mixed $name): void
    {
        $this->name = $name;

        if (! $this->slug) {
            $this->slug = (new AsciiSlugger())->slug(strtolower((string) $this->name))->toString();
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

    public function getMime(): mixed
    {
        return $this->mime;
    }

    public function setMime(mixed $mime = null): void
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

    public function getMeta(string $key, $default = null)
    {
        return $this->metas[$key] ?? $default;
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

    public function getPath($separator = '/')
    {
        $tree = $this->getSlug();
        $current = $this->getFolder();
        if ($current !== null) {
            do {
                $tree = $current->getSlug() . $separator . $tree;
                $current = $current->getParent();
            } while ($current);
        }

        return trim($tree, $separator);
    }
}
