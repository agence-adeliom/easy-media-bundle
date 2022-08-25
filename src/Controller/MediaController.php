<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Controller;

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
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use League\Flysystem\Filesystem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MediaController extends AbstractController
{
    use Utils;
    use GetContent;
    use Delete;
    use Download;
    use Move;
    use Rename;
    use Metas;
    use Upload;
    use NewFolder;
    use GlobalSearch;

    protected TranslatorInterface $translator;
    protected EventDispatcherInterface $eventDispatcher;
    protected string $ignoreFiles;
    protected string $chunksDir;
    protected $paginationAmount;
    protected Filesystem $filesystem;
    protected ObjectManager $em;
    protected EasyMediaHelper $helper;
    protected EasyMediaManager $manager;

    /**
     * @readonly
     */
    protected $filesystem;

    public function __construct(EasyMediaManager $manager, ManagerRegistry $managerRegistry, ParameterBagInterface $bag, EventDispatcherInterface $dispatcher, TranslatorInterface $translator)
    {
        $this->manager = $manager;
        $this->managerRegistry = $managerRegistry;
        $this->em = $this->managerRegistry->getManager();

        $this->ignoreFiles = $bag->get('easy_media.ignore_files');
        $this->paginationAmount = $bag->get('easy_media.pagination_amount');
        $this->chunksDir = $bag->get('kernel.project_dir') . "/var/chunks_upload";
        $this->helper = $manager->getHelper();
        $this->filesystem = $manager->getFilesystem();

        $this->eventDispatcher = $dispatcher;
        $this->translator = $translator;
    }

    public function index(): Response
    {
        return $this->render('@EasyMedia/manager_view.html.twig');
    }

    public function browse(Request $request): Response
    {
        $data = [
            'provider' => $request->query->get('provider'),
            'restrict' => $request->query->get('restrict'),
            'CKEditor' => $request->query->get('CKEditor'),
            'CKEditorFuncNum' => $request->query->get('CKEditorFuncNum'),
            'langCode' => $request->query->get('langCode', 'en'),
        ];

        return $this->render('@EasyMedia/browser.html.twig', $data);
    }
}
