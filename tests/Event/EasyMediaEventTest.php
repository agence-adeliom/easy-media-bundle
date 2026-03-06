<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Tests\Event;

use Adeliom\EasyMediaBundle\Event\EasyMediaBeforeFileCreated;
use Adeliom\EasyMediaBundle\Event\EasyMediaBeforeSetMetas;
use Adeliom\EasyMediaBundle\Event\EasyMediaFileDeleted;
use Adeliom\EasyMediaBundle\Event\EasyMediaFileRenamed;
use Adeliom\EasyMediaBundle\Event\EasyMediaFileSaved;
use Adeliom\EasyMediaBundle\Event\EasyMediaFileUploaded;
use Adeliom\EasyMediaBundle\Event\EasyMediaGenerateAllAlt;
use Adeliom\EasyMediaBundle\Event\EasyMediaGenerateAlt;
use Adeliom\EasyMediaBundle\Event\EasyMediaGenerateAltGroup;
use Adeliom\EasyMediaBundle\Tests\Fixtures\Entity\TestMedia;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;

final class EasyMediaEventTest extends TestCase
{
    public function testBeforeFileCreatedEventExposesAndMutatesPayload(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'easy-media-event');
        self::assertNotFalse($path);
        $file = new File($path);

        $event = new EasyMediaBeforeFileCreated($file, '/gallery', 'hero.jpg');

        self::assertSame($file, $event->getData());
        self::assertSame('/gallery', $event->getFolderPath());
        self::assertSame('hero.jpg', $event->getName());

        $event->setData('base64:image');
        $event->setFolderPath('/archive');
        $event->setName('archive.jpg');

        self::assertSame('base64:image', $event->getData());
        self::assertSame('/archive', $event->getFolderPath());
        self::assertSame('archive.jpg', $event->getName());

        @unlink($path);
    }

    public function testBeforeSetMetasEventExposesEntitySourceAndUpdatedMetas(): void
    {
        $media = new TestMedia();
        $event = new EasyMediaBeforeSetMetas($media, '/tmp/source.jpg', ['alt' => 'Initial']);

        self::assertSame($media, $event->getEntity());
        self::assertSame('/tmp/source.jpg', $event->getSource());
        self::assertSame(['alt' => 'Initial'], $event->getMetas());

        $event->setMetas(['alt' => 'Updated', 'title' => 'Hero']);

        self::assertSame(['alt' => 'Updated', 'title' => 'Hero'], $event->getMetas());
    }

    public function testUploadedAndSavedEventsExposeFileInformation(): void
    {
        $uploaded = new EasyMediaFileUploaded('gallery/hero.jpg', 'image/jpeg', ['folder' => 'gallery']);
        $saved = new EasyMediaFileSaved('gallery/hero.jpg', 'image/jpeg', ['folder' => 'gallery']);

        self::assertSame('gallery/hero.jpg', $uploaded->getFilePath());
        self::assertSame('image/jpeg', $uploaded->getMimeType());
        self::assertSame(['folder' => 'gallery'], $uploaded->getOptions());
        self::assertSame('gallery/hero.jpg', $saved->getFilePath());
        self::assertSame('image/jpeg', $saved->getMimeType());
        self::assertSame(['folder' => 'gallery'], $saved->getOptions());
    }

    public function testGenerateAltEventsExposeMutableAltAndRequestPayload(): void
    {
        $media = new TestMedia();
        $generateAlt = new EasyMediaGenerateAlt($media, 'gallery/hero.jpg', 'Old alt');
        $request = new Request(['scope' => 'all']);
        $generateAll = new EasyMediaGenerateAllAlt($request);
        $generateGroup = new EasyMediaGenerateAltGroup([1, 2, 3]);

        self::assertSame($media, $generateAlt->getEntity());
        self::assertSame('gallery/hero.jpg', $generateAlt->getFilePath());
        self::assertSame('Old alt', $generateAlt->getAlt());

        $generateAlt->setAlt('New alt');

        self::assertSame('New alt', $generateAlt->getAlt());
        self::assertSame($request, $generateAll->getRequest());
        self::assertSame([1, 2, 3], $generateGroup->getFiles());
    }

    public function testDeletedAndRenamedEventsCanBeInstantiated(): void
    {
        self::assertInstanceOf(EasyMediaFileDeleted::class, new EasyMediaFileDeleted('gallery/hero.jpg', false));
        self::assertInstanceOf(EasyMediaFileRenamed::class, new EasyMediaFileRenamed('gallery/old.jpg', 'gallery/new.jpg'));
    }
}
