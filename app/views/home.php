<?php
use App\Core\Csrf;
use App\Models\Firm;

ob_start();
?>

<div class="home-page">
    <h1>Otobüs Bileti Ara</h1>
    
    <form method="GET" action="/" class="search-form">
        <div class="form-group">
            <label for="from_city">Nereden</label>
            <select id="from_city" name="from_city" class="form-control">
                <option value="">Tüm Şehirler</option>
                <?php 
                $selectedFrom = $_GET['from_city'] ?? '';
                foreach ($cities as $city): 
                    $selected = ($city === $selectedFrom) ? 'selected' : '';
                ?>
                    <option value="<?= htmlspecialchars($city, ENT_QUOTES, 'UTF-8') ?>" <?= $selected ?>>
                        <?= htmlspecialchars($city, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="to_city">Nereye</label>
            <select id="to_city" name="to_city" class="form-control">
                <option value="">Tüm Şehirler</option>
                <?php 
                $selectedTo = $_GET['to_city'] ?? '';
                foreach ($cities as $city): 
                    $selected = ($city === $selectedTo) ? 'selected' : '';
                ?>
                    <option value="<?= htmlspecialchars($city, ENT_QUOTES, 'UTF-8') ?>" <?= $selected ?>>
                        <?= htmlspecialchars($city, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="date">Tarih</label>
            <input type="date" id="date" name="date" 
                   value="<?= htmlspecialchars($_GET['date'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                   placeholder="Tümü" class="form-control">
        </div>
        
        <button type="submit" class="btn btn-primary">Sefer Ara</button>
    </form>
    
    <?php if (!empty($trips)): ?>
        <h2>Bulunan Seferler (<?= count($trips) ?>)</h2>
        
        <div class="trips-list">
            <?php foreach ($trips as $trip): ?>
                <?php 
                $firm = Firm::find($trip->firma_id);
                $firmName = $firm ? htmlspecialchars($firm->name, ENT_QUOTES, 'UTF-8') : 'Bilinmeyen Firma';
                ?>
                <div class="trip-card">
                    <div class="trip-info">
                        <div class="trip-header">
                            <h3><?= htmlspecialchars($trip->from_city, ENT_QUOTES, 'UTF-8') ?> → <?= htmlspecialchars($trip->to_city, ENT_QUOTES, 'UTF-8') ?></h3>
                            <span class="firm-badge"><?= $firmName ?></span>
                        </div>
                        <div class="trip-details">
                            <p><strong>Tarih:</strong> <?= htmlspecialchars($trip->date, ENT_QUOTES, 'UTF-8') ?></p>
                            <p><strong>Saat:</strong> <?= htmlspecialchars($trip->time, ENT_QUOTES, 'UTF-8') ?></p>
                            <p><strong>Fiyat:</strong> <?= number_format($trip->price, 2) ?> TL</p>
                            <p><strong>Müsait Koltuk:</strong> <?= $trip->getAvailableSeatsCount() ?> / <?= $trip->seats ?></p>
                        </div>
                    </div>
                    <div class="trip-actions">
                        <a href="/trips/<?= $trip->id ?>" class="btn btn-primary">Detay</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php elseif (isset($_GET['from_city']) || isset($_GET['to_city']) || isset($_GET['date'])): ?>
        <p class="no-results">Arama kriterlerinize uygun sefer bulunamadı.</p>
    <?php else: ?>
        <div class="welcome-section">
            <p>Otobüs seferlerini aramak için yukarıdaki formu kullanın.</p>
            <p>Tüm seferleri görmek için herhangi bir alan doldurmadan "Sefer Ara" butonuna tıklayın.</p>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
$title = 'Ana Sayfa - Bilet Satın Alma Sistemi';
require __DIR__ . '/layout.php';
?>

