<?php

declare(strict_types=1);

namespace App;

final class Router
{
    /** @var array<int, array{method:string, path:string, handler:callable}> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes[] = ['method' => 'GET', 'path' => $path, 'handler' => $handler];
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes[] = ['method' => 'POST', 'path' => $path, 'handler' => $handler];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = rtrim($path, '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            $pattern = '#^' . preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $route['path']) . '$#';
            if (!preg_match($pattern, $path, $matches)) {
                continue;
            }
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            ($route['handler'])($params);
            return;
        }

        http_response_code(404);
        echo 'Not found';
    }
}
