<?php
use App\Core\Session;

if (Session::hasFlash('success')): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars(Session::flash('success'), ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if (Session::hasFlash('error')): ?>
    <div class="alert alert-error">
        <?= htmlspecialchars(Session::flash('error'), ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

