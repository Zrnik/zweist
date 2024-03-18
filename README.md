# Zweist

> "I'm susperd the mountain zweist" - Lenka Dusilová

### What is this?

This is a routing tool for `Slim Framework 4` to generate router
only by using `zircote/swagger-php` attributes (or maybe annotations,
didn't test it) to generate the router.

```
composer require zrnik/zweist
```

### What do you mean?

#### 1. You annotate your controller methods with the `zircote/swagger-php` attributes

```php
class HelloWorldController
{
    /**
     * @throws JsonException
     * @param array<string, string> $arguments
     */
    #[
        Get(
            path: '/api/hello/{name}',
            operationId: 'Say Hello',
            description: 'says hello by the request parameter',
        ),
        Response(
            response: 200,
            description: 'when ok',
            content: new JsonContent(ref: TestResponse::class)
        )
    ]
    #[Middleware(ExampleMiddleware::class)]
    public function sayHello(
        RequestInterface $request,
        ResponseInterface $response,
        array $arguments = []
    ): ResponseInterface
    {
        return JsonResponse::of(
            $response,
            new TestResponse(
                sprintf(
                    'Hello, %s :)',
                    $arguments['name']
                )
            )
        );
    }
}
```

#### 2. Generate (and commit) `openapi.json` & `router.json`

```php
$zweistConfiguration = new ZweistConfiguration(
    
    // scan paths for openapi attributes (requests & schemas)
    [
        __DIR__ . '/../../Controllers',
        __DIR__ . '/../../Model',
    ], 
    
    // generated (and committed) files
    __DIR__ . '/openapi.json', 
    __DIR__ .'/router.json', 
    
    // PSR-11 DI ContainerInterface (to get middleware instances)
    $container,
);

$zweistOpenApiGenerator = new ZweistOpenApiGenerator($zweistConfiguration);

$zweistOpenApiGenerator->generate();
```

#### 3. Let `ZweistRouteService` populate routes in the `\Slim\App` instance.

```php
$zweistRouteService = new ZweistRouteService($zweistConfiguration);

$zweistRouteService->applyRoutes($app);
```


