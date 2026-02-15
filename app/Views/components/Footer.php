<footer class="bg-dark text-light py-3">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h5><?= $appName ?? 'My App' ?></h5>
                <p>Sistema di generazione API REST automatizzato con PHP MV.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <div class="">
                    <a href="<?= url('/generator') ?>" class="text-light me-3">Generator</a>
                    <a href="<?= url('/generator/builder') ?>" class="text-light me-3">Builder</a>
                    <a href="<?= url('/deploy') ?>" class="text-light me-3">Deploy</a>
                    <a href="<?= url('/about') ?>" class="text-light me-3">About</a>
                </div>
                <small>&copy; <?php echo date('Y'); ?> Tutti i diritti riservati.</small>
            </div>
        </div>
    </div>
</footer>

<!-- Modal Globale per messaggi -->
<div class="modal fade" id="appModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" id="appModalHeader">
                <h5 class="modal-title" id="appModalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>
            <div class="modal-body" id="appModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                <button type="button" class="btn d-none" id="appModalConfirm" data-bs-dismiss="modal">Conferma</button>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * Sistema di notifiche modal Bootstrap
 * Sostituisce tutti gli alert() nel progetto
 */
const AppModal = {
    _modal: null,
    _getModal() {
        if (!this._modal) {
            this._modal = new bootstrap.Modal(document.getElementById('appModal'));
        }
        return this._modal;
    },
    
    /**
     * Mostra un modal di errore
     */
    error(message, title = 'Errore') {
        this._show(title, message, 'danger', 'fa-exclamation-triangle');
    },
    
    /**
     * Mostra un modal di successo
     */
    success(message, title = 'Successo') {
        this._show(title, message, 'success', 'fa-check-circle');
    },
    
    /**
     * Mostra un modal di warning / info
     */
    warning(message, title = 'Attenzione') {
        this._show(title, message, 'warning', 'fa-exclamation-circle');
    },
    
    info(message, title = 'Info') {
        this._show(title, message, 'info', 'fa-info-circle');
    },

    /**
     * Mostra un modal di conferma con callback
     */
    confirm(message, onConfirm, title = 'Conferma') {
        const header = document.getElementById('appModalHeader');
        const titleEl = document.getElementById('appModalTitle');
        const body = document.getElementById('appModalBody');
        const confirmBtn = document.getElementById('appModalConfirm');
        
        header.className = 'modal-header bg-warning text-dark';
        titleEl.innerHTML = `<i class="fas fa-question-circle me-2"></i>${title}`;
        body.innerHTML = `<p class="mb-0">${message}</p>`;
        
        confirmBtn.classList.remove('d-none');
        confirmBtn.className = 'btn btn-warning';
        confirmBtn.textContent = 'Conferma';
        confirmBtn.onclick = () => { onConfirm(); };
        
        this._getModal().show();
    },
    
    _show(title, message, type, icon) {
        const header = document.getElementById('appModalHeader');
        const titleEl = document.getElementById('appModalTitle');
        const body = document.getElementById('appModalBody');
        const confirmBtn = document.getElementById('appModalConfirm');
        
        const textClass = (type === 'warning') ? 'text-dark' : 'text-white';
        header.className = `modal-header bg-${type} ${textClass}`;
        titleEl.innerHTML = `<i class="fas ${icon} me-2"></i>${title}`;
        body.innerHTML = `<p class="mb-0">${message}</p>`;
        confirmBtn.classList.add('d-none');
        
        this._getModal().show();
    }
};
</script>

</body>

</html>