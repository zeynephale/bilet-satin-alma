<?php
use App\Core\Csrf;

ob_start();
?>

<div class="auth-page">
    <div class="auth-card">
        <h1>Kayıt Ol</h1>
        
        <form method="POST" action="/register">
            <?= Csrf::hidden() ?>
            
            <div class="form-group">
                <label for="username">Kullanıcı Adı</label>
                <input type="text" id="username" name="username" required 
                       minlength="3" class="form-control" autofocus>
                <small>En az 3 karakter olmalıdır.</small>
            </div>
            
            <div class="form-group">
                <label for="password">Şifre</label>
                <input type="password" id="password" name="password" required 
                       minlength="8" class="form-control">
                <small>En az 8 karakter olmalıdır.</small>
            </div>
            
            <div class="form-group">
                <label for="password_confirm">Şifre Tekrar</label>
                <input type="password" id="password_confirm" name="password_confirm" required 
                       class="form-control">
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Kayıt Ol</button>
        </form>
        
        <p class="auth-link">
            Zaten hesabınız var mı? <a href="/login">Giriş yapın</a>
        </p>
    </div>
</div>

<?php
$content = ob_get_clean();
$title = 'Kayıt Ol';
require __DIR__ . '/../layout.php';
?>

