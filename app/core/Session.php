<?php

namespace App\Core;

class Session {
    
    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            // Session cookie security settings
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_secure', $_ENV['APP_ENV'] === 'production' ? '1' : '0');
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.use_strict_mode', '1');
            
            // Session cache limiter
            // Don't automatically send cache headers - we'll control them manually
            session_cache_limiter('');
            
            session_start();
            
            // Session hijacking prevention
            if (!isset($_SESSION['initiated'])) {
                session_regenerate_id(true);
                $_SESSION['initiated'] = true;
            }
            
            // Session fixation prevention - regenerate periodically
            if (!isset($_SESSION['last_regeneration'])) {
                $_SESSION['last_regeneration'] = time();
            } elseif (time() - $_SESSION['last_regeneration'] > 300) {
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
        }
    }
    
    public static function set(string $key, $value): void {
        $_SESSION[$key] = $value;
    }
    
    public static function get(string $key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    public static function has(string $key): bool {
        return isset($_SESSION[$key]);
    }
    
    public static function remove(string $key): void {
        unset($_SESSION[$key]);
    }
    
    public static function destroy(): void {
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    public static function flash(string $key, $value = null) {
        if ($value === null) {
            $flash = self::get("flash_{$key}");
            self::remove("flash_{$key}");
            return $flash;
        }
        
        self::set("flash_{$key}", $value);
    }
    
    public static function hasFlash(string $key): bool {
        return self::has("flash_{$key}");
    }
}

