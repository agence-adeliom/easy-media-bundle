<?php
namespace Adeliom\EasyMediaBundle\Controller\Module;


use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

trait Metas
{
    /**
     * rename item.
     *
     * @param Request $request [description]
     *
     * @return [type] [description]
     */
    public function editMetasItem(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $file = $data["file"];
        $metas = $data["new_metas"];

        $storage_path         = $file['storage_path'];
        $path         = "/$storage_path";

        $message          = '';

        try {
            $metas = $this->metasService->saveMetas($path, $metas);

            $title = current(array_filter($metas, function ($item){return $item->getMetaKey() == "title";}));
            $alt = current(array_filter($metas, function ($item){return $item->getMetaKey() == "alt";}));
            $description = current(array_filter($metas, function ($item){return $item->getMetaKey() == "description";}));
            $extra = current(array_filter($metas, function ($item){return !in_array($item->getMetaKey(), ["title","alt","description","dimensions"]); }));


            $metas = [
                "title" => $title ? $title->getMetaValue() : null,
                "alt" => $alt ? $alt->getMetaValue() : null,
                "description" => $description ? $description->getMetaValue() : null,
                "extra" => $extra,
            ];
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }

        return new JsonResponse(compact('message', 'metas'));
    }
}
