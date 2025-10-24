<?php

namespace App\Core;

class Router {
    private array $routes = [];
    
    public function get(string $path, callable|array $handler): void {
        $this->addRoute('GET', $path, $handler);
    }
    
    public function post(string $path, callable|array $handler): void {
        $this->addRoute('POST', $path, $handler);
    }
    
    private function addRoute(string $method, string $path, callable|array $handler): void {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }
    
    public function dispatch(): void {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        foreach ($this->routes as $route) {
            if ($route['method'] !== $requestMethod) {
                continue;
            }
            
            $pattern = $this->convertPathToRegex($route['path']);
            
            if (preg_match($pattern, $requestUri, $matches)) {
                array_shift($matches); // Remove full match
                
                // Filter out named capture groups (keep only numeric indices)
                // This prevents "Cannot use positional argument after named argument" error in PHP 8.0+
                $params = array_filter($matches, 'is_int', ARRAY_FILTER_USE_KEY);
                // Re-index array to start from 0
                $params = array_values($params);
                
                $handler = $route['handler'];
                
                if (is_array($handler)) {
                    [$controller, $method] = $handler;
                    $controllerInstance = new $controller();
                    call_user_func_array([$controllerInstance, $method], $params);
                } else {
                    call_user_func_array($handler, $params);
                }
                
                return;
            }
        }
        
        Response::notFound();
    }
    
    private function convertPathToRegex(string $path): string {
        // Convert :param to named capture group
        $pattern = preg_replace('/\/:([^\/]+)/', '/(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }
}

