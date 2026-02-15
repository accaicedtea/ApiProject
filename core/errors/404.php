<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Pagina Non Trovata</title>
    <style>
        <?php 
        $cssPath = __DIR__ . '/error-styles.css';
        if (file_exists($cssPath)) {
            echo file_get_contents($cssPath);
        }
        ?>
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">🔍</div>
        <div class="error-code ">404</div>
        <h1>Pagina Non Trovata</h1>
        <p class="error-message"><?= htmlspecialchars($message ?? 'La pagina che stai cercando non esiste o è stata spostata.') ?></p>
        <a href="<?= htmlspecialchars($homeUrl ?? '/') ?>" class="btn-home ">Torna alla Home</a>
        
        <?php if (isset($debug) && $debug): ?>
        <div class="details">
            <h3>Dettagli Tecnici:</h3>
            <p><strong>URI:</strong> <?= htmlspecialchars($uri ?? 'N/A') ?></p>
            <p><strong>Timestamp:</strong> <?= date('Y-m-d H:i:s') ?></p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
