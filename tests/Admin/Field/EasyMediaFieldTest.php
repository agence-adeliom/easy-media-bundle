<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Tests\Admin\Field;

use Adeliom\EasyMediaBundle\Admin\Field\EasyMediaField;
use Adeliom\EasyMediaBundle\Form\EasyMediaType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Adeliom\EasyMediaBundle\Admin\Field\EasyMediaField::class)]
final class EasyMediaFieldTest extends TestCase
{
    public function testFieldConfiguresExpectedEasyAdminDto(): void
    {
        $dto = EasyMediaField::new('media', 'Media')->getAsDto();

        self::assertSame('media', $dto->getProperty());
        self::assertSame('Media', $dto->getLabel());
        self::assertSame(EasyMediaType::class, $dto->getFormType());
        self::assertSame('@EasyMedia/admin/field/easy-media.html.twig', $dto->getTemplatePath());
        self::assertStringContainsString('field-easy-media', $dto->getCssClass());
        self::assertSame('col-md-8 col-xxl-7', $dto->getDefaultColumns());
    }
}
