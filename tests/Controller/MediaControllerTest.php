<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Tests\Controller;

use Adeliom\EasyMediaBundle\Controller\MediaController;
use Adeliom\EasyMediaBundle\Service\EasyMediaHelper;
use Adeliom\EasyMediaBundle\Service\EasyMediaManager;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(\Adeliom\EasyMediaBundle\Controller\MediaController::class)]
final class MediaControllerTest extends TestCase
{
    public function testIndexRendersManagerView(): void
    {
        $controller = $this->createController();

        $response = $controller->index();

        self::assertSame('ok', $response->getContent());
        self::assertSame('@EasyMedia/manager_view.html.twig', $controller->lastTemplate);
        self::assertSame([], $controller->lastParameters);
    }

    public function testBrowseRendersBrowserViewWithQueryParameters(): void
    {
        $controller = $this->createController();
        $request = Request::create('/media/browse', 'GET', [
            'provider' => 'local',
            'restrict' => ['image', 'video'],
            'CKEditor' => 'editor',
            'CKEditorFuncNum' => '17',
            'langCode' => 'fr',
        ]);

        $response = $controller->browse($request);

        self::assertSame('ok', $response->getContent());
        self::assertSame('@EasyMedia/browser.html.twig', $controller->lastTemplate);
        self::assertSame('local', $controller->lastParameters['provider']);
        self::assertSame(['image', 'video'], $controller->lastParameters['restrict']);
        self::assertSame('editor', $controller->lastParameters['CKEditor']);
        self::assertSame('17', $controller->lastParameters['CKEditorFuncNum']);
        self::assertSame('fr', $controller->lastParameters['langCode']);
    }

    private function createController(): MediaControllerDouble
    {
        $filesystem = $this->createMock(FilesystemOperator::class);
        $helper = $this->createMock(EasyMediaHelper::class);
        $manager = $this->createMock(EasyMediaManager::class);
        $manager->method('getHelper')->willReturn($helper);
        $manager->method('getFilesystem')->willReturn($filesystem);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManager')->willReturn($this->createMock(ObjectManager::class));

        $bag = $this->createMock(ParameterBagInterface::class);
        $bag->method('get')
            ->willReturnMap([
                ['easy_media.ignore_files', '*.tmp'],
                ['easy_media.pagination_amount', 24],
                ['kernel.project_dir', '/srv/project'],
            ]);

        return new MediaControllerDouble(
            $manager,
            $registry,
            $bag,
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(TranslatorInterface::class)
        );
    }
}

final class MediaControllerDouble extends MediaController
{
    public string $lastTemplate = '';

    public array $lastParameters = [];

    protected function render(string $view, array $parameters = [], ?Response $response = null): Response
    {
        $this->lastTemplate = $view;
        $this->lastParameters = $parameters;

        return new Response('ok');
    }
}
