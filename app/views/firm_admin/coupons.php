<?php
use App\Core\Csrf;

ob_start();
?>

<div class="admin-page">
    <h1>Kupon Yönetimi</h1>
    
    <div class="create-section">
        <h2>Yeni Firma-Özel Kupon Ekle</h2>
        <p class="section-info">
            ℹ️ Bu kupon sadece firmanıza ait seferlerde kullanılabilecektir (Global değil).
        </p>
        <form method="POST" action="/firm-admin/coupons/create" class="create-form">
            <?= Csrf::hidden() ?>
            
            <div class="form-row">
                <input type="text" name="code" placeholder="Kupon Kodu (örn: SUMMER20)" 
                       required maxlength="50" minlength="3" class="form-control"
                       pattern="[A-Z0-9]+" 
                       title="Sadece büyük harf ve rakam kullanın">
                <input type="number" name="discount_percent" placeholder="İndirim %" 
                       min="1" max="100" step="0.01" required class="form-control"
                       title="İndirim oranı (1-100)">
                <input type="number" name="usage_limit" placeholder="Kullanım Limiti" 
                       min="1" max="10000" required class="form-control"
                       title="Maksimum kullanım sayısı (1-10000)">
                <input type="date" name="expiry_date" required class="form-control"
                       min="<?= date('Y-m-d') ?>"
                       title="Son kullanma tarihi">
                <button type="submit" class="btn btn-primary">➕ Kupon Ekle</button>
            </div>
        </form>
    </div>
    
    <div class="table-section">
        <h2>Firma-Özel Kuponlarım (<?= htmlspecialchars((string)count($coupons), ENT_QUOTES, 'UTF-8') ?>)</h2>
        
        <?php if (empty($coupons)): ?>
            <p class="no-results">Henüz firma-özel kupon bulunmamaktadır. Yukarıdaki formu kullanarak yeni kupon ekleyebilirsiniz.</p>
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
                                <?php if ($coupon->isValid(App\Core\Auth::firmaId())): ?>
                                    <span class="status-active">✓ Aktif</span>
                                <?php else: ?>
                                    <span class="status-expired">✗ Geçersiz</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" 
                                      action="/firm-admin/coupons/<?= htmlspecialchars((string)$coupon->id, ENT_QUOTES, 'UTF-8') ?>/delete" 
                                      style="display: inline;"
                                      onsubmit="return confirm('Bu kuponu silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.');">
                                    <?= Csrf::hidden() ?>
                                    <button type="submit" class="btn btn-danger btn-sm" title="Kuponu sil">
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
$title = 'Kupon Yönetimi';
require __DIR__ . '/../layout.php';
?>

