<section class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <h1 class="display-5 fw-bold text-primary">
                <i class="fas fa-book"></i> Documentazione
            </h1>
            <p class="text-muted">Guida completa all'utilizzo di SerioApi</p>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <zero-md>
                <script type="text/markdown"><?= $readmeContent ?></script>
            </zero-md>
        </div>
    </div>
</section>

<!-- Lightweight client-side loader that feature-detects and load polyfills only when necessary -->
<script src="https://cdn.jsdelivr.net/npm/@webcomponents/webcomponentsjs@2/webcomponents-loader.min.js"></script>

<!-- Load the element definition -->
<script type="module" src="https://cdn.jsdelivr.net/gh/zerodevx/zero-md@1/src/zero-md.min.js"></script>

