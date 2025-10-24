<?php
use App\Core\Csrf;

ob_start();
?>

<div class="auth-page">
    <div class="auth-card">
        <h1>Giriş Yap</h1>
        
        <form method="POST" action="/login">
            <?= Csrf::hidden() ?>
            
            <?php if (!empty($_GET['redirect'])): ?>
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($_GET['redirect'], ENT_QUOTES, 'UTF-8') ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="username">Kullanıcı Adı</label>
                <input type="text" id="username" name="username" required 
                       class="form-control" autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Şifre</label>
                <input type="password" id="password" name="password" required 
                       class="form-control">
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Giriş Yap</button>
        </form>
        
        <p class="auth-link">
            Hesabınız yok mu? <a href="/register">Kayıt olun</a>
        </p>
    </div>
</div>

<?php
$content = ob_get_clean();
$title = 'Giriş Yap';
require __DIR__ . '/../layout.php';
?>

