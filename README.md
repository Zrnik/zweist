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
     * @param array<string, string> $arguments
     * @throws JsonException
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
        ),
        Middleware(ExampleMiddleware::class),
    ]
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

#### 2. Add `ZweistConfiguration` to your DI.

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
);
```

#### 3. Generate (and commit) `openapi.json` & `router.json`

```php
$zweistOpenApiGenerator = $container->get(ZweistOpenApiGenerator::class);
$zweistOpenApiGenerator->generate();
```

#### 4. Let `ZweistRouteService` populate routes in the `\Slim\App` instance.

```php
$zweistRouteService = $container->get(ZweistRouteService::class);
$zweistRouteService->applyRoutes($app);
```

## More things you should know

You will need to create a class with `openapi`
description attributes.
(see [./tests/ExampleApplication/ExampleApplicationInfo.php](./tests/ExampleApplication/ExampleApplicationInfo.php))

You want to **generate** `openapi.json` and `router.json` locally when developing,
and then committing them with your code, because you do not want to scan all the files
for the router at runtime for every request.

You should check on the CI that you didn't forget to generate new files.
