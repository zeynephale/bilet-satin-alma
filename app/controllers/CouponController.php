<?php

namespace App\Controllers;

use App\Core\CouponService;
use App\Core\Database;
use App\Core\Response;
use App\Models\Coupon;

class CouponController {
    
    /**
     * Validate coupon (AJAX endpoint for preview)
     * This is a read-only check, does not decrement usage
     */
    public function validate(): void {
        $code = trim($_POST['code'] ?? '');
        $firmaId = isset($_POST['firma_id']) ? (int) $_POST['firma_id'] : null;
        
        if (empty($code)) {
            Response::json(['valid' => false, 'message' => 'Kupon kodu gereklidir.'], 400);
        }
        
        if ($firmaId === null) {
            Response::json(['valid' => false, 'message' => 'Firma ID gereklidir.'], 400);
        }
        
        // Use CouponService for validation (read-only, no transaction needed)
        $db = Database::getInstance();
        $couponInfo = CouponService::checkCouponValidity($db, $code, $firmaId);
        
        if (!$couponInfo) {
            Response::json([
                'valid' => false, 
                'message' => 'Kupon geçerli değil, süresi dolmuş veya bu firma için kullanılamaz.'
            ], 400);
        }
        
        Response::json([
            'valid' => true,
            'discount_percent' => $couponInfo['discount_percent'],
            'remaining_uses' => $couponInfo['usage_limit'],
            'message' => '%' . $couponInfo['discount_percent'] . ' indirim uygulanacak.'
        ]);
    }
}

