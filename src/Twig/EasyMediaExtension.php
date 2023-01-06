<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Twig;

use Adeliom\EasyMediaBundle\Service\EasyMediaManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class EasyMediaExtension extends AbstractExtension
{
    public function __construct(protected EasyMediaManager $manager, protected FilterManager $filterManager)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('resolve_media', [EasyMediaRuntime::class, 'resolveMedia']),
            new TwigFilter('media_infos', [EasyMediaRuntime::class, 'mediaInfos']),
            new TwigFilter('media_meta', [EasyMediaRuntime::class, 'mediaMeta']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('mime_icon', [EasyMediaRuntime::class, 'getMimeIcon']),
            new TwigFunction('file_is_type', [EasyMediaRuntime::class, 'fileIsType']),
            new TwigFunction('easy_media', [EasyMediaRuntime::class, 'media'], ['is_safe' => ['html']]),
            new TwigFunction('easy_media_path', [EasyMediaRuntime::class, 'path']),
            new TwigFunction('easy_media_download_url', [EasyMediaRuntime::class, 'downloadUrl']),
        ];
    }
}
