<?php
use App\Core\Csrf;

ob_start();
?>

<div class="tickets-page">
    <h1>🎫 Biletlerim</h1>
    
    <div class="page-info">
        <p>Kullanıcı ID: <?= App\Core\Auth::id() ?></p>
        <p>Toplam Bilet: <?= count($tickets) ?></p>
    </div>
    
    <?php if (empty($tickets)): ?>
        <div class="empty-state">
            <p class="empty-icon">🎫</p>
            <p class="empty-text">Henüz biletiniz bulunmamaktadır.</p>
            <p class="empty-hint">Sefer arayıp bilet satın alabilirsiniz.</p>
            <a href="/" class="btn btn-primary btn-large">🔍 Sefer Ara</a>
        </div>
    <?php else: ?>
        <div class="tickets-list">
            <?php foreach ($tickets as $ticket): ?>
                <?php 
                $trip = $ticket->getTrip();
                if (!$trip) continue;
                $firm = \App\Models\Firm::find($trip->firma_id);
                ?>
                
                <div class="ticket-card <?= $ticket->status === 'cancelled' ? 'cancelled' : '' ?>">
                    <div class="ticket-header">
                        <h3><?= htmlspecialchars($trip->from_city, ENT_QUOTES, 'UTF-8') ?> → <?= htmlspecialchars($trip->to_city, ENT_QUOTES, 'UTF-8') ?></h3>
                        <span class="ticket-status status-<?= $ticket->status ?>">
                            <?= $ticket->status === 'active' ? 'Aktif' : 'İptal Edildi' ?>
                        </span>
                    </div>
                    
                    <div class="ticket-info">
                        <p><strong>Bilet No:</strong> #<?= htmlspecialchars((string)$ticket->id, ENT_QUOTES, 'UTF-8') ?></p>
                        <p><strong>Firma:</strong> <?= htmlspecialchars($firm->name ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></p>
                        <p><strong>Tarih:</strong> <?= htmlspecialchars($trip->date, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($trip->time, ENT_QUOTES, 'UTF-8') ?></p>
                        <p><strong>Koltuk:</strong> <?= htmlspecialchars((string)$ticket->seat_number, ENT_QUOTES, 'UTF-8') ?></p>
                        <p><strong>Fiyat:</strong> <?= htmlspecialchars(number_format($trip->price, 2), ENT_QUOTES, 'UTF-8') ?> TL</p>
                        <p><strong>Satın Alma:</strong> <?= htmlspecialchars($ticket->created_at, ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    
                    <div class="ticket-actions">
                        <a href="/tickets/<?= htmlspecialchars((string)$ticket->id, ENT_QUOTES, 'UTF-8') ?>" 
                           class="btn btn-secondary" 
                           title="Bilet detaylarını görüntüle">
                            Görüntüle
                        </a>
                        <a href="/tickets/<?= htmlspecialchars((string)$ticket->id, ENT_QUOTES, 'UTF-8') ?>/pdf" 
                           class="btn btn-secondary"
                           title="PDF olarak indir">
                            PDF İndir
                        </a>
                        
                        <?php if ($ticket->canBeCancelled()): ?>
                            <form method="POST" 
                                  action="/tickets/<?= htmlspecialchars((string)$ticket->id, ENT_QUOTES, 'UTF-8') ?>/cancel" 
                                  style="display: inline;"
                                  onsubmit="return confirm('Bu bileti iptal etmek istediğinizden emin misiniz? Ücret kredinize iade edilecektir.');">
                                <?= Csrf::hidden() ?>
                                <button type="submit" class="btn btn-danger" title="Bileti iptal et">İptal Et</button>
                            </form>
                        <?php elseif ($ticket->status === 'active'): ?>
                            <button class="btn btn-disabled" disabled title="İptal edilemez (seferden 1 saatten az kaldı)">
                                İptal Edilemez
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
$title = 'Biletlerim';
require __DIR__ . '/../layout.php';
?>

