<?php

namespace App\Core;

use ReflectionClass;

final class Router
{
    private array $routes = [];

    public function __construct(private readonly array $config)
    {
    }

    public function get(string $route, array $action): void
    {
        $this->routes['GET'][$route] = $action;
    }

    public function post(string $route, array $action): void
    {
        $this->routes['POST'][$route] = $action;
    }

    public function dispatch(string $route, string $method): void
    {
        $method = strtoupper($method);
        $route = trim($route);

        if (!isset($this->routes[$method][$route])) {
            http_response_code(404);
            echo 'Route bulunamadi.';
            return;
        }

        [$controllerClass, $controllerMethod] = $this->routes[$method][$route];

        $controller = (new ReflectionClass($controllerClass))->newInstance($this->config);
        $controller->{$controllerMethod}();
    }
}
