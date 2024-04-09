<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Tests\InvalidController;

use Nette\Utils\Paginator;
use Nyholm\Psr7\Factory\Psr17Factory;
use OpenApi\Attributes\Get;
use OpenApi\Attributes\QueryParameter;
use OpenApi\Attributes\Schema;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zrnik\Zweist\Content\JsonContentFacade;
use Zrnik\Zweist\Content\Pagination\PaginatedJsonResponse;

class InvalidPaginationController
{
    private JsonContentFacade $jsonContentFacade;

    public function __construct()
    {
        $this->jsonContentFacade = new JsonContentFacade(
            new Psr17Factory(),
            new Psr17Factory(),
        );
    }

    #[
        Get(
            path: '/api/page-invaild/{page}',
            operationId: 'Paginated Invalid',
            description: '...',
            parameters: [
                new QueryParameter(name: 'page', schema: new Schema(type: 'integer', default: 1)),
            ],
            responses: [
                new PaginatedJsonResponse(
                    response: 200,
                    itemType: 'invalid',
                    description: '...',
                ),
            ],
        ),
    ]
    public function paginatedInvalid(
        RequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface
    {
        $data = ['zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten'];

        $paginator = new Paginator();
        $paginator->setPage(1);
        $paginator->setItemCount(count($data));
        $paginator->setItemsPerPage(3);

        return $this->jsonContentFacade->createPaginatedResponse(
            $paginator->page,
            $paginator->itemsPerPage,
            count($data),
            array_slice($data, $paginator->getOffset(), $paginator->getItemsPerPage()),
        );
    }
}
