<?php

namespace App\Router;

class Router
{
    /**
     * @var array<string, array<int, array{pattern:string,keys:array,handler:callable|array}>>
     */
    private array $routes = [];

    /**
     * Generic route registration – internal use.
     */
    public function add(string $method, string $path, callable|array $handler): void
    {
        $method = strtoupper($method);
        $path   = rtrim($path, '/') ?: '/';

        // Convert route with {param} to regex
        $keys = [];
        $regex = preg_replace_callback('/\{([^}]+)\}/', function ($m) use (&$keys) {
            $keys[] = $m[1];
            return '([^/]+)';
        }, $path);
        $pattern = '#^' . $regex . '$#';

        $this->routes[$method][] = [
            'pattern' => $pattern,
            'keys'    => $keys,
            'handler' => $handler,
        ];
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

        if (!isset($this->routes[$method])) {
            return $this->notFound();
        }

        foreach ($this->routes[$method] as $route) {
            if (preg_match($route['pattern'], $path, $matches)) {
                array_shift($matches);
                $params = [];
                foreach ($route['keys'] as $idx => $key) {
                    $params[$key] = $matches[$idx] ?? null;
                }

                $handler = $route['handler'];
                if (is_array($handler)) {
                    [$class, $action] = $handler;
                    $instance = new $class();
                    $result = $instance->$action($params);
                } else {
                    $result = $handler($params);
                }

                if ($result !== null) {
                    if (!headers_sent()) {
                        header('Content-Type: application/json');
                    }
                    echo is_string($result) ? $result : json_encode($result);
                }
                return;
            }
        }

        $this->notFound();
    }

    private function notFound(): void
    {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'status'  => 'error',
            'message' => 'Not found',
        ]);
    }
}
