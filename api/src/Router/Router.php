<?php

namespace App\Router;

class Router
{
    /**
     * @var array<string, array<string, callable|array>>
     */
    private array $routes = [];

    /**
     * Generic route registration – internal use.
     */
    public function add(string $method, string $path, callable|array $handler): void
    {
        $method = strtoupper($method);
        $path   = rtrim($path, '/') ?: '/';

        $this->routes[$method][$path] = $handler;
    }

    /**
     * Convenience: register a GET route.
     */
    public function get(string $path, callable|array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    /**
     * Convenience: register a POST route.
     */
    public function post(string $path, callable|array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    /**
     * Dispatch the current request.
     */
    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $path   = parse_url($uri, PHP_URL_PATH) ?? '/';
        $path   = rtrim($path, '/') ?: '/';

        if (!isset($this->routes[$method][$path])) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode([
                'status'  => 'error',
                'message' => 'Not found',
            ]);
            return;
        }

        $handler = $this->routes[$method][$path];

        // Allow both closures and [ControllerClass::class, 'method'] style
        if (is_array($handler)) {
            [$class, $action] = $handler;
            $instance = new $class();
            $result = $instance->$action();
        } else {
            $result = $handler();
        }

        // If handler returns something, output JSON by default
        if ($result !== null) {
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            echo is_string($result) ? $result : json_encode($result);
        }
    }
}
