<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Firm {
    public ?int $id = null;
    public string $name;
    
    public static function create(array $data): self {
        $firm = new self();
        $firm->name = $data['name'];
        
        $db = Database::getInstance();
        $stmt = $db->prepare("INSERT INTO firms (name) VALUES (:name)");
        $stmt->execute(['name' => $firm->name]);
        
        $firm->id = (int) $db->lastInsertId();
        return $firm;
    }
    
    public static function find(int $id): ?self {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM firms WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        return self::hydrate($data);
    }
    
    public static function findByName(string $name): ?self {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM firms WHERE name = :name");
        $stmt->execute(['name' => $name]);
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        return self::hydrate($data);
    }
    
    public static function all(): array {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM firms ORDER BY name");
        $firms = [];
        
        while ($data = $stmt->fetch()) {
            $firms[] = self::hydrate($data);
        }
        
        return $firms;
    }
    
    public function update(): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE firms SET name = :name WHERE id = :id");
        return $stmt->execute(['name' => $this->name, 'id' => $this->id]);
    }
    
    public function delete(): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM firms WHERE id = :id");
        return $stmt->execute(['id' => $this->id]);
    }
    
    private static function hydrate(array $data): self {
        $firm = new self();
        $firm->id = (int) $data['id'];
        $firm->name = $data['name'];
        return $firm;
    }
}

