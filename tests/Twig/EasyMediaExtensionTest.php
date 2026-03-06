<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Tests\Twig;

use Adeliom\EasyMediaBundle\Service\EasyMediaManager;
use Adeliom\EasyMediaBundle\Twig\EasyMediaExtension;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;
use Twig\TwigFunction;

#[CoversClass(\Adeliom\EasyMediaBundle\Twig\EasyMediaExtension::class)]
final class EasyMediaExtensionTest extends TestCase
{
    public function testExtensionRegistersExpectedFiltersAndFunctions(): void
    {
        $extension = new EasyMediaExtension(
            $this->createMock(EasyMediaManager::class),
            $this->createMock(FilterManager::class)
        );

        self::assertContainsOnlyInstancesOf(TwigFilter::class, $extension->getFilters());
        self::assertContainsOnlyInstancesOf(TwigFunction::class, $extension->getFunctions());
        self::assertSame(
            ['resolve_media', 'media_infos', 'media_meta'],
            array_map(static fn (TwigFilter $filter): string => $filter->getName(), $extension->getFilters())
        );
        self::assertSame(
            ['mime_icon', 'file_is_type', 'easy_media', 'easy_media_path', 'easy_media_download_url'],
            array_map(static fn (TwigFunction $function): string => $function->getName(), $extension->getFunctions())
        );
    }
}
