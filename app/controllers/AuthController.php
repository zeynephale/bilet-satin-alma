<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\SecurityHeaders;
use App\Core\Session;
use App\Models\User;

class AuthController {
    
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_TIME = 900; // 15 minutes
    
    public function showLogin(): void {
        // Prevent caching of login page (contains CSRF token and session data)
        SecurityHeaders::noCache();
        
        if (Auth::check()) {
            // If already logged in, redirect based on role or to requested page
            $redirect = $_GET['redirect'] ?? null;
            if ($redirect && $this->isValidRedirect($redirect)) {
                Response::redirect($redirect);
            }
            $this->redirectBasedOnRole(Auth::user());
        }
        
        require __DIR__ . '/../views/auth/login.php';
    }
    
    public function login(): void {
        Csrf::validateOrFail();
        
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Basic validation
        if (empty($username) || empty($password)) {
            Session::flash('error', 'Kullanıcı adı ve şifre gereklidir.');
            Response::redirect('/login');
        }
        
        // Brute-force protection: check login attempts
        $this->checkBruteForce($username);
        
        $user = User::findByUsername($username);
        
        if (!$user || !$user->verifyPassword($password)) {
            // Record failed attempt
            $this->recordFailedAttempt($username);
            
            // Get attempt count for delay calculation
            $attempts = $this->getFailedAttempts($username);
            
            // Progressive delay: 1-2 seconds based on attempts
            if ($attempts > 1) {
                $delay = min($attempts - 1, 5); // Max 5 seconds
                sleep($delay);
            }
            
            Session::flash('error', 'Geçersiz kullanıcı adı veya şifre.');
            Response::redirect('/login');
        }
        
        // Clear failed attempts on successful login
        $this->clearFailedAttempts($username);
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        Auth::login($user);
        Session::flash('success', 'Hoş geldiniz, ' . htmlspecialchars($user->username, ENT_QUOTES, 'UTF-8'));
        
        // Check if there's a redirect parameter
        $redirect = $_POST['redirect'] ?? $_GET['redirect'] ?? null;
        if ($redirect && $this->isValidRedirect($redirect)) {
            Response::redirect($redirect);
        }
        
        // Redirect based on role
        $this->redirectBasedOnRole($user);
    }
    
    public function showRegister(): void {
        // Prevent caching of register page (contains CSRF token)
        SecurityHeaders::noCache();
        
        if (Auth::check()) {
            Response::redirect('/');
        }
        
        require __DIR__ . '/../views/auth/register.php';
    }
    
    public function register(): void {
        Csrf::validateOrFail();
        
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        
        // Validation
        if (empty($username) || empty($password)) {
            Session::flash('error', 'Kullanıcı adı ve şifre gereklidir.');
            Response::redirect('/register');
        }
        
        if (strlen($username) < 3) {
            Session::flash('error', 'Kullanıcı adı en az 3 karakter olmalıdır.');
            Response::redirect('/register');
        }
        
        // Minimum 8 character password requirement
        if (strlen($password) < 8) {
            Session::flash('error', 'Şifre en az 8 karakter olmalıdır.');
            Response::redirect('/register');
        }
        
        if ($password !== $password_confirm) {
            Session::flash('error', 'Şifreler eşleşmiyor.');
            Response::redirect('/register');
        }
        
        // Check if username exists (UNIQUE constraint)
        if (User::findByUsername($username)) {
            Session::flash('error', 'Bu kullanıcı adı zaten kullanılıyor.');
            Response::redirect('/register');
        }
        
        // Create user: role='user', credit=0, firma_id=NULL
        $user = User::create([
            'username' => $username,
            'password' => $password,
            'role' => 'user',
            'credit' => 0.0,
            'firma_id' => null
        ]);
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        Auth::login($user);
        Session::flash('success', 'Kayıt başarılı! Hoş geldiniz.');
        
        // Redirect to user tickets page
        Response::redirect('/me/tickets');
    }
    
    public function logout(): void {
        Auth::logout();
        Session::flash('success', 'Başarıyla çıkış yaptınız.');
        Response::redirect('/');
    }
    
    /**
     * Redirect user based on their role
     */
    private function redirectBasedOnRole(User $user): void {
        if ($user->role === 'admin') {
            Response::redirect('/admin/firms');
        } elseif ($user->role === 'firma_admin') {
            Response::redirect('/firm-admin/trips');
        } else {
            // Regular users go to their tickets
            Response::redirect('/me/tickets');
        }
    }
    
    /**
     * Check for brute-force attempts
     */
    private function checkBruteForce(string $username): void {
        $attempts = $this->getFailedAttempts($username);
        $lockoutUntil = Session::get("lockout_until_{$username}");
        
        if ($lockoutUntil && time() < $lockoutUntil) {
            $remainingTime = ceil(($lockoutUntil - time()) / 60);
            Session::flash('error', "Çok fazla başarısız giriş denemesi. Lütfen {$remainingTime} dakika sonra tekrar deneyin.");
            Response::redirect('/login');
        }
        
        if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
            // Lock out for 15 minutes
            Session::set("lockout_until_{$username}", time() + self::LOCKOUT_TIME);
            Session::flash('error', 'Çok fazla başarısız giriş denemesi. Hesap 15 dakika süreyle kilitlendi.');
            Response::redirect('/login');
        }
    }
    
    /**
     * Record a failed login attempt
     */
    private function recordFailedAttempt(string $username): void {
        $attempts = $this->getFailedAttempts($username);
        Session::set("login_attempts_{$username}", $attempts + 1);
        Session::set("last_attempt_{$username}", time());
    }
    
    /**
     * Get number of failed login attempts
     */
    private function getFailedAttempts(string $username): int {
        $lastAttempt = Session::get("last_attempt_{$username}");
        
        // Reset attempts if last attempt was more than 15 minutes ago
        if ($lastAttempt && (time() - $lastAttempt) > self::LOCKOUT_TIME) {
            $this->clearFailedAttempts($username);
            return 0;
        }
        
        return (int) Session::get("login_attempts_{$username}", 0);
    }
    
    /**
     * Clear failed login attempts
     */
    private function clearFailedAttempts(string $username): void {
        Session::remove("login_attempts_{$username}");
        Session::remove("last_attempt_{$username}");
        Session::remove("lockout_until_{$username}");
    }
    
    /**
     * Validate redirect URL to prevent open redirect vulnerability
     */
    private function isValidRedirect(string $redirect): bool {
        // Only allow internal redirects (starting with /)
        // Prevent open redirect attacks
        if (!str_starts_with($redirect, '/')) {
            return false;
        }
        
        // Prevent protocol-relative URLs
        if (str_starts_with($redirect, '//')) {
            return false;
        }
        
        // Additional safety: check for common attack patterns
        if (preg_match('/[^\x20-\x7E]/', $redirect)) {
            return false;
        }
        
        return true;
    }
}

