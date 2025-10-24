<?php
use App\Core\Auth;
use App\Core\Csrf;

ob_start();
?>

<div class="profile-page">
    <h1>Şifre Değiştir</h1>
    
    <div class="form-card">
        <p class="info-text">
            <strong>Güvenlik:</strong> Şifreler güvenli bir şekilde hashlenmiş olarak saklanır. 
            Mevcut şifrenizi göremezsiniz, ancak yeni bir şifre belirleyebilirsiniz.
        </p>
        
        <form method="POST" action="/profile/change-password" class="change-password-form">
            <?= Csrf::hidden() ?>
            
            <div class="form-group">
                <label for="current_password">Mevcut Şifre *</label>
                <input type="password" id="current_password" name="current_password" 
                       class="form-control" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="new_password">Yeni Şifre *</label>
                <input type="password" id="new_password" name="new_password" 
                       class="form-control" required minlength="8">
                <small class="form-hint">En az 8 karakter olmalıdır.</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Yeni Şifre (Tekrar) *</label>
                <input type="password" id="confirm_password" name="confirm_password" 
                       class="form-control" required minlength="8">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Şifreyi Değiştir</button>
                <a href="<?= Auth::isAdmin() ? '/admin/firms' : (Auth::isFirmaAdmin() ? '/firm-admin/trips' : '/me/tickets') ?>" 
                   class="btn btn-secondary">İptal</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
$title = 'Şifre Değiştir - Bilet Satın Alma Sistemi';
require __DIR__ . '/../layout.php';
?>


