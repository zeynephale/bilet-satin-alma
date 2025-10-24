<?php
use App\Core\Csrf;
use App\Helpers\TurkeyCities;

$cities = TurkeyCities::all();

ob_start();
?>

<div class="admin-page">
    <h1>Sefer Yönetimi</h1>
    
    <div class="create-section">
        <h2>➕ Yeni Sefer Ekle</h2>
        <form method="POST" action="/firm-admin/trips/create" class="create-trip-form">
            <?= Csrf::hidden() ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="from_city">Nereden *</label>
                    <select name="from_city" id="from_city" required class="form-control">
                        <option value="">Kalkış Şehri Seçin</option>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?= htmlspecialchars($city, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($city, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="to_city">Nereye *</label>
                    <select name="to_city" id="to_city" required class="form-control">
                        <option value="">Varış Şehri Seçin</option>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?= htmlspecialchars($city, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($city, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="date">Tarih *</label>
                    <input type="date" name="date" id="date" required class="form-control"
                           min="<?= date('Y-m-d') ?>">
                </div>
                
                <div class="form-group">
                    <label for="time">Saat *</label>
                    <input type="time" name="time" id="time" required class="form-control">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="bus_type">Otobüs Tipi *</label>
                    <select name="bus_type" id="bus_type" required class="form-control" onchange="updateSeatsBasedOnBusType()">
                        <option value="">Seçiniz</option>
                        <option value="2+1">2+1 (Lüks - 36 koltuk)</option>
                        <option value="2+2" selected>2+2 (Standart - 44 koltuk)</option>
                        <option value="3+2">3+2 (Ekonomik - 45 koltuk)</option>
                    </select>
                    <small class="form-hint">Otobüs tipine göre koltuk düzeni belirlenir</small>
                </div>
                
                <div class="form-group">
                    <label for="seats">Koltuk Sayısı *</label>
                    <input type="number" name="seats" id="seats" placeholder="44" 
                           min="1" max="50" required class="form-control" value="44">
                    <small class="form-hint" id="seats-hint">2+2 otobüs için önerilen: 44</small>
                </div>
                
                <div class="form-group">
                    <label for="price">Fiyat (TL) *</label>
                    <input type="number" name="price" id="price" placeholder="250.00" 
                           min="1" max="10000" step="0.01" required class="form-control">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-large">✓ Sefer Oluştur</button>
            </div>
        </form>
    </div>
    
    <div class="table-section">
        <h2>Seferlerim (<?= htmlspecialchars((string)count($trips), ENT_QUOTES, 'UTF-8') ?>)</h2>
        
        <?php if (empty($trips)): ?>
            <p class="no-results">Henüz sefer bulunmamaktadır. Yukarıdaki formu kullanarak yeni sefer ekleyebilirsiniz.</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Güzergah</th>
                        <th>Tarih</th>
                        <th>Saat</th>
                        <th>Fiyat</th>
                        <th>Koltuklar</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trips as $trip): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$trip->id, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($trip->from_city, ENT_QUOTES, 'UTF-8') ?> → <?= htmlspecialchars($trip->to_city, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($trip->date, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($trip->time, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(number_format($trip->price, 2), ENT_QUOTES, 'UTF-8') ?> TL</td>
                            <td>
                                <span title="Müsait / Toplam">
                                    <?= htmlspecialchars((string)$trip->getAvailableSeatsCount(), ENT_QUOTES, 'UTF-8') ?> / 
                                    <?= htmlspecialchars((string)$trip->seats, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td>
                                <a href="/trips/<?= htmlspecialchars((string)$trip->id, ENT_QUOTES, 'UTF-8') ?>" 
                                   class="btn btn-secondary btn-sm"
                                   title="Sefer detaylarını görüntüle">
                                    👁️ Görüntüle
                                </a>
                                <form method="POST" 
                                      action="/firm-admin/trips/<?= htmlspecialchars((string)$trip->id, ENT_QUOTES, 'UTF-8') ?>/delete" 
                                      style="display: inline;"
                                      onsubmit="return confirm('Bu seferi silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.');">
                                    <?= Csrf::hidden() ?>
                                    <button type="submit" class="btn btn-danger btn-sm" title="Seferi sil">
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
$title = 'Sefer Yönetimi';
require __DIR__ . '/../layout.php';
?>

