<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class User {
    public ?int $id = null;
    public string $username;
    public string $password_hash;
    public string $role; // admin, firma_admin, user
    public ?int $firma_id = null;
    public float $credit = 0.0;
    
    public static function create(array $data): self {
        $user = new self();
        $user->username = $data['username'];
        $user->password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
        $user->role = $data['role'] ?? 'user';
        $user->firma_id = $data['firma_id'] ?? null;
        $user->credit = $data['credit'] ?? 0.0;
        
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "INSERT INTO users (username, password_hash, role, firma_id, credit) 
             VALUES (:username, :password_hash, :role, :firma_id, :credit)"
        );
        
        $stmt->execute([
            'username' => $user->username,
            'password_hash' => $user->password_hash,
            'role' => $user->role,
            'firma_id' => $user->firma_id,
            'credit' => $user->credit
        ]);
        
        $user->id = (int) $db->lastInsertId();
        return $user;
    }
    
    public static function find(int $id): ?self {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        return self::hydrate($data);
    }
    
    public static function findByUsername(string $username): ?self {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        return self::hydrate($data);
    }
    
    public static function all(): array {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM users ORDER BY id");
        $users = [];
        
        while ($data = $stmt->fetch()) {
            $users[] = self::hydrate($data);
        }
        
        return $users;
    }
    
    public static function search(array $filters = []): array {
        $db = Database::getInstance();
        $query = "SELECT * FROM users WHERE 1=1";
        $params = [];
        
        if (isset($filters['role'])) {
            $query .= " AND role = :role";
            $params['role'] = $filters['role'];
        }
        
        if (isset($filters['firma_id'])) {
            $query .= " AND firma_id = :firma_id";
            $params['firma_id'] = $filters['firma_id'];
        }
        
        $query .= " ORDER BY id";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        $users = [];
        while ($data = $stmt->fetch()) {
            $users[] = self::hydrate($data);
        }
        
        return $users;
    }
    
    public function update(array $data = []): bool {
        // Update object properties if provided
        if (!empty($data)) {
            if (isset($data['username'])) {
                $this->username = $data['username'];
            }
            if (isset($data['role'])) {
                $this->role = $data['role'];
            }
            if (isset($data['firma_id'])) {
                $this->firma_id = $data['firma_id'];
            }
            if (isset($data['credit'])) {
                $this->credit = $data['credit'];
            }
        }
        
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "UPDATE users SET username = :username, role = :role, 
             firma_id = :firma_id, credit = :credit WHERE id = :id"
        );
        
        return $stmt->execute([
            'username' => $this->username,
            'role' => $this->role,
            'firma_id' => $this->firma_id,
            'credit' => $this->credit,
            'id' => $this->id
        ]);
    }
    
    public function delete(): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute(['id' => $this->id]);
    }
    
    public function verifyPassword(string $password): bool {
        return password_verify($password, $this->password_hash);
    }
    
    public function addCredit(float $amount): bool {
        $this->credit += $amount;
        return $this->update();
    }
    
    public function deductCredit(float $amount): bool {
        if ($this->credit < $amount) {
            return false;
        }
        
        $this->credit -= $amount;
        return $this->update();
    }
    
    private static function hydrate(array $data): self {
        $user = new self();
        $user->id = (int) $data['id'];
        $user->username = $data['username'];
        $user->password_hash = $data['password_hash'];
        $user->role = $data['role'];
        $user->firma_id = $data['firma_id'] ? (int) $data['firma_id'] : null;
        $user->credit = (float) $data['credit'];
        return $user;
    }
}

