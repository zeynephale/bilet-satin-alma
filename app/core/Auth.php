<?php

namespace App\Core;

use App\Models\User;

class Auth {
    
    public static function login(User $user): void {
        Session::set('user_id', $user->id);
        Session::set('user_role', $user->role);
        Session::set('user_firma_id', $user->firma_id);
        // Note: session_regenerate_id(true) is called in AuthController for security
    }
    
    public static function logout(): void {
        Session::destroy();
    }
    
    public static function check(): bool {
        return Session::has('user_id');
    }
    
    public static function user(): ?User {
        if (!self::check()) {
            return null;
        }
        
        return User::find(Session::get('user_id'));
    }
    
    public static function id(): ?int {
        return Session::get('user_id');
    }
    
    public static function role(): ?string {
        return Session::get('user_role');
    }
    
    public static function firmaId(): ?int {
        return Session::get('user_firma_id');
    }
    
    public static function isAdmin(): bool {
        return self::role() === 'admin';
    }
    
    public static function isFirmaAdmin(): bool {
        return self::role() === 'firma_admin';
    }
    
    public static function isUser(): bool {
        return self::role() === 'user';
    }
    
    public static function hasRole(string ...$roles): bool {
        return in_array(self::role(), $roles);
    }
    
    public static function requireAuth(): void {
        if (!self::check()) {
            Session::flash('error', 'Bu sayfaya erişmek için giriş yapmalısınız.');
            Response::redirect('/login');
        }
    }
    
    public static function requireRole(string ...$roles): void {
        self::requireAuth();
        
        if (!self::hasRole(...$roles)) {
            http_response_code(403);
            Session::flash('error', 'Bu sayfaya erişim yetkiniz yok.');
            
            // Redirect to appropriate page based on current role
            $currentRole = self::role();
            if ($currentRole === 'admin') {
                Response::redirect('/admin/firms');
            } elseif ($currentRole === 'firma_admin') {
                Response::redirect('/firm-admin/trips');
            } else {
                Response::redirect('/');
            }
        }
    }
}

