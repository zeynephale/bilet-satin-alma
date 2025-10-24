<?php
use App\Core\Csrf;

ob_start();

$firm = $trip ? \App\Models\Firm::find($trip->firma_id) : null;
?>

<div class="ticket-detail">
    <h1>Bilet Detayı</h1>
    
    <div class="detail-card ticket-view">
        <div class="ticket-number">
            <strong>Bilet No:</strong> #<?= htmlspecialchars((string)$ticket->id, ENT_QUOTES, 'UTF-8') ?>
        </div>
        
        <div class="status-badge status-<?= htmlspecialchars($ticket->status, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($ticket->status === 'active' ? 'Aktif' : 'İptal Edildi', ENT_QUOTES, 'UTF-8') ?>
        </div>
        
        <div class="detail-info">
            <h2><?= htmlspecialchars($trip->from_city, ENT_QUOTES, 'UTF-8') ?> → <?= htmlspecialchars($trip->to_city, ENT_QUOTES, 'UTF-8') ?></h2>
            
            <div class="info-grid">
                <div class="info-item">
                    <strong>Firma:</strong> <?= htmlspecialchars($firm->name ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>
                </div>
                <div class="info-item">
                    <strong>Tarih:</strong> <?= htmlspecialchars($trip->date, ENT_QUOTES, 'UTF-8') ?>
                </div>
                <div class="info-item">
                    <strong>Saat:</strong> <?= htmlspecialchars($trip->time, ENT_QUOTES, 'UTF-8') ?>
                </div>
                <div class="info-item">
                    <strong>Koltuk No:</strong> <?= htmlspecialchars((string)$ticket->seat_number, ENT_QUOTES, 'UTF-8') ?>
                </div>
                <div class="info-item">
                    <strong>Fiyat:</strong> <?= htmlspecialchars(number_format($trip->price, 2), ENT_QUOTES, 'UTF-8') ?> TL
                </div>
                <div class="info-item">
                    <strong>Satın Alma:</strong> <?= htmlspecialchars($ticket->created_at, ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
        </div>
        
        <div class="ticket-actions">
            <a href="/me/tickets" class="btn btn-secondary" title="Biletlerime geri dön">
                ← Biletlerime Dön
            </a>
            <a href="/tickets/<?= htmlspecialchars((string)$ticket->id, ENT_QUOTES, 'UTF-8') ?>/pdf" 
               class="btn btn-primary"
               title="Bu bileti PDF olarak indir">
                📄 PDF İndir
            </a>
            
            <?php if ($ticket->canBeCancelled()): ?>
                <form method="POST" 
                      action="/tickets/<?= htmlspecialchars((string)$ticket->id, ENT_QUOTES, 'UTF-8') ?>/cancel" 
                      style="display: inline;"
                      onsubmit="return confirm('Bu bileti iptal etmek istediğinizden emin misiniz? Ücret kredinize iade edilecektir.');">
                    <?= Csrf::hidden() ?>
                    <button type="submit" class="btn btn-danger" title="Bileti iptal et ve ücret iadesi al">
                        ❌ İptal Et
                    </button>
                </form>
            <?php elseif ($ticket->status === 'active'): ?>
                <p class="warning-text">⚠️ Bu bilet iptal edilemez (seferden 1 saatten az süre kaldı).</p>
            <?php elseif ($ticket->status === 'cancelled'): ?>
                <p class="info-text">ℹ️ Bu bilet iptal edilmiştir.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$title = 'Bilet Detayı';
require __DIR__ . '/../layout.php';
?>

