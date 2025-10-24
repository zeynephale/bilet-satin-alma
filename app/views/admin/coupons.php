<?php
use App\Core\Csrf;

ob_start();
?>

<div class="admin-page">
    <h1>Global Kupon Yönetimi</h1>
    
    <div class="create-section">
        <h2>Yeni Global Kupon Ekle</h2>
        <p class="section-info">
            ℹ️ Bu kupon TÜM firmalarda kullanılabilir (is_global=1, firma_id=NULL).
        </p>
        <form method="POST" action="/admin/coupons/create" class="create-form">
            <?= Csrf::hidden() ?>
            
            <div class="form-row">
                <input type="text" name="code" placeholder="Kupon Kodu (örn: NEWYEAR2025)" 
                       required minlength="3" maxlength="50" class="form-control"
                       pattern="[A-Z0-9]+"
                       title="Sadece büyük harf ve rakam kullanın">
                <input type="number" name="discount_percent" placeholder="İndirim %" 
                       min="1" max="100" step="0.01" required class="form-control"
                       title="İndirim oranı (1-100)">
                <input type="number" name="usage_limit" placeholder="Kullanım Limiti" 
                       min="1" max="100000" required class="form-control"
                       title="Maksimum kullanım sayısı (1-100000)">
                <input type="date" name="expiry_date" required class="form-control"
                       min="<?= date('Y-m-d') ?>"
                       title="Son kullanma tarihi">
                <button type="submit" class="btn btn-primary">➕ Global Kupon Ekle</button>
            </div>
        </form>
    </div>
    
    <div class="table-section">
        <h2>Global Kuponlar (<?= htmlspecialchars((string)count($coupons), ENT_QUOTES, 'UTF-8') ?>)</h2>
        
        <?php if (empty($coupons)): ?>
            <p class="no-results">Henüz global kupon bulunmamaktadır. Yukarıdaki formu kullanarak yeni global kupon ekleyebilirsiniz.</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Kod</th>
                        <th>İndirim %</th>
                        <th>Kalan Kullanım</th>
                        <th>Kullanılan</th>
                        <th>Son Kullanma</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($coupons as $coupon): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$coupon->id, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><strong><?= htmlspecialchars($coupon->code, ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td><?= htmlspecialchars(number_format($coupon->discount_percent, 2), ENT_QUOTES, 'UTF-8') ?>%</td>
                            <td><?= htmlspecialchars((string)$coupon->usage_limit, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)$coupon->used_count, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($coupon->expiry_date, ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php if ($coupon->isValid()): ?>
                                    <span class="status-active">✓ Aktif</span>
                                <?php else: ?>
                                    <span class="status-expired">✗ Geçersiz</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" 
                                      action="/admin/coupons/<?= htmlspecialchars((string)$coupon->id, ENT_QUOTES, 'UTF-8') ?>/delete" 
                                      style="display: inline;"
                                      onsubmit="return confirm('Bu global kuponu silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.');">
                                    <?= Csrf::hidden() ?>
                                    <button type="submit" class="btn btn-danger btn-sm" title="Global kuponu sil">
                                        🗑️ Sil
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
$title = 'Global Kupon Yönetimi';
require __DIR__ . '/../layout.php';
?>

