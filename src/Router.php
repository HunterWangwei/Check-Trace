<?php
declare(strict_types=1);

namespace App;

final class Router
{
    /** @var array<string, callable|array{0: class-string, 1: string}> */
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $path, $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);
        $path = '/' . ltrim(parse_url($path, PHP_URL_PATH) ?: '/', '/');

        $handler = $this->routes[$method][$path] ?? null;
        if ($handler === null) {
            http_response_code(404);
            echo 'Not Found';
            return;
        }

        if (is_array($handler)) {
            [$class, $action] = $handler;
            $instance = new $class();
            $handler = [$instance, $action];
        }

        echo call_user_func($handler, $_REQUEST);
    }
}


