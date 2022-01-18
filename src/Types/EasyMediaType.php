<?php
namespace Adeliom\EasyMediaBundle\Types;

use Adeliom\EasyMediaBundle\Entity\Media;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EasyMediaType extends Type
{
    const EASYMEDIATYPE = 'easy_media_type'; // modify to match your type name


    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return "TEXT";
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        $listeners = $platform->getEventManager()->getListeners('getContainer');
        $listener = array_shift($listeners);
        /** @var ContainerInterface $container */
        $container = $listener->getContainer();
        $class = $container->getParameter("easy_media.media_entity");

        if($value){
            return $container->get("doctrine.orm.entity_manager")->getRepository($class)->find($value);
        }
        return null;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if($value){
            if ($value instanceof Media) {
                return $value->getId();
            }
            return $value;
        }
        return null;
    }

    public function getName()
    {
        return self::EASYMEDIATYPE; // modify to match your constant name
    }

    /**
     * {@inheritdoc}
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
