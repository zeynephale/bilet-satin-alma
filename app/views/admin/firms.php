<?php
use App\Core\Csrf;

ob_start();
?>

<div class="admin-page">
    <h1>Firma Yönetimi</h1>
    
    <?php
    // Display new firm credentials once (then cleared)
    $newCreds = \App\Core\Session::get('new_firm_credentials');
    if ($newCreds):
        \App\Core\Session::remove('new_firm_credentials');
    ?>
        <div class="alert alert-success" style="margin-bottom: 20px; padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;">
            <h3 style="margin-top: 0; color: #155724;">✅ Firma ve Admin Oluşturuldu!</h3>
            <p style="margin: 10px 0;"><strong>Firma:</strong> <?= htmlspecialchars($newCreds['firm_name'], ENT_QUOTES, 'UTF-8') ?></p>
            <p style="margin: 10px 0;"><strong>Kullanıcı Adı:</strong> <code style="background: #fff; padding: 2px 6px; border-radius: 3px;"><?= htmlspecialchars($newCreds['username'], ENT_QUOTES, 'UTF-8') ?></code></p>
            <p style="margin: 10px 0;"><strong>Şifre:</strong> <code style="background: #fff; padding: 2px 6px; border-radius: 3px;"><?= htmlspecialchars($newCreds['password'], ENT_QUOTES, 'UTF-8') ?></code></p>
            <p style="margin: 10px 0 0 0; color: #856404; background: #fff3cd; padding: 10px; border-radius: 3px; border-left: 4px solid #ffc107;">
                ⚠️ <strong>ÖNEMLİ:</strong> Bu bilgileri not alın! Bu mesaj yalnızca bir kez gösterilecektir.
            </p>
        </div>
    <?php endif; ?>
    
    <div class="create-section">
        <h2>Yeni Firma Ekle</h2>
        <p class="section-info">
            ℹ️ Firma adı ve admin kullanıcı adı benzersiz (UNIQUE) olmalıdır.
        </p>
        <form method="POST" action="/admin/firms/create" class="firm-create-form">
            <?= Csrf::hidden() ?>
            
            <div class="form-group">
                <label for="firm_name">Firma Adı *</label>
                <input type="text" id="firm_name" name="name" 
                       placeholder="Örn: Metro Turizm" 
                       required minlength="2" maxlength="100" 
                       class="form-control"
                       title="Firma adı 2-100 karakter arasında olmalıdır">
            </div>
            
            <div class="form-group">
                <label for="admin_username">Firma Admin Kullanıcı Adı *</label>
                <input type="text" id="admin_username" name="admin_username" 
                       placeholder="Örn: metro_admin" 
                       required minlength="3" maxlength="50" 
                       class="form-control"
                       title="Kullanıcı adı 3-50 karakter arasında olmalıdır">
            </div>
            
            <div class="form-group">
                <label for="admin_password">Firma Admin Şifresi *</label>
                <input type="password" id="admin_password" name="admin_password" 
                       placeholder="En az 8 karakter" 
                       required minlength="8" 
                       class="form-control"
                       title="Şifre en az 8 karakter olmalıdır">
            </div>
            
            <button type="submit" class="btn btn-primary">➕ Firma ve Admin Oluştur</button>
        </form>
    </div>
    
    <div class="table-section">
        <h2>Firmalar (<?= htmlspecialchars((string)count($firms), ENT_QUOTES, 'UTF-8') ?>)</h2>
        
        <?php if (empty($firms)): ?>
            <p class="no-results">Henüz firma bulunmamaktadır. Yukarıdaki formu kullanarak yeni firma ekleyebilirsiniz.</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Firma Adı</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($firms as $firm): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$firm->id, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><strong><?= htmlspecialchars($firm->name, ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td>
                                <form method="POST" 
                                      action="/admin/firms/<?= htmlspecialchars((string)$firm->id, ENT_QUOTES, 'UTF-8') ?>/delete" 
                                      style="display: inline;"
                                      onsubmit="return confirm('Bu firmayı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.');">
                                    <?= Csrf::hidden() ?>
                                    <button type="submit" class="btn btn-danger btn-sm" title="Firmayı sil">
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
$title = 'Firma Yönetimi';
require __DIR__ . '/../layout.php';
?>

