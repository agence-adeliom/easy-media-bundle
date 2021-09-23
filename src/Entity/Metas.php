<?php

namespace Adeliom\EasyMediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass
 */
class Metas implements \JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $path;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $metaKey;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $metaValue = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param mixed $path
     * @return self
     */
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMetaKey()
    {
        return $this->metaKey;
    }

    /**
     * @param mixed $metaKey
     * @return self
     */
    public function setMetaKey($metaKey)
    {
        $this->metaKey = $metaKey;
        return $this;
    }

    /**
     * @return null
     */
    public function getMetaValue()
    {
        return $this->metaValue;
    }

    /**
     * @param null $metaValue
     * @return self
     */
    public function setMetaValue($metaValue)
    {
        $this->metaValue = $metaValue;
        return $this;
    }


    public function jsonSerialize() {
        $value = $this->getMetaValue();
        $json = json_decode($value);
        if($json && $json != $value){
            $value = $json;
        }
        return [
            "key" => $this->getMetaKey(),
            "value" => $value
        ];
    }


}
