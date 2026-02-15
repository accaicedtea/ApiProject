<section>
    <div class="container">
        <h1> <?= e($title) ?></h1>
        <p class="subtitle">Carica automaticamente le API generate sul tuo server via FTP</p>

        <?php if (isset($_GET['success'])): ?>
            <div class="success-box">
                <h3> Upload completato con successo!</h3>
                <p><?= isset($_GET['files']) ? e($_GET['files']) . ' file caricati' : 'API caricate sul server' ?></p>
            </div>
            <script>
                // Mostra il loading al 100% quando arriva da upload completato
                document.addEventListener('DOMContentLoaded', function() {
                    const loadingDiv = document.getElementById('loading');
                    const loadingText = loadingDiv.querySelector('p');
                    if (loadingDiv && loadingText) {
                        loadingDiv.classList.add('show');
                        loadingText.innerHTML = '✓ Upload completato!<br><small>Progresso: 100%</small>';
                        loadingText.style.color = '#28a745';
                        
                        // Nascondi dopo 3 secondi
                        setTimeout(() => {
                            loadingDiv.classList.remove('show');
                        }, 3000);
                        
                        // Rimuovi il parametro success dall'URL dopo 20 secondi
                        setTimeout(() => {
                            const url = new URL(window.location);
                            url.searchParams.delete('success');
                            window.history.replaceState({}, document.title, url);
                        }, 200);
                    }
                });
            </script>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="error-box">
                <h3> Errore durante l'upload</h3>
                <p><?= e($_GET['error']) ?></p>
            </div>
        <?php endif; ?>

        <?php if (!$apiExists): ?>
            <div class="warning-box">
                <h3> Cartella API non trovata</h3>
                <p>Prima di caricare sul server, devi <a href="<?= url('/builder') ?>">generare le API</a>.</p>
            </div>
        <?php else: ?>
            <div class="info-box">
                <h3>📦 Cartella da caricare</h3>
                <p><code><?= e($apiPath) ?></code></p>
                <p style="margin-top: 10px;">Tutti i file in questa cartella verranno caricati sul server FTP.</p>
            </div>

            <?php if (!empty($apiStats)): ?>
            <div style="margin: 25px 0; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 1px solid #dee2e6;">
                <h3 style="margin: 0 0 15px 0; color: #495057;">🔍 Analisi generated-api</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 15px;">
                    <div style="background: #fff; padding: 12px; border-radius: 6px; border-left: 3px solid #667eea; text-align: center;">
                        <div style="font-size: 22px; font-weight: bold; color: #667eea;"><?= $apiStats['totalFiles'] ?></div>
                        <small style="color: #666;">File totali</small>
                    </div>
                    <div style="background: #fff; padding: 12px; border-radius: 6px; border-left: 3px solid #28a745; text-align: center;">
                        <div style="font-size: 22px; font-weight: bold; color: #28a745;"><?= count($apiStats['endpoints']) ?></div>
                        <small style="color: #666;">Endpoint</small>
                    </div>
                    <div style="background: #fff; padding: 12px; border-radius: 6px; border-left: 3px solid #17a2b8; text-align: center;">
                        <div style="font-size: 22px; font-weight: bold; color: #17a2b8;"><?= count($apiStats['models']) ?></div>
                        <small style="color: #666;">Models</small>
                    </div>
                    <div style="background: #fff; padding: 12px; border-radius: 6px; border-left: 3px solid #ffc107; text-align: center;">
                        <div style="font-size: 22px; font-weight: bold; color: #d4a009;">
                            <?php
                                $size = $apiStats['totalSize'];
                                echo $size > 1048576 ? round($size / 1048576, 1) . ' MB' : round($size / 1024, 1) . ' KB';
                            ?>
                        </div>
                        <small style="color: #666;">Dimensione</small>
                    </div>
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px;">
                    <?php foreach ($apiStats['folders'] as $folder): ?>
                        <span style="background: #e9ecef; padding: 3px 10px; border-radius: 12px; font-size: 13px; color: #495057;">📁 <?= e($folder) ?></span>
                    <?php endforeach; ?>
                    <?php if ($apiStats['hasAuth']): ?>
                        <span style="background: #d4edda; padding: 3px 10px; border-radius: 12px; font-size: 13px; color: #155724;">🔐 Auth JWT</span>
                    <?php endif; ?>
                    <?php if ($apiStats['hasCors']): ?>
                        <span style="background: #cce5ff; padding: 3px 10px; border-radius: 12px; font-size: 13px; color: #004085;">🌐 CORS</span>
                    <?php endif; ?>
                    <?php if ($apiStats['hasMiddleware']): ?>
                        <span style="background: #fff3cd; padding: 3px 10px; border-radius: 12px; font-size: 13px; color: #856404;">🛡️ Security</span>
                    <?php endif; ?>
                </div>

                <?php if ($apiStats['lastModified'] > 0): ?>
                    <small style="color: #888;">Ultima modifica: <?= date('d/m/Y H:i', $apiStats['lastModified']) ?></small>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div style="margin: 30px 0; padding: 20px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #667eea;">
                <h3 style="margin: 0 0 15px 0; color: #667eea;">🚀 Modalità di Deploy</h3>
                <div class="checkbox-group" style="margin-bottom: 10px;">
                    <input type="radio" name="deploy_mode" id="mode_ftp" value="ftp" checked onchange="toggleDeployMode()">
                    <label for="mode_ftp" style="font-weight: bold;">Deploy via FTP</label>
                    <p style="margin: 5px 0 0 25px; color: #666; font-size: 14px;">Carica le API sul server tramite FTP</p>
                </div>
                <div class="checkbox-group">
                    <input type="radio" name="deploy_mode" id="mode_local" value="local" onchange="toggleDeployMode()">
                    <label for="mode_local" style="font-weight: bold;">API già online (Sposta/Rinomina)</label>
                    <p style="margin: 5px 0 0 25px; color: #666; font-size: 14px;">Le API sono già sul server, devi solo spostare o rinominare la cartella</p>
                </div>
            </div>

            <div id="ftpSection">
            <form method="POST" action="<?= url('/deploy/upload') ?>" id="ftpForm">
                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                <h2 style="margin-top: 30px; color: #667eea;">Configurazione FTP</h2>

                <div class="form-group">
                    <label>Host FTP *</label>
                    <input type="text" name="ftp_host" id="ftp_host" placeholder="ftp.tuosito.com"
                        value="<?= e($savedCredentials['ftp_host'] ?? '') ?>" required>
                    <small>Indirizzo del server FTP (es: ftp.tuosito.com o 192.168.1.100)</small>
                </div>

                <div class="form-group">
                    <label>Porta</label>
                    <input type="number" name="ftp_port" id="ftp_port"
                        value="<?= e($savedCredentials['ftp_port'] ?? 21) ?>">
                    <small>Porta FTP (di solito 21 per FTP normale, 22 per SFTP)</small>
                </div>

                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="ftp_user" id="ftp_user" value="<?= e($savedCredentials['ftp_user']) ?>"
                        required>
                </div>

                <div class="form-group">
                    <label>Password *</label>
                    <!--TODO: poi togli il fatto che è automatico -->
                    <input type="password" name="ftp_pass" id="ftp_pass" value="<?= e($savedCredentials['ftp_pass']) ?>"
                        required>
                    <small style="color: #856404;"> (MO LO FA IN AUTOMATICO) Per sicurezza, la password non viene salvata.
                        Reinseriscila ad ogni
                        deploy.</small>
                </div>

                <div class="form-group">
                    <label>Percorso Remoto</label>
                    <input type="text" name="ftp_path" id="ftp_path"
                        value="<?= e($savedCredentials['ftp_path'] ?? '/public_html/api') ?>"
                        placeholder="/public_html/api">
                    <small>Cartella dove caricare le API sul server (verrà creata se non esiste)</small>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" name="ftp_ssl" id="ftp_ssl" <?= ($savedCredentials['ftp_ssl'] ?? false) ? 'checked' : '' ?>>
                    <label for="ftp_ssl">Usa connessione SSL/TLS (FTPS)</label>
                </div>

                <div style="margin-top: 30px;">
                    <button type="button" class="btn btn-secondary "  onclick="testConnection()"> Testa Connessione (disabilitato online)</button>
                    <button type="submit" class="btn btn-success " id="uploadBtn"> Carica sul Server (disabilitato online)</button>
                </div>

                <div id="testResult" class="test-result"></div>

                <div id="loading" class="loading">
                    <div class="spinner"></div>
                    <p style="margin-top: 15px;"></p>
                </div>
            </form>
            </div>

            <div id="localSection" style="display: none;">
                <div class="info-box" style="background: #e7f3ff; border-left-color: #2196F3;">
                    <h3>📁 Sposta/Rinomina Cartella API</h3>
                    <p style="margin-top: 15px;">Le API sono già generate. Puoi spostarle o rinominarle direttamente dal server PHP.</p>
                    <p><strong>Cartella attuale:</strong> <code><?= e($apiPath) ?></code></p>
                </div>

                <form method="POST" action="<?= url('/deploy/move') ?>" id="moveForm">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                    <input type="hidden" name="source_path" value="<?= e($apiPath) ?>">

                    <h2 style="margin-top: 30px; color: #667eea;">🔄 Nuova Posizione</h2>

                    <div class="form-group">
                        <label>Nuovo Percorso/Nome *</label>
                        <input type="text" name="destination_path" id="destination_path" 
                            placeholder="/percorso/completo/api" required>
                        <small>Inserisci il percorso completo dove vuoi spostare/rinominare la cartella API</small>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" name="copy_mode" id="copy_mode">
                        <label for="copy_mode">Copia invece di spostare (mantieni l'originale)</label>
                    </div>

                    <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
                        <strong>⚠️ Attenzione:</strong> Assicurati che il percorso di destinazione sia accessibile e che PHP abbia i permessi necessari.
                    </div>

                    <div style="margin-top: 30px;">
                        <button type="submit" class="btn btn-success">✓ Sposta/Rinomina Cartella</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <div style="margin-top: 40px; text-align: center;">
            <a href="<?= url('/builder') ?>" class="back-link"> Genera API</a>
            <a href="<?= url('/generator') ?>" class="back-link"> Configurazione</a>
        </div>
    </div>
</section>

<script>
    function toggleDeployMode() {
        const ftpMode = document.getElementById('mode_ftp').checked;
        const ftpSection = document.getElementById('ftpSection');
        const localSection = document.getElementById('localSection');
        
        if (ftpMode) {
            ftpSection.style.display = 'block';
            localSection.style.display = 'none';
        } else {
            ftpSection.style.display = 'none';
            localSection.style.display = 'block';
        }
    }

    async function testConnection() {
        const host = document.getElementById('ftp_host').value;
        const user = document.getElementById('ftp_user').value;
        const pass = document.getElementById('ftp_pass').value;
        const port = document.getElementById('ftp_port').value;
        const ssl = document.getElementById('ftp_ssl').checked;

        if (!host || !user || !pass) {
            AppModal.warning('Compila host, username e password.', 'Campi Mancanti');
            return;
        }

        const resultDiv = document.getElementById('testResult');
        resultDiv.textContent = 'Test in corso...';
        resultDiv.className = 'test-result';
        resultDiv.style.display = 'block';

        const formData = new FormData();
        formData.append('csrf_token', '<?= e($csrf_token) ?>');
        formData.append('ftp_host', host);
        formData.append('ftp_user', user);
        formData.append('ftp_pass', pass);
        formData.append('ftp_port', port);
        if (ssl) formData.append('ftp_ssl', '1');

        try {
            const response = await fetch('<?= url("/deploy/test") ?>', {
                method: 'POST',
                body: formData
            });

            const text = await response.text();
            let result;
            
            try {
                result = JSON.parse(text);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                console.error('Raw response:', text);
                throw new Error('Risposta del server non valida');
            }

            if (result.success) {
                resultDiv.textContent = '✓ ' + result.message;
                resultDiv.className = 'test-result success';
            } else {
                resultDiv.textContent = '✗ ' + result.error;
                resultDiv.className = 'test-result error';
            }
        } catch (error) {
            resultDiv.textContent = '✗ Errore di connessione: ' + error.message;
            resultDiv.className = 'test-result error';
        }
    }

    document.getElementById('ftpForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        
        const loadingDiv = document.getElementById('loading');
        const loadingText = loadingDiv.querySelector('p');
        loadingDiv.classList.add('show');
        
        const formData = new FormData(this);
        const eventSource = new EventSource('<?= url("/deploy/upload") ?>?' + new URLSearchParams(formData).toString());
        
        // Fallback con fetch per POST
        fetch('<?= url("/deploy/upload") ?>', {
            method: 'POST',
            body: formData
        }).then(response => {
            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            
            function readStream() {
                reader.read().then(({ done, value }) => {
                    if (done) {
                        setTimeout(() => {
                            window.location.href = '<?= url("/deploy?success=Upload completato") ?>';
                        }, 2000);
                        return;
                    }
                    
                    const text = decoder.decode(value);
                    const lines = text.split('\n');
                    
                    lines.forEach(line => {
                        if (line.startsWith('data: ')) {
                            try {
                                const data = JSON.parse(line.substring(6));
                                loadingText.innerHTML = data.message + '<br><small>Progresso: ' + Math.round(data.percentage) + '%</small>';
                                
                                if (data.error) {
                                    loadingText.style.color = '#dc3545';
                                } else if (data.percentage === 100) {
                                    loadingText.style.color = '#28a745';
                                }
                            } catch (e) {
                                console.error('Parse error:', e);
                            }
                        }
                    });
                    
                    readStream();
                });
            }
            
            readStream();
        }).catch(error => {
            loadingText.textContent = 'Errore: ' + error.message;
            loadingText.style.color = '#dc3545';
        });
    });
</script>