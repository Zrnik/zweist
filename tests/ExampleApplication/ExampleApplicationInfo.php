<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Tests\ExampleApplication;

use OpenApi\Attributes\Contact;
use OpenApi\Attributes\Info;
use OpenApi\Attributes\License;

#[
    Info(
        version: 'v1.0.0',
        title: 'Example API',
        termsOfService: 'https://example.com/terms-of-service',
    ),
    Contact(
        name: 'Example Company',
        url: 'https://example.com/',
        email: 'info@example.com',
    ),
    License(
        name: 'The MIT License',
        identifier: 'MIT',
        url: 'https://mit-license.org/',
    ),
]
class ExampleApplicationInfo
{
}
