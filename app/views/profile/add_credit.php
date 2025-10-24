<?php
use App\Core\Auth;
use App\Core\Csrf;

$user = Auth::user();

ob_start();
?>

<div class="profile-page">
    <h1>💳 Bakiye Yükle</h1>
    
    <div class="credit-info-card">
        <div class="current-balance">
            <span class="balance-label">Mevcut Bakiyeniz</span>
            <span class="balance-amount"><?= number_format($user->credit, 2) ?> TL</span>
        </div>
        
        <div class="balance-hint">
            <p>ℹ️ Bilet satın alabilmek için bakiyenize para yüklemeniz gerekmektedir.</p>
            <p>💡 <strong>Not:</strong> Bu bir demo projedir. Gerçek para transferi yapılmamaktadır.</p>
        </div>
    </div>
    
    <div class="form-card">
        <h2>Bakiye Ekle</h2>
        
        <form method="POST" action="/profile/add-credit" class="add-credit-form">
            <?= Csrf::hidden() ?>
            
            <div class="form-group">
                <label for="amount">Yüklenecek Tutar (TL) *</label>
                <input type="number" 
                       id="amount" 
                       name="amount" 
                       class="form-control amount-input" 
                       placeholder="0.00"
                       min="1" 
                       max="10000" 
                       step="0.01" 
                       required 
                       autofocus>
                <small class="form-hint">Minimum: 1 TL, Maksimum: 10,000 TL</small>
            </div>
            
            <div class="quick-amounts">
                <p class="quick-amounts-label">Hızlı Seçim:</p>
                <div class="quick-amounts-grid">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="setAmount(100)">100 TL</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="setAmount(250)">250 TL</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="setAmount(500)">500 TL</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="setAmount(1000)">1,000 TL</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="setAmount(2000)">2,000 TL</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="setAmount(5000)">5,000 TL</button>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-large">
                    ✓ Bakiye Yükle
                </button>
                <a href="/me/tickets" class="btn btn-secondary">
                    ← Biletlerime Dön
                </a>
            </div>
        </form>
    </div>
    
    <div class="info-section">
        <h3>ℹ️ Bilgilendirme</h3>
        <ul class="info-list">
            <li>Yüklediğiniz bakiye hesabınıza anında yansır</li>
            <li>Bakiyeniz ile bilet satın alabilir, iptal ettiğiniz biletlerin ücreti iade alabilirsiniz</li>
            <li>Bu bir demo/eğitim projesidir, gerçek para transferi yapılmaz</li>
            <li>Maksimum tek seferde 10,000 TL yükleyebilirsiniz</li>
        </ul>
    </div>
</div>

<script>
function setAmount(amount) {
    document.getElementById('amount').value = amount;
}
</script>

<?php
$content = ob_get_clean();
$title = 'Bakiye Yükle - Bilet Satın Alma Sistemi';
require __DIR__ . '/../layout.php';
?>




