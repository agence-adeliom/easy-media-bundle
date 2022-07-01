<?php

namespace Adeliom\EasyMediaBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Adeliom\EasyMediaBundle\Controller\Module\Delete;
use Adeliom\EasyMediaBundle\Controller\Module\Download;
use Adeliom\EasyMediaBundle\Controller\Module\GetContent;
use Adeliom\EasyMediaBundle\Controller\Module\GlobalSearch;
use Adeliom\EasyMediaBundle\Controller\Module\Metas;
use Adeliom\EasyMediaBundle\Controller\Module\Move;
use Adeliom\EasyMediaBundle\Controller\Module\NewFolder;
use Adeliom\EasyMediaBundle\Controller\Module\Rename;
use Adeliom\EasyMediaBundle\Controller\Module\Upload;
use Adeliom\EasyMediaBundle\Controller\Module\Utils;
use Adeliom\EasyMediaBundle\Service\EasyMediaHelper;
use Adeliom\EasyMediaBundle\Service\EasyMediaManager;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\Filesystem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


class MediaController extends AbstractController
{
    use Utils,
        GetContent,
        Delete,
        Download,
        Move,
        Rename,
        Metas,
        Upload,
        NewFolder,
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
    protected $ignoreFiles;
    protected $paginationAmount;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var EasyMediaHelper
     */
    protected $helper;


    public function __construct(ContainerInterface $container, protected EasyMediaManager $manager, private ManagerRegistry $managerRegistry)
    {
        $this->container = $container;
        $this->em = $this->managerRegistry->getManager();

        $this->ignoreFiles = $this->container->getParameter("easy_media.ignore_files");
        $this->paginationAmount = $this->container->getParameter("easy_media.pagination_amount");
        $this->helper = $manager->getHelper();
        $this->filesystem = $manager->getFilesystem();

        $this->eventDispatcher = $this->get("event_dispatcher");
        $this->translator = $this->get("translator");
    }

    /**
     * main view.
     *
     * @return [type] [description]
     */
    public function index(): Response
    {
        return $this->render("@EasyMedia/manager_view.html.twig");
    }

    public function browse(Request $request): Response
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
