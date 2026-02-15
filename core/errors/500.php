<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Errore Interno</title>
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
        <div class="error-icon">⚠️</div>
        <div class="error-code">500</div>
        <h1>Errore Interno del Server</h1>
        <p class="error-message"><?= htmlspecialchars($message ?? 'Si è verificato un errore interno. Il nostro team è stato avvisato.') ?></p>
        <a href="<?= htmlspecialchars($homeUrl ?? '/') ?>" class="btn-home">Torna alla Home</a>
        
        <?php if (isset($debug) && $debug): ?>
        <div class="details">
            <h3>Dettagli Debug:</h3>
            <?php if (isset($file)): ?>
            <p><strong>File:</strong> <?= htmlspecialchars($file) ?></p>
            <?php endif; ?>
            <?php if (isset($line)): ?>
            <p><strong>Linea:</strong> <?= htmlspecialchars($line) ?></p>
            <?php endif; ?>
            <?php if (isset($trace)): ?>
            <p><strong>Stack Trace:</strong></p>
            <pre><?= htmlspecialchars($trace) ?></pre>
            <?php endif; ?>
            <p><strong>Timestamp:</strong> <?= date('Y-m-d H:i:s') ?></p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
