<?php

namespace App\Models;

use App\Core\Database;
use PDO;
use DateTime;

class Coupon {
    public ?int $id = null;
    public string $code;
    public float $discount_percent;
    public int $usage_limit;
    public int $used_count = 0;
    public string $expiry_date; // YYYY-MM-DD
    public ?int $firma_id = null; // NULL = global, otherwise firma-specific
    
    public static function create(array $data): self {
        $coupon = new self();
        $coupon->code = strtoupper($data['code']);
        $coupon->discount_percent = $data['discount_percent'];
        $coupon->usage_limit = $data['usage_limit'];
        $coupon->used_count = 0;
        $coupon->expiry_date = $data['expiry_date'];
        $coupon->firma_id = $data['firma_id'] ?? null;
        
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "INSERT INTO coupons (code, discount_percent, usage_limit, used_count, expiry_date, firma_id) 
             VALUES (:code, :discount_percent, :usage_limit, :used_count, :expiry_date, :firma_id)"
        );
        
        $stmt->execute([
            'code' => $coupon->code,
            'discount_percent' => $coupon->discount_percent,
            'usage_limit' => $coupon->usage_limit,
            'used_count' => $coupon->used_count,
            'expiry_date' => $coupon->expiry_date,
            'firma_id' => $coupon->firma_id
        ]);
        
        $coupon->id = (int) $db->lastInsertId();
        return $coupon;
    }
    
    public static function find(int $id): ?self {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM coupons WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        return self::hydrate($data);
    }
    
    public static function findByCode(string $code): ?self {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM coupons WHERE code = :code");
        $stmt->execute(['code' => strtoupper($code)]);
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        return self::hydrate($data);
    }
    
    public static function getByFirma(?int $firmaId = null): array {
        $db = Database::getInstance();
        
        if ($firmaId === null) {
            // Get global coupons
            $stmt = $db->prepare("SELECT * FROM coupons WHERE firma_id IS NULL ORDER BY code");
            $stmt->execute();
        } else {
            // Get firma-specific coupons
            $stmt = $db->prepare("SELECT * FROM coupons WHERE firma_id = :firma_id ORDER BY code");
            $stmt->execute(['firma_id' => $firmaId]);
        }
        
        $coupons = [];
        while ($data = $stmt->fetch()) {
            $coupons[] = self::hydrate($data);
        }
        
        return $coupons;
    }
    
    public static function all(): array {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM coupons ORDER BY code");
        $coupons = [];
        
        while ($data = $stmt->fetch()) {
            $coupons[] = self::hydrate($data);
        }
        
        return $coupons;
    }
    
    public function update(): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "UPDATE coupons SET code = :code, discount_percent = :discount_percent, 
             usage_limit = :usage_limit, used_count = :used_count, 
             expiry_date = :expiry_date, firma_id = :firma_id WHERE id = :id"
        );
        
        return $stmt->execute([
            'code' => $this->code,
            'discount_percent' => $this->discount_percent,
            'usage_limit' => $this->usage_limit,
            'used_count' => $this->used_count,
            'expiry_date' => $this->expiry_date,
            'firma_id' => $this->firma_id,
            'id' => $this->id
        ]);
    }
    
    public function delete(): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM coupons WHERE id = :id");
        return $stmt->execute(['id' => $this->id]);
    }
    
    public function isValid(?int $firmaId = null): bool {
        // Check if expired (using Europe/Istanbul timezone)
        $timezone = new \DateTimeZone('Europe/Istanbul');
        $today = new \DateTime('now', $timezone);
        $today->setTime(0, 0, 0); // Start of day
        
        $expiryDate = new \DateTime($this->expiry_date, $timezone);
        $expiryDate->setTime(23, 59, 59); // End of day
        
        if ($today > $expiryDate) {
            return false;
        }
        
        // Check usage limit (must have remaining uses)
        if ($this->used_count >= $this->usage_limit) {
            return false;
        }
        
        // Check firma scope
        // Global coupon: firma_id is NULL
        // Firma-specific: firma_id must match
        if ($this->firma_id !== null && $this->firma_id !== $firmaId) {
            return false;
        }
        
        return true;
    }
    
    public function decrementUsageLimit(): bool {
        // Decrement usage_limit instead of incrementing used_count
        if ($this->usage_limit <= 0) {
            return false;
        }
        
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "UPDATE coupons SET usage_limit = usage_limit - 1, used_count = used_count + 1 
             WHERE id = :id AND usage_limit > 0"
        );
        
        $result = $stmt->execute(['id' => $this->id]);
        
        if ($result) {
            $this->usage_limit--;
            $this->used_count++;
        }
        
        return $result;
    }
    
    public function calculateDiscount(float $price): float {
        return $price * ($this->discount_percent / 100);
    }
    
    private static function hydrate(array $data): self {
        $coupon = new self();
        $coupon->id = (int) $data['id'];
        $coupon->code = $data['code'];
        $coupon->discount_percent = (float) $data['discount_percent'];
        $coupon->usage_limit = (int) $data['usage_limit'];
        $coupon->used_count = (int) $data['used_count'];
        $coupon->expiry_date = $data['expiry_date'];
        $coupon->firma_id = $data['firma_id'] ? (int) $data['firma_id'] : null;
        return $coupon;
    }
}

