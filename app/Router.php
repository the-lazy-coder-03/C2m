<?php

class Router
{
    private array $routes = [];

    public function get(string $pattern, string $file, array $defaults = []): void
    {
        $this->add('GET', $pattern, $file, $defaults);
    }

    public function post(string $pattern, string $file, array $defaults = []): void
    {
        $this->add('POST', $pattern, $file, $defaults);
    }

    public function add(string $method, string $pattern, string $file, array $defaults = []): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $this->normalizePath($pattern),
            'file' => $file,
            'defaults' => $defaults,
        ];
    }

    public function dispatch(string $method, string $path): bool
    {
        $method = strtoupper($method);
        $path = $this->normalizePath($path);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->match($route['pattern'], $path);

            if ($params === null) {
                continue;
            }

            foreach (array_merge($route['defaults'], $params) as $key => $value) {
                $_GET[$key] = $value;
                $_REQUEST[$key] = $value;
            }

            $this->includeRouteFile($route['file']);

            return true;
        }

        return false;
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    private function match(string $pattern, string $path): ?array
    {
        $paramNames = [];
        $regexParts = [];

        foreach (explode('/', trim($pattern, '/')) as $part) {
            if (preg_match('/^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$/', $part, $matches)) {
                $paramNames[] = $matches[1];
                $regexParts[] = '([^/]+)';
            } else {
                $regexParts[] = preg_quote($part, '#');
            }
        }

        $regex = $pattern === '/' ? '/' : '/' . implode('/', $regexParts);

        if (!preg_match('#^' . $regex . '$#', $path, $matches)) {
            return null;
        }

        array_shift($matches);

        return array_combine($paramNames, array_map('urldecode', $matches)) ?: [];
    }

    private function includeRouteFile(string $file): void
    {
        $projectRoot = dirname(__DIR__);
        $absolutePath = realpath($projectRoot . '/' . ltrim($file, '/'));

        if ($absolutePath === false || !str_starts_with($absolutePath, $projectRoot) || !is_file($absolutePath)) {
            throw new RuntimeException('Route target not found: ' . $file);
        }

        chdir(dirname($absolutePath));
        require $absolutePath;
    }
}
