<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($code ?? '500') ?> - Errore</title>
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
        <div class="error-icon"><?= htmlspecialchars($icon ?? '❌') ?></div>
        <div class="error-code"><?= htmlspecialchars($code ?? '500') ?></div>
        <h1><?= htmlspecialchars($title ?? 'Errore') ?></h1>
        <p class="error-message"><?= htmlspecialchars($message ?? 'Si è verificato un errore imprevisto.') ?></p>
        <a href="<?= htmlspecialchars($homeUrl ?? '/') ?>" class="btn-home">Torna alla Home</a>
        
        <?php if (isset($debug) && $debug && isset($debugInfo)): ?>
        <div class="details">
            <h3>Informazioni Debug:</h3>
            <pre><?= htmlspecialchars($debugInfo) ?></pre>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
