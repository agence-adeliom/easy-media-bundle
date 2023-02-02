<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Controller\Module;

use Symfony\Component\HttpFoundation\Request;

trait Utils
{
    /**
     * helper to paginate array.
     *
     * @param [type] $items
     */
    public function paginate($items, int $perPage = 10): array
    {
        $request = Request::createFromGlobals();
        $currentPage = (int) $request->query->get('page', 1);

        $page = $currentPage;
        $total = count($items); // total items in array
        $totalPages = (int) ceil($total / $perPage); // calculate total pages
        $page = max($page, 1); // get 1 page when $_GET['page'] <= 0
        $page = min($page, $totalPages);
        // get last page when $_GET['page'] > $totalPages
        $offset = ($page - 1) * $perPage;
        if ($offset < 0) {
            $offset = 0;
        }

        $datas = array_slice($items, $offset, $perPage);

        return [
            'current_page' => $currentPage,
            'data' => $datas,
            'total' => $total,
            'from' => $offset + 1,
            'to' => min($offset + 1 + $perPage, $total),
            'per_page' => $perPage,
            'last_page' => $totalPages,
            'path' => $request->getPathInfo(),
            'first_page_url' => $request->getPathInfo().(parse_url($request->getPathInfo(), PHP_URL_QUERY) ? '&' : '?').'page=1',
            'last_page_url' => $request->getPathInfo().(parse_url($request->getPathInfo(), PHP_URL_QUERY) ? '&' : '?').'page='.$totalPages,
            'next_page_url' => $currentPage + 1 <= $totalPages ? $request->getPathInfo().(parse_url($request->getPathInfo(), PHP_URL_QUERY) ? '&' : '?').'page='.min($currentPage + 1, $totalPages) : null,
            'prev_page_url' => $currentPage - 1 >= 1 ? $request->getPathInfo().(parse_url($request->getPathInfo(), PHP_URL_QUERY) ? '&' : '?').'page='.max($currentPage - 1, 1) : null,
        ];
    }
}
