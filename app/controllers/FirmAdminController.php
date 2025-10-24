<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\SecurityHeaders;
use App\Core\Session;
use App\Models\Trip;
use App\Models\Coupon;

class FirmAdminController {
    
    /**
     * Trips Management
     * Access: role='firma_admin' only
     * Resource ownership: currentUser.firma_id check
     */
    
    public function trips(): void {
        Auth::requireRole('firma_admin');
        
        // Prevent caching of firma admin panel pages
        SecurityHeaders::noCache();
        
        // Only get trips belonging to current admin's firma
        $trips = Trip::search(['firma_id' => Auth::firmaId()]);
        
        require __DIR__ . '/../views/firm_admin/trips.php';
    }
    
    public function createTrip(): void {
        Auth::requireRole('firma_admin');
        Csrf::validateOrFail();
        
        // Automatically set firma_id to current admin's firma
        $busType = trim($_POST['bus_type'] ?? '2+2');
        $data = [
            'firma_id' => Auth::firmaId(),
            'from_city' => trim($_POST['from_city'] ?? ''),
            'to_city' => trim($_POST['to_city'] ?? ''),
            'date' => trim($_POST['date'] ?? ''),
            'time' => trim($_POST['time'] ?? ''),
            'price' => (float) ($_POST['price'] ?? 0),
            'seats' => (int) ($_POST['seats'] ?? 0),
            'bus_type' => $busType
        ];
        
        // Validation
        if (empty($data['from_city']) || empty($data['to_city']) || empty($data['date']) || empty($data['time'])) {
            Session::flash('error', 'Tüm alanlar gereklidir (Nereden, Nereye, Tarih, Saat).');
            Response::back();
        }
        
        if (!in_array($busType, ['2+1', '2+2', '3+2'])) {
            Session::flash('error', 'Geçersiz otobüs tipi.');
            Response::back();
        }
        
        if ($data['price'] <= 0) {
            Session::flash('error', 'Fiyat pozitif bir sayı olmalıdır.');
            Response::back();
        }
        
        if ($data['seats'] < 1 || $data['seats'] > 50) {
            Session::flash('error', 'Koltuk sayısı 1-50 arasında olmalıdır.');
            Response::back();
        }
        
        Trip::create($data);
        
        Session::flash('success', 'Sefer başarıyla oluşturuldu.');
        Response::redirect('/firm-admin/trips');
    }
    
    public function deleteTrip(string $id): void {
        Auth::requireRole('firma_admin');
        Csrf::validateOrFail();
        
        $trip = Trip::find((int) $id);
        
        if (!$trip) {
            Session::flash('error', 'Sefer bulunamadı.');
            Response::notFound();
        }
        
        // IDOR Protection: ensure trip belongs to admin's firma
        // Resource ownership check: currentUser.firma_id
        if ($trip->firma_id !== Auth::firmaId()) {
            Session::flash('error', 'Bu sefere erişim yetkiniz yok. Sadece kendi firmanıza ait seferleri yönetebilirsiniz.');
            http_response_code(403);
            Response::redirect('/firm-admin/trips');
        }
        
        $trip->delete();
        
        Session::flash('success', 'Sefer başarıyla silindi.');
        Response::redirect('/firm-admin/trips');
    }
    
    /**
     * Coupons Management
     * Access: role='firma_admin' only
     * Firma-specific coupons: firma_id = currentUser.firma_id (NOT global)
     */
    
    public function coupons(): void {
        Auth::requireRole('firma_admin');
        
        // Prevent caching of firma admin panel pages
        SecurityHeaders::noCache();
        
        // Only get coupons belonging to current admin's firma (is_global=0)
        $coupons = Coupon::getByFirma(Auth::firmaId());
        
        require __DIR__ . '/../views/firm_admin/coupons.php';
    }
    
    public function createCoupon(): void {
        Auth::requireRole('firma_admin');
        Csrf::validateOrFail();
        
        // Automatically set firma_id to current admin's firma (is_global=0)
        $data = [
            'code' => strtoupper(trim($_POST['code'] ?? '')),
            'discount_percent' => (float) ($_POST['discount_percent'] ?? 0),
            'usage_limit' => (int) ($_POST['usage_limit'] ?? 0),
            'expiry_date' => trim($_POST['expiry_date'] ?? ''),
            'firma_id' => Auth::firmaId() // Firma-specific coupon (NOT global)
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
        
        if ($data['usage_limit'] < 1 || $data['usage_limit'] > 10000) {
            Session::flash('error', 'Kullanım limiti 1-10000 arasında olmalıdır.');
            Response::back();
        }
        
        Coupon::create($data);
        
        Session::flash('success', 'Firma-özel kupon başarıyla oluşturuldu.');
        Response::redirect('/firm-admin/coupons');
    }
    
    public function deleteCoupon(string $id): void {
        Auth::requireRole('firma_admin');
        Csrf::validateOrFail();
        
        $coupon = Coupon::find((int) $id);
        
        if (!$coupon) {
            Session::flash('error', 'Kupon bulunamadı.');
            Response::notFound();
        }
        
        // IDOR Protection: ensure coupon belongs to admin's firma
        // Resource ownership check: currentUser.firma_id
        if ($coupon->firma_id !== Auth::firmaId()) {
            Session::flash('error', 'Bu kupona erişim yetkiniz yok. Sadece kendi firmanıza ait kuponları yönetebilirsiniz.');
            http_response_code(403);
            Response::redirect('/firm-admin/coupons');
        }
        
        $coupon->delete();
        
        Session::flash('success', 'Kupon başarıyla silindi.');
        Response::redirect('/firm-admin/coupons');
    }
}

