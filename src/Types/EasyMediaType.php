<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Types;

use Adeliom\EasyMediaBundle\Entity\Media;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EasyMediaType extends Type
{
    /**
     * @var string
     */
    public const EASYMEDIATYPE = 'easy_media_type';

    private static ?ContainerInterface $container = null;
    private static $mediaResolver = null;

    public static function setContainer(?ContainerInterface $container): void
    {
        self::$container = $container;
    }

    public static function setMediaResolver(?callable $mediaResolver): void
    {
        self::$mediaResolver = $mediaResolver;
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        return 'TEXT';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        if (!$value) {
            return null;
        }

        if (\is_callable(self::$mediaResolver)) {
            try {
                return (self::$mediaResolver)($value);
            } catch (\Throwable) {
                return null;
            }
        }

        $container = self::$container;

        if (!$container instanceof ContainerInterface) {
            return null;
        }

        $class = $container->getParameter('easy_media.media_entity');

        return $container->get('easy_media.entity_manager_provider')->getEntityManager()->getRepository($class)->find($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if ($value) {
            if ($value instanceof Media) {
                return $value->getId();
            }

            return $value;
        }

        return null;
    }

    public function getName(): string
    {
        return self::EASYMEDIATYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
