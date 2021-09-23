<?php
namespace Adeliom\EasyMediaBundle\Types;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;

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
        $container = $listener->getContainer();
        $root = $container->getParameter("easy_media.storage");

        if($value){
            if(file_exists(dirname($root) . $value)){
                return new File(dirname($root) . $value);
            }
        }
        return null;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if($value){
            return $value;
        }
        return null;
    }

    public function getName()
    {
        return self::EASYMEDIATYPE; // modify to match your constant name
    }
}
