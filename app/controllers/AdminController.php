<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\SecurityHeaders;
use App\Core\Session;
use App\Models\Firm;
use App\Models\User;
use App\Models\Coupon;

class AdminController {
    
    /**
     * Firms Management
     * Access: role='admin' only
     * UNIQUE name constraint
     */
    
    public function firms(): void {
        Auth::requireRole('admin');
        
        // Prevent caching of admin panel pages
        SecurityHeaders::noCache();
        
        $firms = Firm::all();
        
        require __DIR__ . '/../views/admin/firms.php';
    }
    
    public function createFirm(): void {
        Auth::requireRole('admin');
        Csrf::validateOrFail();
        
        $firmName = trim($_POST['name'] ?? '');
        $adminUsername = trim($_POST['admin_username'] ?? '');
        $adminPassword = $_POST['admin_password'] ?? '';
        
        // Validation
        if (empty($firmName)) {
            Session::flash('error', 'Firma adı gereklidir.');
            Response::back();
        }
        
        if (strlen($firmName) < 2 || strlen($firmName) > 100) {
            Session::flash('error', 'Firma adı 2-100 karakter arasında olmalıdır.');
            Response::back();
        }
        
        if (empty($adminUsername)) {
            Session::flash('error', 'Firma admin kullanıcı adı gereklidir.');
            Response::back();
        }
        
        if (strlen($adminUsername) < 3 || strlen($adminUsername) > 50) {
            Session::flash('error', 'Kullanıcı adı 3-50 karakter arasında olmalıdır.');
            Response::back();
        }
        
        if (empty($adminPassword)) {
            Session::flash('error', 'Firma admin şifresi gereklidir.');
            Response::back();
        }
        
        if (strlen($adminPassword) < 8) {
            Session::flash('error', 'Şifre en az 8 karakter olmalıdır.');
            Response::back();
        }
        
        // Check UNIQUE firma name constraint
        if (Firm::findByName($firmName)) {
            Session::flash('error', 'Bu firma adı zaten kullanılıyor. Lütfen farklı bir isim seçin.');
            Response::back();
        }
        
        // Check UNIQUE username constraint
        if (User::findByUsername($adminUsername)) {
            Session::flash('error', 'Bu kullanıcı adı zaten kullanılıyor. Lütfen farklı bir kullanıcı adı seçin.');
            Response::back();
        }
        
        try {
            // Begin transaction
            \App\Core\Database::beginTransaction();
            
            // 1. Create firm
            $firm = Firm::create(['name' => $firmName]);
            
            // 2. Create firma_admin user for this firm
            $adminUser = User::create([
                'username' => $adminUsername,
                'password' => $adminPassword,
                'role' => 'firma_admin',
                'firma_id' => $firm->id,
                'credit' => 0.0
            ]);
            
            // Commit transaction
            \App\Core\Database::commit();
            
            // Store credentials in session to display once (will be cleared on next page load)
            Session::set('new_firm_credentials', [
                'firm_name' => $firmName,
                'username' => $adminUsername,
                'password' => $adminPassword
            ]);
            
            Session::flash('success', "Firma ve admin kullanıcısı başarıyla oluşturuldu! Giriş bilgilerini aşağıda görebilirsiniz.");
            Response::redirect('/admin/firms');
            
        } catch (\Exception $e) {
            // Rollback on error
            \App\Core\Database::rollback();
            Session::flash('error', 'Firma oluşturulurken bir hata oluştu: ' . $e->getMessage());
            Response::back();
        }
    }
    
    public function deleteFirm(string $id): void {
        Auth::requireRole('admin');
        Csrf::validateOrFail();
        
        $firm = Firm::find((int) $id);
        
        if (!$firm) {
            Session::flash('error', 'Firma bulunamadı.');
            Response::notFound();
        }
        
        $firm->delete();
        
        Session::flash('success', 'Firma başarıyla silindi.');
        Response::redirect('/admin/firms');
    }
    
    /**
     * Users Management
     * Access: role='admin' only
     * Can create new users or update existing users to firma_admin
     */
    
    public function users(): void {
        Auth::requireRole('admin');
        
        // Prevent caching of admin panel pages
        SecurityHeaders::noCache();
        
        $users = User::all();
        $firms = Firm::all();
        
        require __DIR__ . '/../views/admin/users.php';
    }
    
    public function createUser(): void {
        Auth::requireRole('admin');
        Csrf::validateOrFail();
        
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';
        $firmaId = !empty($_POST['firma_id']) ? (int) $_POST['firma_id'] : null;
        
        // Validation
        if (empty($username)) {
            Session::flash('error', 'Kullanıcı adı gereklidir.');
            Response::back();
        }
        
        if (strlen($username) < 3 || strlen($username) > 50) {
            Session::flash('error', 'Kullanıcı adı 3-50 karakter arasında olmalıdır.');
            Response::back();
        }
        
        if (empty($password)) {
            Session::flash('error', 'Şifre gereklidir.');
            Response::back();
        }
        
        // Password minimum 8 characters
        if (strlen($password) < 8) {
            Session::flash('error', 'Şifre en az 8 karakter olmalıdır.');
            Response::back();
        }
        
        if (!in_array($role, ['admin', 'firma_admin', 'user'])) {
            Session::flash('error', 'Geçersiz rol.');
            Response::back();
        }
        
        if ($role === 'firma_admin' && !$firmaId) {
            Session::flash('error', 'Firma admin için firma seçimi gereklidir.');
            Response::back();
        }
        
        // Check username UNIQUE
        if (User::findByUsername($username)) {
            Session::flash('error', 'Bu kullanıcı adı zaten kullanılıyor. Lütfen farklı bir kullanıcı adı seçin.');
            Response::back();
        }
        
        // password_hash will be applied in User::create()
        User::create([
            'username' => $username,
            'password' => $password, // Will be hashed in User model
            'role' => $role,
            'firma_id' => $firmaId,
            'credit' => 1000.0
        ]);
        
        Session::flash('success', 'Kullanıcı başarıyla oluşturuldu (role: ' . htmlspecialchars($role, ENT_QUOTES, 'UTF-8') . ').');
        Response::redirect('/admin/users');
    }
    
    public function updateUser(string $id): void {
        Auth::requireRole('admin');
        Csrf::validateOrFail();
        
        $user = User::find((int) $id);
        
        if (!$user) {
            Session::flash('error', 'Kullanıcı bulunamadı.');
            Response::notFound();
        }
        
        $role = $_POST['role'] ?? $user->role;
        $firmaId = !empty($_POST['firma_id']) ? (int) $_POST['firma_id'] : null;
        
        // Validation
        if (!in_array($role, ['admin', 'firma_admin', 'user'])) {
            Session::flash('error', 'Geçersiz rol.');
            Response::back();
        }
        
        if ($role === 'firma_admin' && !$firmaId) {
            Session::flash('error', 'Firma admin için firma seçimi gereklidir.');
            Response::back();
        }
        
        // Prevent changing own role
        if ($user->id === Auth::id() && $role !== $user->role) {
            Session::flash('error', 'Kendi rolünüzü değiştiremezsiniz.');
            Response::back();
        }
        
        // Update user
        $user->update([
            'role' => $role,
            'firma_id' => $firmaId
        ]);
        
        Session::flash('success', 'Kullanıcı rolü başarıyla güncellendi.');
        Response::redirect('/admin/users');
    }
    
    public function deleteUser(string $id): void {
        Auth::requireRole('admin');
        Csrf::validateOrFail();
        
        $user = User::find((int) $id);
        
        if (!$user) {
            Session::flash('error', 'Kullanıcı bulunamadı.');
            Response::notFound();
        }
        
        // Prevent deleting self
        if ($user->id === Auth::id()) {
            Session::flash('error', 'Kendi hesabınızı silemezsiniz.');
            Response::back();
        }
        
        $user->delete();
        
        Session::flash('success', 'Kullanıcı başarıyla silindi.');
        Response::redirect('/admin/users');
    }
    
    /**
     * Global Coupons Management
     * Access: role='admin' only
     * Global coupons: is_global=1, firma_id=NULL
     */
    
    public function coupons(): void {
        Auth::requireRole('admin');
        
        // Prevent caching of admin panel pages
        SecurityHeaders::noCache();
        
        // Get global coupons (firma_id = NULL)
        $coupons = Coupon::getByFirma(null);
        
        require __DIR__ . '/../views/admin/coupons.php';
    }
    
    public function createCoupon(): void {
        Auth::requireRole('admin');
        Csrf::validateOrFail();
        
        // Global coupon: is_global=1, firma_id=NULL
        $data = [
            'code' => strtoupper(trim($_POST['code'] ?? '')),
            'discount_percent' => (float) ($_POST['discount_percent'] ?? 0),
            'usage_limit' => (int) ($_POST['usage_limit'] ?? 0),
            'expiry_date' => trim($_POST['expiry_date'] ?? ''),
            'firma_id' => null // Global coupon (firma_id = NULL)
        ];
        
        // Validation
        if (empty($data['code'])) {
            Session::flash('error', 'Kupon kodu gereklidir.');
            Response::back();
        }
        
        if (strlen($data['code']) < 3 || strlen($data['code']) > 50) {
            Session::flash('error', 'Kupon kodu 3-50 karakter arasında olmalıdır.');
            Response::back();
        }
        
        if (empty($data['expiry_date'])) {
            Session::flash('error', 'Son kullanma tarihi gereklidir.');
            Response::back();
        }
        
        if ($data['discount_percent'] <= 0 || $data['discount_percent'] > 100) {
            Session::flash('error', 'İndirim oranı 1-100 arasında olmalıdır.');
            Response::back();
        }
        
        if ($data['usage_limit'] < 1 || $data['usage_limit'] > 100000) {
            Session::flash('error', 'Kullanım limiti 1-100000 arasında olmalıdır.');
            Response::back();
        }
        
        Coupon::create($data);
        
        Session::flash('success', 'Global kupon başarıyla oluşturuldu (tüm firmalar için geçerli).');
        Response::redirect('/admin/coupons');
    }
    
    public function deleteCoupon(string $id): void {
        Auth::requireRole('admin');
        Csrf::validateOrFail();
        
        $coupon = Coupon::find((int) $id);
        
        if (!$coupon) {
            Session::flash('error', 'Kupon bulunamadı.');
            Response::notFound();
        }
        
        // Only delete global coupons (firma_id = NULL)
        if ($coupon->firma_id !== null) {
            Session::flash('error', 'Sadece global kuponlar silinebilir. Firma-özel kuponlar için firma admin panelini kullanın.');
            http_response_code(403);
            Response::redirect('/admin/coupons');
        }
        
        $coupon->delete();
        
        Session::flash('success', 'Global kupon başarıyla silindi.');
        Response::redirect('/admin/coupons');
    }
}

