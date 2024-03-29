<?php

declare(strict_types=1);

namespace Petite\Tests\Routing;

use Petite\Http\HttpNotFoundException;
use Petite\Http\Request;
use Petite\Routing\Router;
use PHPUnit\Framework\TestCase;
use Petite\Routing\Route;
use Petite\Container\Container;

class RouterTest extends TestCase
{
    private Router $router;
    private object $testClass;

    protected function setUp(): void
    {
        parent::setUp();
        $this->router = new Router((new Container()));
        $this->testClass = new class () {
            #[Route('/test')]
            public function index(): bool
            {
                return true;
            }

            public function show(): bool
            {
                return true;
            }

            public function create(): bool
            {
                return true;
            }

            #[Route('/test/store', 'POST')]
            public function store(): bool
            {
                return true;
            }

            public function edit(): bool
            {
                return true;
            }

            #[Route('/test/param', 'PATCH')]
            public function update(): bool
            {
                return true;
            }

            #[Route('/test/param', 'DELETE')]
            public function delete(): bool
            {
                return true;
            }
        };
    }

    public function testItCreatesGetRoute(): void
    {
        $this->router->get('/', [$this->testClass::class, 'index']);

        $expected = [
            'GET' => [
                '/' => [$this->testClass::class, 'index']
            ]
        ];

        $this->assertSame($expected, $this->router->getRoutes());
    }

    public function testItCreatesPostRoute(): void
    {
        $this->router->post('/', [$this->testClass::class, 'index']);

        $expected = [
            'POST' => [
                '/' => [$this->testClass::class, 'index']
            ]
        ];

        $this->assertSame($expected, $this->router->getRoutes());
    }

    public function testItCreatesPutRoute(): void
    {
        $this->router->put('/', [$this->testClass::class, 'index']);

        $expected = [
            'PUT' => [
                '/' => [$this->testClass::class, 'index']
            ]
        ];

        $this->assertSame($expected, $this->router->getRoutes());
    }

    public function testItCreatesPatchRoute(): void
    {
        $this->router->patch('/', [$this->testClass::class, 'index']);

        $expected = [
            'PATCH' => [
                '/' => [$this->testClass::class, 'index']
            ]
        ];

        $this->assertSame($expected, $this->router->getRoutes());
    }

    public function testItCreatesDeleteRoute(): void
    {
        $this->router->delete('/', [$this->testClass::class, 'index']);

        $expected = [
            'DELETE' => [
                '/' => [$this->testClass::class, 'index']
            ]
        ];

        $this->assertSame($expected, $this->router->getRoutes());
    }

    public function testItCreatesMultipleRoutes(): void
    {
        $this->router->createMultipleRoutes([$this->testClass::class]);

        $expected = [
            'GET' => [
                '/test' => [$this->testClass::class, 'index']
            ],
            'POST' => [
                '/test/store' => [$this->testClass::class, 'store']
            ],
            'PATCH' => [
                '/test/param' => [$this->testClass::class, 'update']
            ],
            'DELETE' => [
                '/test/param' => [$this->testClass::class, 'delete']
            ]
        ];

        $this->assertSame($expected, $this->router->getRoutes());
    }

    public function testItHasNoRoutesAtCreation(): void
    {
        $this->assertEmpty((new Router((new Container())))->getRoutes());
    }

    /**
     * @dataProvider httpNotFoundCases
     */
    public function testItThrowsHttpNotFoundException(
        string $uri,
        string $method
    ): void {
        $request = new Request(
            uri: $uri,
            method: $method
        );

        $this->router->get('/test', [$this->testClass::class, 'index']);
        $this->router->post('/test', [$this->testClass::class, 'store']);
        $this->expectException(HttpNotFoundException::class);
        $this->router->resolve($request);
    }

    /**
     * @dataProvider resolveCases
     */
    public function testItResolvesRouteWhenActionIsArray(
        string $uri,
        string $method
    ): void {
        $request = new Request(
            uri: $uri,
            method: $method
        );

        $this->router->get('/test', [$this->testClass::class, 'index']);
        $this->router->post('/test/store', [$this->testClass::class, 'store']);
        $this->router->patch('/test/param', [$this->testClass::class, 'update']);
        $this->router->delete('/test/param', [$this->testClass::class, 'delete']);
        $resolved = $this->router->resolve($request);
        $this->assertSame($resolved, true);
    }

    /**
     * @dataProvider resolveCases
     */
    public function testItResolvesRouteWhenActionIsCallable(
        string $uri,
        string $method
    ): void {
        $request = new Request(
            uri: $uri,
            method: $method
        );

        $this->router->get('/test', fn () => true);
        $this->router->post('/test/store', fn () => true);
        $this->router->patch('/test/param', fn () => true);
        $this->router->delete('/test/param', fn () => true);
        $resolved = $this->router->resolve($request);
        $this->assertSame($resolved, true);
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function httpNotFoundCases(): array
    {
        return [
            ['/users', 'PUT'],
            ['/users', 'GET'],
            ['/test', 'PUT']
        ];
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function resolveCases(): array
    {
        return [
            ['/test', 'GET'],
            ['/test/store', 'POST'],
            ['/test/param', 'PATCH'],
            ['/test/param', 'DELETE'],
        ];
    }
}
