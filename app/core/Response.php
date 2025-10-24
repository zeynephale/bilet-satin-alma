<?php

namespace App\Core;

class Response {
    
    public static function redirect(string $path, int $code = 302): void {
        header("Location: {$path}", true, $code);
        exit;
    }
    
    public static function json(array $data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    public static function back(): void {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        self::redirect($referer);
    }
    
    public static function notFound(): void {
        http_response_code(404);
        echo "404 - Page Not Found";
        exit;
    }
    
    public static function forbidden(): void {
        http_response_code(403);
        echo "403 - Forbidden";
        exit;
    }
    
    public static function error(string $message = "Internal Server Error", int $code = 500): void {
        http_response_code($code);
        echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        exit;
    }
}

