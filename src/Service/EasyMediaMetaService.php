<?php
namespace Adeliom\EasyMediaBundle\Service;


use Adeliom\EasyMediaBundle\Entity\Metas;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EasyMediaMetaService
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var string
     */
    protected $metaEntity;

    /**
     * @var EntityRepository
     */
    protected $repository;

    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container)
    {
        $this->entityManager = $entityManager;
        $this->container = $container;
        $this->metaEntity = $this->container->getParameter("easy_media.metas_entity");
        $this->repository = $this->entityManager->getRepository($this->metaEntity);
    }

    /**
     * @param string $path
     * @return array
     */
    public function saveMetas(string $path, array $metas) : array
    {

        $newMetas = [];
        foreach ($metas as $key => $value){
            /** @var Metas $meta */
            if($key == "dimensions"){
                $value = json_encode($value);
                $meta = $this->setMeta($key, $value, $this->getMeta($path, $key));
                $meta->setPath($path);
                $newMetas[] = $meta;
            }elseif($key == "extra"){
                if (!empty($value)){
                    foreach ($value as $extra){
                        $meta = $this->setMeta($extra["name"], $extra["data"], $this->getMeta($path, $key));
                        $meta->setPath($path);
                        $newMetas[] = $meta;
                    }
                }
            }else{
                $meta = $this->setMeta($key, $value, $this->getMeta($path, $key));
                $meta->setPath($path);
                $newMetas[] = $meta;
            }

        }

        foreach ($newMetas as $meta){
            $this->entityManager->persist($meta);
        }
        $this->entityManager->flush();

        return $newMetas;
    }

    public function removeMetas(string $path)
    {
        foreach ($this->getMetas($path) as $meta){
            $this->entityManager->remove($meta);
        }
        $this->entityManager->flush();
    }

    public function moveMetas(string $oldPath, string $newPath)
    {
        foreach ($this->getMetas($oldPath) as $meta){
            $meta->setPath(str_replace($oldPath, $newPath, $meta->getPath()));
            $this->entityManager->persist($meta);
        }
        $this->entityManager->flush();
    }

    public function setMeta($key, $value, $instance = null){
        if(!$instance){
            $meta = new $this->metaEntity();
            $meta->setMetaKey($key);
        }else{
            $meta = $instance;
        }
        $meta->setMetaValue($value);
        return $meta;
    }



    /**
     * @param string $path
     * @return array
     */
    public function getMetas(string $path) : array
    {
        return $this->repository->findBy([
            "path" => $path
        ]);
    }

    /**
     * @param string $path
     * @param string $key
     * @return string|null
     */
    public function getMeta(string $path, string $key)
    {
        return $this->repository->findOneBy([
            "path" => $path,
            "metaKey" => $key
        ]);
    }

}
