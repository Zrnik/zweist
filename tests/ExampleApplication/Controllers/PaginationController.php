<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Tests\ExampleApplication\Controllers;

use Nette\Utils\Paginator;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use OpenApi\Attributes\Get;
use OpenApi\Attributes\QueryParameter;
use OpenApi\Attributes\Schema;
use Psr\Http\Message\ResponseInterface;
use Zrnik\Zweist\Content\JsonContentFacade;
use Zrnik\Zweist\Content\Pagination\PaginatedJsonResponse;

class PaginationController
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
            path: '/api/page-scalar',
            operationId: 'Paginated Scalar',
            description: '...',
            parameters: [
                new QueryParameter(name: 'page', schema: new Schema(type: 'integer', default: 1)),
            ],
            responses: [
                new PaginatedJsonResponse(
                    response: 200,
                    itemType: 'string',
                    description: '...',
                ),
            ],
        ),
    ]
    public function paginatedScalar(
        ServerRequest $request,
        ResponseInterface $response,
    ): ResponseInterface
    {
        $data = ['zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten'];

        $paginator = new Paginator();

        $queryParams = $request->getQueryParams();

        if (array_key_exists('page', $queryParams)) {
            $paginator->setPage((int) $queryParams['page']);
        }

        $paginator->setItemCount(count($data));
        $paginator->setItemsPerPage(3);

        return $this->jsonContentFacade->createPaginatedResponse(
            $paginator->page,
            $paginator->itemsPerPage,
            count($data),
            array_slice($data, $paginator->getOffset(), $paginator->getItemsPerPage()),
        );
    }

    #[
        Get(
            path: '/api/page-object',
            operationId: 'Paginated Object',
            description: '...',
            parameters: [
                new QueryParameter(name: 'page', schema: new Schema(type: 'integer', default: 1)),
            ],
            responses: [
                new PaginatedJsonResponse(
                    response: 200,
                    itemType: PaginatedObject::class,
                    description: '...',
                ),
            ],
        ),
    ]
    public function paginatedObject(
        ServerRequest $request,
        ResponseInterface $response
    ): ResponseInterface
    {
        $data = [
            new PaginatedObject('zero'),
            new PaginatedObject('one'),
            new PaginatedObject('two'),
            new PaginatedObject('three'),
            new PaginatedObject('four'),
            new PaginatedObject('five'),
            new PaginatedObject('six'),
            new PaginatedObject('seven'),
            new PaginatedObject('eight'),
            new PaginatedObject('nine'),
            new PaginatedObject('ten'),
        ];

        $paginator = new Paginator();

        $queryParams = $request->getQueryParams();

        if (array_key_exists('page', $queryParams)) {
            $paginator->setPage((int) $queryParams['page']);
        }

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
