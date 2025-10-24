<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\SecurityHeaders;
use App\Core\Session;
use App\Models\User;

class ProfileController {
    
    /**
     * Show change password form
     * Access: Any authenticated user
     */
    public function showChangePassword(): void {
        Auth::requireAuth();
        
        SecurityHeaders::noCache();
        
        require __DIR__ . '/../views/profile/change_password.php';
    }
    
    /**
     * Process password change
     * Access: Any authenticated user
     */
    public function changePassword(): void {
        Auth::requireAuth();
        Csrf::validateOrFail();
        
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            Session::flash('error', 'Tüm alanlar gereklidir.');
            Response::back();
        }
        
        // Get current user
        $user = User::find(Auth::id());
        
        if (!$user) {
            Session::flash('error', 'Kullanıcı bulunamadı.');
            Response::redirect('/');
        }
        
        // Verify current password
        if (!$user->verifyPassword($currentPassword)) {
            Session::flash('error', 'Mevcut şifreniz hatalı.');
            Response::back();
        }
        
        // Minimum 8 characters for new password
        if (strlen($newPassword) < 8) {
            Session::flash('error', 'Yeni şifre en az 8 karakter olmalıdır.');
            Response::back();
        }
        
        // Check password confirmation
        if ($newPassword !== $confirmPassword) {
            Session::flash('error', 'Yeni şifreler eşleşmiyor.');
            Response::back();
        }
        
        // Don't allow same password
        if ($currentPassword === $newPassword) {
            Session::flash('error', 'Yeni şifre mevcut şifrenizden farklı olmalıdır.');
            Response::back();
        }
        
        // Update password
        $db = \App\Core\Database::getInstance();
        $stmt = $db->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $success = $stmt->execute([
            'hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'id' => $user->id
        ]);
        
        if (!$success) {
            Session::flash('error', 'Şifre değiştirilemedi. Lütfen tekrar deneyin.');
            Response::back();
        }
        
        Session::flash('success', 'Şifreniz başarıyla değiştirildi.');
        
        // Redirect based on role
        if (Auth::isAdmin()) {
            Response::redirect('/admin/firms');
        } elseif (Auth::isFirmaAdmin()) {
            Response::redirect('/firm-admin/trips');
        } else {
            Response::redirect('/me/tickets');
        }
    }
    
    /**
     * Show add credit form
     * Access: User role only (not admin/firma_admin)
     */
    public function showAddCredit(): void {
        Auth::requireRole('user');
        
        SecurityHeaders::noCache();
        
        require __DIR__ . '/../views/profile/add_credit.php';
    }
    
    /**
     * Process credit addition
     * Access: User role only
     */
    public function addCredit(): void {
        Auth::requireRole('user');
        Csrf::validateOrFail();
        
        $amount = (float) ($_POST['amount'] ?? 0);
        
        // Validation
        if ($amount <= 0) {
            Session::flash('error', 'Geçersiz tutar. Pozitif bir sayı girin.');
            Response::back();
        }
        
        if ($amount > 10000) {
            Session::flash('error', 'Maksimum yükleme tutarı 10,000 TL\'dir.');
            Response::back();
        }
        
        // Get current user
        $user = User::find(Auth::id());
        
        if (!$user) {
            Session::flash('error', 'Kullanıcı bulunamadı.');
            Response::redirect('/');
        }
        
        // Add credit
        $user->addCredit($amount);
        
        Session::flash('success', number_format($amount, 2) . ' TL bakiye başarıyla yüklendi. Yeni bakiyeniz: ' . number_format($user->credit, 2) . ' TL');
        Response::redirect('/profile/add-credit');
    }
}

