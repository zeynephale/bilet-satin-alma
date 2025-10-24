<?php
use App\Core\Auth;
use App\Core\Csrf;

ob_start();
?>

<div class="trip-detail">
    <h1>Sefer Detayı</h1>
    
    <div class="detail-card">
        <div class="detail-header">
            <h2><?= htmlspecialchars($trip->from_city, ENT_QUOTES, 'UTF-8') ?> → <?= htmlspecialchars($trip->to_city, ENT_QUOTES, 'UTF-8') ?></h2>
            <p class="firm-name">Firma: <?= htmlspecialchars($firm->name ?? 'Bilinmiyor', ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        
        <div class="detail-info">
            <div class="info-item">
                <strong>Tarih:</strong> <?= htmlspecialchars($trip->date, ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="info-item">
                <strong>Saat:</strong> <?= htmlspecialchars($trip->time, ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="info-item">
                <strong>Fiyat:</strong> <?= number_format($trip->price, 2) ?> TL
            </div>
            <div class="info-item">
                <strong>Müsait Koltuk:</strong> <?= $availableSeats ?> / <?= $trip->seats ?>
            </div>
        </div>
        
        <div class="seat-selection">
            <h3>Koltuk Durumu</h3>
            <div class="seat-legend">
                <span class="legend-item"><span class="seat-indicator available"></span> Müsait</span>
                <span class="legend-item"><span class="seat-indicator occupied"></span> Dolu</span>
                <span class="legend-item"><span class="seat-indicator selected"></span> Seçili</span>
            </div>
            
            <?php if (Auth::isUser()): ?>
                <form method="POST" action="/tickets/purchase" id="purchase-form">
                    <?= Csrf::hidden() ?>
                    <input type="hidden" name="trip_id" value="<?= $trip->id ?>">
                    <input type="hidden" name="seat_number" id="selected-seat" value="">
                    
                    <div class="bus-container">
                        <div class="bus-header">
                            <div class="bus-driver">🚗</div>
                            <span class="bus-type-badge"><?= htmlspecialchars($trip->bus_type, ENT_QUOTES, 'UTF-8') ?> Otobüs</span>
                        </div>
                        
                        <div class="bus-body">
                            <?php
                            $layout = $trip->getBusLayout();
                            $arrangement = $trip->getSeatArrangement();
                            $seatNumber = 1;
                            
                            for ($row = 0; $row < $layout['rows']; $row++):
                            ?>
                                <div class="bus-row">
                                    <?php for ($col = 0; $col < $layout['columns']; $col++): ?>
                                        <?php if ($seatNumber <= $trip->seats): ?>
                                            <?php 
                                            $isOccupied = in_array($seatNumber, $occupiedSeats);
                                            $seatClass = 'bus-seat ' . ($isOccupied ? 'occupied' : 'available');
                                            ?>
                                            <button type="button" 
                                                    class="<?= $seatClass ?>" 
                                                    data-seat="<?= $seatNumber ?>"
                                                    <?= $isOccupied ? 'disabled' : '' ?>
                                                    title="<?= $isOccupied ? 'Koltuk ' . $seatNumber . ' - Dolu' : 'Koltuk ' . $seatNumber . ' - Müsait' ?>">
                                                <span class="seat-icon">💺</span>
                                                <span class="seat-number"><?= $seatNumber ?></span>
                                            </button>
                                            <?php $seatNumber++; ?>
                                        <?php endif; ?>
                                        
                                        <?php if (($col + 1) == $layout['aisle_after'] && $col + 1 < $layout['columns']): ?>
                                            <div class="bus-aisle"></div>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div class="coupon-section">
                        <label for="coupon_code">Kupon Kodu (Opsiyonel)</label>
                        <input type="text" id="coupon_code" name="coupon_code" 
                               class="form-control" placeholder="Örn: WELCOME20"
                               maxlength="50">
                        <small class="form-hint">Global veya firma kuponlarını kullanabilirsiniz.</small>
                    </div>
                    
                    <div class="selected-info" id="selected-info" style="display: none;">
                        <p>Seçilen Koltuk: <strong id="seat-display"></strong></p>
                        <p class="price-info">Bilet Fiyatı: <?= number_format($trip->price, 2) ?> TL</p>
                        <p class="credit-info">Mevcut Bakiyeniz: <?= number_format(App\Core\Auth::user()->credit, 2) ?> TL</p>
                        <button type="submit" class="btn btn-primary btn-block">Satın Al</button>
                        <p class="purchase-note">💳 Ödeme kredinizden düşecektir.</p>
                    </div>
                </form>
            <?php else: ?>
                <div class="seats-grid-readonly">
                    <?php for ($i = 1; $i <= $trip->seats; $i++): ?>
                        <?php 
                        $isOccupied = in_array($i, $occupiedSeats);
                        $seatClass = $isOccupied ? 'seat occupied' : 'seat available';
                        ?>
                        <div class="<?= $seatClass ?>" title="Koltuk <?= $i ?>">
                            <?= $i ?>
                        </div>
                    <?php endfor; ?>
                </div>
                
                <div class="auth-required">
                    <p class="warning-icon">⚠️</p>
                    <h3>Lütfen Giriş Yapın</h3>
                    <p>Bilet satın almak için sisteme giriş yapmalısınız.</p>
                    <a href="/login?redirect=<?= urlencode('/trips/' . $trip->id) ?>" class="btn btn-primary">Giriş Yap</a>
                    <p class="register-link">Hesabınız yok mu? <a href="/register">Kayıt olun</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$title = 'Sefer Detayı';
require __DIR__ . '/../layout.php';
?>

