<?php

namespace Adeliom\EasyMediaBundle\Controller;

use Adeliom\EasyMediaBundle\Controller\Module\Delete;
use Adeliom\EasyMediaBundle\Controller\Module\Download;
use Adeliom\EasyMediaBundle\Controller\Module\GetContent;
use Adeliom\EasyMediaBundle\Controller\Module\GlobalSearch;
use Adeliom\EasyMediaBundle\Controller\Module\Lock;
use Adeliom\EasyMediaBundle\Controller\Module\Metas;
use Adeliom\EasyMediaBundle\Controller\Module\Move;
use Adeliom\EasyMediaBundle\Controller\Module\NewFolder;
use Adeliom\EasyMediaBundle\Controller\Module\Rename;
use Adeliom\EasyMediaBundle\Controller\Module\Upload;
use Adeliom\EasyMediaBundle\Controller\Module\Utils;
use Adeliom\EasyMediaBundle\Controller\Module\Visibility;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


class MediaController extends AbstractController
{
    use Utils,
        GetContent,
        Delete,
        Download,
        Lock,
        Move,
        Rename,
        Metas,
        Upload,
        NewFolder,
        Visibility,
        GlobalSearch;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var string
     */
    protected $rootPath;
    protected $baseUrl;
    protected $ignoreFiles;
    protected $GFI;
    protected $LMF;
    protected $paginationAmount;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    public function __construct(Container $container, TranslatorInterface $translator, EventDispatcherInterface $eventDispatcher, EntityManagerInterface $em)
    {
        $this->rootPath = $container->getParameter("easy_media.storage");
        $this->baseUrl = $container->getParameter("easy_media.base_url");
        $this->fileChars = $container->getParameter("easy_media.allowed_fileNames_chars");
        $this->folderChars = $container->getParameter("easy_media.allowed_folderNames_chars");
        $this->sanitizedText = $container->getParameter("easy_media.sanitized_text");
        $this->lockEntity = $container->getParameter("easy_media.lock_entity");
        $this->metasEntity = $container->getParameter("easy_media.metas_entity");
        $this->metasService = $container->get("easy_media.service.metas");
        $this->ignoreFiles = $container->getParameter("easy_media.ignore_files");
        $this->GFI = $container->getParameter("easy_media.get_folder_info");
        $this->LMF = $container->getParameter("easy_media.last_modified_format");
        $this->paginationAmount = $container->getParameter("easy_media.pagination_amount");

        // The internal adapter
        $adapter = new LocalFilesystemAdapter(
            // Determine the root directory
            $this->rootPath,
            // Customize how visibility is converted to unix permissions
            PortableVisibilityConverter::fromArray([
                'file' => [
                    'public' => 0644,
                    'private' => 0640,
                ],
                'dir' => [
                    'public' => 0755,
                    'private' => 0740,
                ],
            ]),
            // Write flags
            LOCK_EX,
            // How to deal with links, either DISALLOW_LINKS or SKIP_LINKS
            // Disallowing them causes exceptions when encountered
            LocalFilesystemAdapter::DISALLOW_LINKS
        );
        $this->filesystem = new Filesystem($adapter);

        $this->em = $em;

        $this->locker = $em->getRepository($this->lockEntity);
        $this->metas = $em->getRepository($this->metasEntity);

        $this->unallowedMimes = $container->getParameter("easy_media.unallowed_mimes");
        $this->unallowedExt = $container->getParameter("easy_media.unallowed_ext");
        $this->eventDispatcher = $eventDispatcher;
        $this->translator = $translator;
    }

    /**
     * main view.
     *
     * @return [type] [description]
     */
    public function index()
    {
        $datas = [];
        return $this->render("@EasyMedia/manager_view.html.twig", $datas);
    }

    public function browse(Request $request)
    {
        $data = [
            "provider" => $request->query->get("provider"),
            "restrict" => $request->query->get("restrict"),
            "CKEditor" => $request->query->get("CKEditor"),
            "CKEditorFuncNum" => $request->query->get("CKEditorFuncNum"),
            "langCode" => $request->query->get("langCode", "en")
        ];
        return $this->render("@EasyMedia/browser.html.twig", $data);
    }


}
