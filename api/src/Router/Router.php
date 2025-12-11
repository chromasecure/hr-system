<?php
namespace App\Router;

use App\Helpers\Response;

class Router {
    private array $routes = [];

    public function add(string $method, string $path, callable $handler): void {
        $this->routes[] = [$method, $path, $handler];
    }

    public function dispatch(string $method, string $uri): void {
        $path = parse_url($uri, PHP_URL_PATH);
        // strip base path (e.g. /api/public) if present
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        if ($base !== '' && $base !== '/') {
            if (str_starts_with($path, $base)) {
                $path = substr($path, strlen($base));
                if ($path === '') $path = '/';
            }
        }
        foreach ($this->routes as [$m, $p, $h]) {
            $pattern = "@^" . preg_replace('@\{(\w+)\}@', '(?P<$1>[^/]+)', $p) . "$@";
            if ($m === $method && preg_match($pattern, $path, $matches)) {
                $h($matches);
                return;
            }
        }
        Response::error('Not found', 404);
    }
}


