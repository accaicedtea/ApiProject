<section class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <h1 class="display-5 fw-bold text-primary">
                <i class="fas fa-cog"></i> Configurazione Database
            </h1>
            <p class="text-muted fs-5">Configura la connessione al database per avviare l'applicazione</p>
        </div>
    </div>

    <?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?= e($success) ?>
        <a href="<?= url('/') ?>" class="btn btn-success btn-sm ms-3">
            <i class="fas fa-home"></i> Vai alla Home
        </a>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i> <?= e($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Colonna Form -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-database me-2"></i>Credenziali Database</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= url('/settings/save') ?>" id="settingsForm">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                        
                        <!-- DB Host -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="db_host" class="form-label fw-bold">
                                    <i class="fas fa-network-wired me-1"></i> Host
                                </label>
                                <input type="text" class="form-control" id="db_host" name="db_host" 
                                    value="<?= e($currentConfig['db_host'] ?? 'localhost') ?>"
                                    placeholder="localhost" required>
                            </div>
                            <div class="col-md-6">
                                <label for="db_name" class="form-label fw-bold">
                                    <i class="fas fa-database me-1"></i> Nome Database
                                </label>
                                <input type="text" class="form-control" id="db_name" name="db_name" 
                                    value="<?= e($currentConfig['db_name'] ?? '') ?>"
                                    placeholder="nome_database" required>
                            </div>
                        </div>

                        <!-- DB User/Pass -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="db_user" class="form-label fw-bold">
                                    <i class="fas fa-user me-1"></i> Username
                                </label>
                                <input type="text" class="form-control" id="db_user" name="db_user" 
                                    value="<?= e($currentConfig['db_user'] ?? '') ?>"
                                    placeholder="root" required>
                            </div>
                            <div class="col-md-6">
                                <label for="db_pass" class="form-label fw-bold">
                                    <i class="fas fa-lock me-1"></i> Password
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="db_pass" name="db_pass" 
                                        value="<?= e($currentConfig['db_pass'] ?? '') ?>"
                                        placeholder="password">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                        <i class="fas fa-eye" id="toggleIcon"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pulsanti -->
                        <div class="d-flex gap-2 mt-4">
                            <button type="button" class="btn btn-info text-white" id="testBtn" onclick="testConnection()">
                                <i class="fas fa-plug me-1"></i> Testa Connessione
                            </button>
                            <button type="submit" class="btn btn-success" id="saveBtn">
                                <i class="fas fa-save me-1"></i> Salva e Applica
                            </button>
                            <a href="<?= url('/') ?>" class="btn btn-outline-secondary ms-auto">
                                <i class="fas fa-arrow-left me-1"></i> Torna alla Home
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Colonna Status -->
        <div class="col-md-4">
            <!-- Stato attuale -->
            <div class="card shadow-sm mb-3">
                <div class="card-header <?= $isConnected ? 'bg-success' : 'bg-danger' ?> text-white">
                    <h5 class="mb-0">
                        <i class="fas <?= $isConnected ? 'fa-check-circle' : 'fa-times-circle' ?> me-2"></i>
                        Stato Connessione
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($isConnected): ?>
                    <p class="text-success mb-1"><strong>Connesso</strong></p>
                    <p class="mb-0">Database: <code><?= e($databaseName) ?></code></p>
                    <?php else: ?>
                    <p class="text-danger mb-1"><strong>Non connesso</strong></p>
                    <p class="mb-0 text-muted">Configura le credenziali e testa la connessione</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Risultato Test -->
            <div class="card shadow-sm mb-3" id="testResultCard" style="display: none;">
                <div class="card-header" id="testResultHeader">
                    <h5 class="mb-0"><i class="fas fa-vial me-2"></i>Risultato Test</h5>
                </div>
                <div class="card-body" id="testResultBody">
                </div>
            </div>
            
            <!-- Help -->
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Guida Rapida</h5>
                </div>
                <div class="card-body">
                    <h6>Locale (XAMPP/MAMP)</h6>
                    <ul class="small mb-3">
                        <li>Host: <code>localhost</code></li>
                        <li>User: <code>root</code></li>
                        <li>Password: <em>(vuota)</em></li>
                    </ul>
                    
                    <h6>Altervista</h6>
                    <ul class="small mb-0">
                        <li>Host: <code>localhost</code></li>
                        <li>Database: <code>my_tuousername</code></li>
                        <li>User: <code>tuousername</code></li>
                        <li>Base URL: <code>/cartella</code></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function togglePassword() {
    const input = document.getElementById('db_pass');
    const icon = document.getElementById('toggleIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

async function testConnection() {
    const btn = document.getElementById('testBtn');
    const card = document.getElementById('testResultCard');
    const header = document.getElementById('testResultHeader');
    const body = document.getElementById('testResultBody');
    
    // Loading state
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Testando...';
    card.style.display = 'block';
    header.className = 'card-header bg-warning';
    body.innerHTML = '<p class="mb-0"><i class="fas fa-spinner fa-spin me-2"></i>Connessione in corso...</p>';
    
    const data = {
        db_host: document.getElementById('db_host').value,
        db_name: document.getElementById('db_name').value,
        db_user: document.getElementById('db_user').value,
        db_pass: document.getElementById('db_pass').value,
    };
    
    try {
        const response = await fetch('<?= url("/settings/test") ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            header.className = 'card-header bg-success text-white';
            let tablesHtml = '';
            if (result.tables && result.tables.length > 0) {
                tablesHtml = '<hr><p class="mb-1"><strong>Tabelle trovate:</strong></p>';
                tablesHtml += '<div style="max-height: 200px; overflow-y: auto;">';
                tablesHtml += '<ul class="list-group list-group-flush small">';
                result.tables.forEach(t => {
                    tablesHtml += `<li class="list-group-item py-1"><i class="fas fa-table me-1 text-muted"></i>${t}</li>`;
                });
                tablesHtml += '</ul></div>';
            }
            body.innerHTML = `<p class="text-success mb-0"><i class="fas fa-check-circle me-2"></i>${result.message}</p>${tablesHtml}`;
            
            // Abilita il pulsante salva con evidenziazione
            document.getElementById('saveBtn').classList.add('btn-lg');
        } else {
            header.className = 'card-header bg-danger text-white';
            body.innerHTML = `<p class="text-danger mb-0"><i class="fas fa-times-circle me-2"></i>${result.message}</p>`;
        }
    } catch (err) {
        header.className = 'card-header bg-danger text-white';
        body.innerHTML = `<p class="text-danger mb-0"><i class="fas fa-times-circle me-2"></i>Errore di rete: ${err.message}</p>`;
    }
    
    // Reset button
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-plug me-1"></i> Testa Connessione';
}
</script>
