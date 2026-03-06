<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Tests\Form;

use Adeliom\EasyMediaBundle\Form\EasyMediaType;
use Adeliom\EasyMediaBundle\Service\EasyMediaHelper;
use Adeliom\EasyMediaBundle\Service\EasyMediaManager;
use Adeliom\EasyMediaBundle\Tests\Fixtures\Entity\TestMedia;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

#[CoversClass(\Adeliom\EasyMediaBundle\Form\EasyMediaType::class)]
final class EasyMediaTypeTest extends TypeTestCase
{
    private EasyMediaManager $manager;

    protected function getExtensions(): array
    {
        $helper = $this->createMock(EasyMediaHelper::class);
        $helper->method('getMediaClassName')->willReturn(TestMedia::class);

        $media = new TestMedia();
        $property = new \ReflectionProperty($media, 'id');
        $property->setValue($media, 12);

        $this->manager = $this->createMock(EasyMediaManager::class);
        $this->manager->method('getHelper')->willReturn($helper);
        $this->manager->method('getMedia')->willReturnCallback(static function ($value) use ($media) {
            return '12' === (string) $value || 12 === $value || $value instanceof TestMedia ? $media : null;
        });

        return [
            new PreloadedExtension([new EasyMediaType($this->manager)], []),
        ];
    }

    public function testTypeBuildsViewAndTransformsExistingMediaToIdentifier(): void
    {
        $media = $this->manager->getMedia(12);
        $form = $this->factory->create(EasyMediaType::class, $media, [
            'data_class' => null,
            'restrictions_path' => '/gallery',
            'restrictions_uploadTypes' => ['image/*'],
            'hideExt' => ['svg'],
            'editor' => false,
            'bulk_selection' => false,
        ]);
        $view = $form->createView();

        self::assertSame(TextType::class, $form->getConfig()->getType()->getInnerType()->getParent());
        self::assertSame('easy_media', $form->getConfig()->getType()->getInnerType()->getBlockPrefix());
        self::assertSame('12', $form->getViewData());
        self::assertSame('/gallery', $view->vars['restrict']['path']);
        self::assertSame(['image/*'], $view->vars['restrict']['uploadTypes']);
        self::assertSame(['svg'], $view->vars['hideExt']);
        self::assertFalse($view->vars['editor']);
        self::assertFalse($view->vars['bulk_selection']);
    }

    public function testTypeReverseTransformRejectsUnknownMedia(): void
    {
        $manager = $this->createMock(EasyMediaManager::class);
        $helper = $this->createMock(EasyMediaHelper::class);
        $helper->method('getMediaClassName')->willReturn(TestMedia::class);
        $manager->method('getHelper')->willReturn($helper);
        $manager->method('getMedia')->willReturn(null);

        $form = $this->factory->createBuilder()
            ->add('media', EasyMediaType::class, ['compound' => false])
            ->getForm();

        $type = new EasyMediaType($manager);
        $builder = $this->factory->createBuilder();
        $type->buildForm($builder, []);
        $transformer = $builder->getModelTransformers()[0];

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('999');
    }
}
