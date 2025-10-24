<?php
use App\Core\Csrf;

ob_start();
?>

<div class="admin-page">
    <h1>Kullanıcı Yönetimi</h1>
    
    <div class="create-section">
        <h2>Yeni Kullanıcı Ekle</h2>
        <p class="section-info">
            ℹ️ Yeni kullanıcı oluşturabilir veya mevcut kullanıcıyı firma admin yapabilirsiniz.
        </p>
        <form method="POST" action="/admin/users/create" class="create-form">
            <?= Csrf::hidden() ?>
            
            <div class="form-row">
                <input type="text" name="username" placeholder="Kullanıcı Adı" 
                       required minlength="3" maxlength="50" class="form-control"
                       title="Kullanıcı adı 3-50 karakter arasında olmalıdır">
                <input type="password" name="password" placeholder="Şifre (min 8 karakter)" 
                       required minlength="8" class="form-control"
                       title="Şifre en az 8 karakter olmalıdır">
                
                <select name="role" required class="form-control" id="user-role"
                        title="Kullanıcı rolü seçin">
                    <option value="">Rol Seçin</option>
                    <option value="user">User (Yolcu)</option>
                    <option value="firma_admin">Firma Admin</option>
                    <option value="admin">Admin</option>
                </select>
                
                <select name="firma_id" class="form-control" id="firma-select"
                        title="Firma admin için firma seçin">
                    <option value="">Firma Seçin (Firma Admin için)</option>
                    <?php foreach ($firms as $firm): ?>
                        <option value="<?= htmlspecialchars((string)$firm->id, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($firm->name, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <button type="submit" class="btn btn-primary">➕ Kullanıcı Ekle</button>
            </div>
        </form>
    </div>
    
    <div class="table-section">
        <h2>Kullanıcılar (<?= htmlspecialchars((string)count($users), ENT_QUOTES, 'UTF-8') ?>)</h2>
        
        <?php if (empty($users)): ?>
            <p class="no-results">Henüz kullanıcı bulunmamaktadır.</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Kullanıcı Adı</th>
                        <th>Rol</th>
                        <th>Firma</th>
                        <th>Kredi</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$user->id, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><strong><?= htmlspecialchars($user->username, ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td>
                                <span class="role-badge role-<?= htmlspecialchars($user->role, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($user->role, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                if ($user->firma_id) {
                                    $firm = array_filter($firms, fn($f) => $f->id === $user->firma_id);
                                    $firm = reset($firm);
                                    echo htmlspecialchars($firm->name ?? 'N/A', ENT_QUOTES, 'UTF-8');
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td><?= htmlspecialchars(number_format($user->credit, 2), ENT_QUOTES, 'UTF-8') ?> TL</td>
                            <td>
                                <!-- Update User Role/Firma -->
                                <button type="button" 
                                        class="btn btn-secondary btn-sm"
                                        onclick="showUpdateForm(<?= htmlspecialchars((string)$user->id, ENT_QUOTES, 'UTF-8') ?>, '<?= htmlspecialchars($user->role, ENT_QUOTES, 'UTF-8') ?>', <?= htmlspecialchars((string)($user->firma_id ?? 'null'), ENT_QUOTES, 'UTF-8') ?>)"
                                        title="Rolü/Firmayı güncelle">
                                    ✏️ Düzenle
                                </button>
                                
                                <form method="POST" 
                                      action="/admin/users/<?= htmlspecialchars((string)$user->id, ENT_QUOTES, 'UTF-8') ?>/delete" 
                                      style="display: inline;"
                                      onsubmit="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.');">
                                    <?= Csrf::hidden() ?>
                                    <button type="submit" class="btn btn-danger btn-sm" title="Kullanıcıyı sil">
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
    
    <!-- Update User Modal -->
    <div id="updateModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h3>Kullanıcı Güncelle</h3>
            <form id="updateForm" method="POST" action="">
                <?= Csrf::hidden() ?>
                
                <div class="form-group">
                    <label for="update-role">Rol</label>
                    <select name="role" id="update-role" required class="form-control">
                        <option value="user">User (Yolcu)</option>
                        <option value="firma_admin">Firma Admin</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="update-firma">Firma (Firma Admin için)</label>
                    <select name="firma_id" id="update-firma" class="form-control">
                        <option value="">Firma Seçin</option>
                        <?php foreach ($firms as $firm): ?>
                            <option value="<?= htmlspecialchars((string)$firm->id, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($firm->name, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">💾 Güncelle</button>
                    <button type="button" class="btn btn-secondary" onclick="closeUpdateForm()">❌ İptal</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function showUpdateForm(userId, role, firmaId) {
        document.getElementById('updateForm').action = '/admin/users/' + userId + '/update';
        document.getElementById('update-role').value = role;
        document.getElementById('update-firma').value = firmaId || '';
        document.getElementById('updateModal').style.display = 'flex';
    }
    
    function closeUpdateForm() {
        document.getElementById('updateModal').style.display = 'none';
    }
    </script>
</div>

<?php
$content = ob_get_clean();
$title = 'Kullanıcı Yönetimi';
require __DIR__ . '/../layout.php';
?>

