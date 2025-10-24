<?php

namespace App\Core;

use PDO;
use PDOException;

class Database {
    private static ?PDO $instance = null;
    private static bool $inTransaction = false;
    
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            try {
                $dbPath = $_ENV['DB_PATH'] ?? __DIR__ . '/../../data/database.sqlite';
                
                // Dizin yoksa oluştur
                $dir = dirname($dbPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                
                self::$instance = new PDO(
                    'sqlite:' . $dbPath,
                    null,
                    null,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
                
                // SQLite için foreign keys aktif et
                self::$instance->exec('PRAGMA foreign_keys = ON');
                
                // SQLite transaction ayarları
                self::$instance->exec('PRAGMA journal_mode = WAL');
                self::$instance->exec('PRAGMA synchronous = NORMAL');

                // İlk kurulum: tablo yoksa şemayı yükle (users tablosu baz alınır)
                try {
                    $checkStmt = self::$instance->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
                    $usersTableExists = $checkStmt && $checkStmt->fetch() !== false;
                    if (!$usersTableExists) {
                        $schemaPath = __DIR__ . '/../../database/schema.sql';
                        if (file_exists($schemaPath)) {
                            $schemaSql = file_get_contents($schemaPath);
                            if ($schemaSql !== false) {
                                self::$instance->exec($schemaSql);
                            }
                        }
                        $seedPath = __DIR__ . '/../../database/seed.sql';
                        if (file_exists($seedPath)) {
                            $seedSql = file_get_contents($seedPath);
                            if ($seedSql !== false) {
                                self::$instance->exec($seedSql);
                            }
                        }
                    }
                } catch (PDOException $initEx) {
                    // Kurulum hataları bağlantıyı engellemesin; logla ve devam et
                    error_log('Database init error: ' . $initEx->getMessage());
                }
                
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                throw new \Exception("Database connection failed");
            }
        }
        
        return self::$instance;
    }
    
    public static function beginTransaction(): bool {
        // Use PDO's native beginTransaction for compatibility
        $db = self::getInstance();
        
        // Check our internal flag first
        if (self::$inTransaction) {
            error_log("Transaction already active (internal flag)");
            return true;
        }
        
        // Double-check PDO state
        if ($db->inTransaction()) {
            error_log("Transaction already active (PDO state)");
            self::$inTransaction = true;
            return true;
        }
        
        try {
            $result = $db->beginTransaction();
            if ($result) {
                self::$inTransaction = true;
                error_log("Transaction started successfully");
            } else {
                error_log("Transaction start returned false");
            }
            return $result;
        } catch (\PDOException $e) {
            error_log("Transaction begin failed: " . $e->getMessage());
            return false;
        }
    }
    
    public static function commit(): bool {
        $db = self::getInstance();
        
        if (!self::$inTransaction) {
            error_log("Warning: Attempted to commit when no transaction is active (internal flag)");
            return false;
        }
        
        if (!$db->inTransaction()) {
            error_log("Warning: Attempted to commit when no transaction is active (PDO state)");
            self::$inTransaction = false; // Sync flag
            return false;
        }
        
        try {
            $result = $db->commit();
            if ($result) {
                self::$inTransaction = false;
                error_log("Transaction committed successfully");
            } else {
                error_log("Transaction commit returned false");
            }
            return $result;
        } catch (\PDOException $e) {
            error_log("Transaction commit failed: " . $e->getMessage());
            self::$inTransaction = false; // Reset flag on error
            return false;
        }
    }
    
    public static function rollback(): bool {
        $db = self::getInstance();
        
        if (!self::$inTransaction) {
            error_log("Warning: Attempted to rollback when no transaction is active (internal flag)");
            return false;
        }
        
        if (!$db->inTransaction()) {
            error_log("Warning: Attempted to rollback when no transaction is active (PDO state)");
            self::$inTransaction = false; // Sync flag
            return false;
        }
        
        try {
            $result = $db->rollBack();
            if ($result) {
                self::$inTransaction = false;
                error_log("Transaction rolled back successfully");
            } else {
                error_log("Transaction rollback returned false");
            }
            return $result;
        } catch (\PDOException $e) {
            error_log("Transaction rollback failed: " . $e->getMessage());
            self::$inTransaction = false; // Reset flag on error
            return false;
        }
    }
    
    public static function isInTransaction(): bool {
        return self::$inTransaction;
    }
}

