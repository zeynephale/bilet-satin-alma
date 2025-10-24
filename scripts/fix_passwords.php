<?php
/**
 * Şifre Düzeltme Script'i
 * 
 * Başka PC'de veritabanındaki şifreleri güncellemek için kullanın.
 * 
 * Kullanım:
 * docker exec bilet-satin-alma php /var/www/html/scripts/fix_passwords.php
 */

echo "\n";
echo "╔══════════════════════════════════════════╗\n";
echo "║  ŞİFRE GÜNCELLEME BAŞLIYOR               ║\n";
echo "╚══════════════════════════════════════════╝\n\n";

require __DIR__ . '/../app/core/Database.php';

use App\Core\Database;

try {
    $db = Database::getInstance();
    
    // Doğru şifreler
    $passwords = [
        'admin' => 'admin123',
        'metro_admin' => 'metro123',
        'pamukkale_admin' => 'pamukkale123',
        'kamilkoc_admin' => 'kamilkoc123',
        'yolcu1' => 'yolcu123',
        'yolcu2' => 'yolcu123',
    ];
    
    echo "Toplam " . count($passwords) . " kullanıcının şifresi güncellenecek...\n\n";
    
    foreach ($passwords as $username => $password) {
        // Yeni hash oluştur
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Veritabanını güncelle
        $stmt = $db->prepare("UPDATE users SET password_hash = :hash WHERE username = :username");
        $result = $stmt->execute(['hash' => $hash, 'username' => $username]);
        
        if ($result && $stmt->rowCount() > 0) {
            echo "✅ $username şifresi güncellendi\n";
            echo "   Şifre: $password\n";
            echo "   Hash: " . substr($hash, 0, 40) . "...\n\n";
        } else {
            echo "⚠️  $username bulunamadı veya zaten güncel\n\n";
        }
    }
    
    echo "╔══════════════════════════════════════════╗\n";
    echo "║  ✅ TÜM ŞİFRELER GÜNCELLENDİ!           ║\n";
    echo "╚══════════════════════════════════════════╝\n\n";
    
    echo "Şimdi test edin:\n";
    echo "  URL: http://localhost:8080/login\n";
    echo "  Kullanıcı: admin\n";
    echo "  Şifre: admin123\n\n";
    
} catch (Exception $e) {
    echo "❌ HATA: " . $e->getMessage() . "\n";
    exit(1);
}

