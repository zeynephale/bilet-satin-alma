<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Bilet Satın Alma Sistemi', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <?php require __DIR__ . '/partials/nav.php'; ?>
    
    <main class="container">
        <?php require __DIR__ . '/partials/flash.php'; ?>
        
        <?php echo $content ?? ''; ?>
    </main>
    
    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> Bilet Satın Alma Sistemi. Tüm hakları saklıdır.</p>
        </div>
    </footer>
    
    <script src="/assets/js/app.js"></script>
</body>
</html>

