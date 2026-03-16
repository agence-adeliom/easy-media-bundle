<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Types;

use Adeliom\EasyMediaBundle\Entity\Media;
use Closure;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class EasyMediaType extends Type
{
    public const EASYMEDIATYPE = 'easy_media_type';

    private static ?Closure $mediaResolver = null;

    public static function setMediaResolver(?callable $mediaResolver): void
    {
        self::$mediaResolver = $mediaResolver !== null ? Closure::fromCallable($mediaResolver) : null;
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

        return null;
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
}
