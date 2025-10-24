<?php
use App\Core\Auth;

$currentUser = Auth::user();
?>

<nav class="navbar">
    <div class="container">
        <div class="nav-brand">
            <a href="/">🚌 Bilet Sistemi</a>
        </div>
        
        <ul class="nav-menu">
            <li><a href="/">Ana Sayfa</a></li>
            
            <?php if ($currentUser): ?>
                <?php if ($currentUser->role === 'admin'): ?>
                    <li><a href="/admin/firms">Firmalar</a></li>
                    <li><a href="/admin/users">Kullanıcılar</a></li>
                    <li><a href="/admin/coupons">Global Kuponlar</a></li>
                <?php elseif ($currentUser->role === 'firma_admin'): ?>
                    <li><a href="/firm-admin/trips">Seferlerim</a></li>
                    <li><a href="/firm-admin/coupons">Kuponlarım</a></li>
                <?php else: ?>
                    <li><a href="/me/tickets">Biletlerim</a></li>
                    <li><a href="/profile/add-credit" class="balance-link">💳 Bakiye: <?= number_format($currentUser->credit, 2) ?> TL</a></li>
                <?php endif; ?>
                
                <li><a href="/profile/change-password">Şifre Değiştir</a></li>
                <li class="user-info">Hoş geldin, <?= htmlspecialchars($currentUser->username, ENT_QUOTES, 'UTF-8') ?></li>
                <li>
                    <form method="POST" action="/logout" style="display: inline;">
                        <?= \App\Core\Csrf::hidden() ?>
                        <button type="submit" class="btn-link">Çıkış</button>
                    </form>
                </li>
            <?php else: ?>
                <li><a href="/login">Giriş</a></li>
                <li><a href="/register">Kayıt Ol</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

