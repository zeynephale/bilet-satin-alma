<?php

namespace App\Core;

use PDO;
use Exception;

/**
 * CouponService
 * 
 * Thread-safe coupon validation and application service.
 * Handles concurrent coupon usage with atomic operations.
 */
class CouponService {
    
    /**
     * Validate and apply coupon within a transaction
     * 
     * @param PDO $db Database connection (must be in active transaction)
     * @param string $code Coupon code
     * @param int $tripId Trip ID (for firma_id lookup)
     * @param int $firmaId Firma ID from trip
     * @param int $userId User ID (for potential future user-specific coupons)
     * @return array ['discount' => float, 'coupon_id' => int, 'code' => string]
     * @throws Exception on validation failure or database error
     */
    public static function validateAndApplyCoupon(
        PDO $db, 
        string $code, 
        int $tripId, 
        int $firmaId, 
        int $userId
    ): array {
        
        // Sanitize input
        $code = strtoupper(trim($code));
        
        if (empty($code)) {
            throw new Exception('Kupon kodu boş olamaz.');
        }
        
        // Step 1: Lock the coupon row
        // Note: SQLite doesn't fully support FOR UPDATE, but BEGIN IMMEDIATE provides row-level locking
        $stmt = $db->prepare(
            "SELECT id, code, discount_percent, usage_limit, used_count, expiry_date, firma_id 
             FROM coupons 
             WHERE code = :code"
        );
        
        $stmt->execute(['code' => $code]);
        $couponData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$couponData) {
            throw new Exception('Geçersiz kupon kodu.');
        }
        
        // Step 2: Validate expiry date (Europe/Istanbul timezone)
        $timezone = new \DateTimeZone('Europe/Istanbul');
        $today = new \DateTime('now', $timezone);
        $today->setTime(0, 0, 0); // Start of day
        
        $expiryDate = new \DateTime($couponData['expiry_date'], $timezone);
        $expiryDate->setTime(23, 59, 59); // End of day
        
        if ($today > $expiryDate) {
            throw new Exception('Bu kuponun süresi dolmuştur.');
        }
        
        // Step 3: Validate usage limit
        if ($couponData['usage_limit'] <= 0) {
            throw new Exception('Bu kuponun kullanım hakkı kalmamıştır.');
        }
        
        // Step 4: Validate scope (is_global=1 OR firma_id matches)
        // Global coupon: firma_id = NULL
        // Firma-specific coupon: firma_id = X
        if ($couponData['firma_id'] !== null && (int)$couponData['firma_id'] !== $firmaId) {
            throw new Exception('Bu kupon sadece belirli firmalarda geçerlidir.');
        }
        
        // Step 5: Calculate discount
        $discountPercent = (float)$couponData['discount_percent'];
        
        // Step 6: Atomically decrement usage_limit and increment used_count
        // This is the critical section that prevents double-spending
        $updateStmt = $db->prepare(
            "UPDATE coupons 
             SET usage_limit = usage_limit - 1, 
                 used_count = used_count + 1
             WHERE id = :id 
               AND usage_limit > 0"
        );
        
        $updateStmt->execute(['id' => $couponData['id']]);
        
        // Check if update actually happened (usage_limit > 0 condition)
        if ($updateStmt->rowCount() === 0) {
            throw new Exception('Kupon kullanımı sırasında bir hata oluştu. Lütfen tekrar deneyin.');
        }
        
        // Return discount information
        return [
            'discount' => $discountPercent,
            'coupon_id' => (int)$couponData['id'],
            'code' => $couponData['code'],
            'discount_percent' => $discountPercent
        ];
    }
    
    /**
     * Check if a coupon is valid (without applying it)
     * Used for preview/validation before purchase
     * 
     * @param PDO $db Database connection
     * @param string $code Coupon code
     * @param int $firmaId Firma ID
     * @return array|null Coupon info or null if invalid
     */
    public static function checkCouponValidity(PDO $db, string $code, int $firmaId): ?array {
        $code = strtoupper(trim($code));
        
        if (empty($code)) {
            return null;
        }
        
        // Read-only check (no locking needed)
        $stmt = $db->prepare(
            "SELECT id, code, discount_percent, usage_limit, used_count, expiry_date, firma_id 
             FROM coupons 
             WHERE code = :code"
        );
        
        $stmt->execute(['code' => $code]);
        $couponData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$couponData) {
            return null;
        }
        
        // Validate expiry
        $timezone = new \DateTimeZone('Europe/Istanbul');
        $today = new \DateTime('now', $timezone);
        $today->setTime(0, 0, 0);
        
        $expiryDate = new \DateTime($couponData['expiry_date'], $timezone);
        $expiryDate->setTime(23, 59, 59);
        
        if ($today > $expiryDate) {
            return null;
        }
        
        // Validate usage limit
        if ($couponData['usage_limit'] <= 0) {
            return null;
        }
        
        // Validate scope
        if ($couponData['firma_id'] !== null && (int)$couponData['firma_id'] !== $firmaId) {
            return null;
        }
        
        return [
            'id' => (int)$couponData['id'],
            'code' => $couponData['code'],
            'discount_percent' => (float)$couponData['discount_percent'],
            'usage_limit' => (int)$couponData['usage_limit'],
            'used_count' => (int)$couponData['used_count']
        ];
    }
}


