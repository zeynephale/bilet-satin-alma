<?php

namespace App\Core;

class Csrf {
    
    public static function generateToken(): string {
        $token = bin2hex(random_bytes(32));
        Session::set('csrf_token', $token);
        return $token;
    }
    
    public static function getToken(): string {
        if (!Session::has('csrf_token')) {
            return self::generateToken();
        }
        return Session::get('csrf_token');
    }
    
    public static function validate(string $token): bool {
        $sessionToken = Session::get('csrf_token');
        
        if (!$sessionToken || !$token) {
            return false;
        }
        
        return hash_equals($sessionToken, $token);
    }
    
    public static function field(): string {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::getToken(), ENT_QUOTES, 'UTF-8') . '">';
    }
    
    public static function hidden(): string {
        return self::field();
    }
    
    public static function check(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            
            if (!self::validate($token)) {
                http_response_code(403);
                die('CSRF token validation failed');
            }
        }
    }
    
    public static function validateOrFail(): void {
        $token = $_POST['csrf_token'] ?? '';
        
        if (!self::validate($token)) {
            http_response_code(403);
            Session::flash('error', 'CSRF token doğrulaması başarısız. Lütfen tekrar deneyin.');
            die('CSRF token validation failed');
        }
    }
}

