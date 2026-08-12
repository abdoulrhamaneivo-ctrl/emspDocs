<div class="mb-4">
    <h1 class="h4 fw-bold mb-1"><i class="bi bi-bar-chart-line-fill text-primary me-2"></i>Statistiques EMSP</h1>
    <p class="text-muted mb-0">Grands indicateurs de la plateforme, dynamique des documents, activité des étudiants.</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center shadow-sm"><div class="card-body py-3">
            <div class="fs-4 fw-bold"><?= number_format($s['users_active'], 0, ',', ' ') ?></div>
            <div class="small text-muted">Étudiants actifs (<?= $activityRate ?>%)</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center shadow-sm"><div class="card-body py-3">
            <div class="fs-4 fw-bold text-success"><?= $approvalRate ?>%</div>
            <div class="small text-muted"><?= number_format($s['docs_approved'], 0, ',', ' ') ?> documents approuvés</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center shadow-sm"><div class="card-body py-3">
            <div class="fs-4 fw-bold text-info"><?= number_format($s['total_favorites'], 0, ',', ' ') ?></div>
            <div class="small text-muted">Favoris enregistrés</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center shadow-sm"><div class="card-body py-3">
            <div class="fs-4 fw-bold text-warning"><?= number_format($actionsRequired, 0, ',', ' ') ?></div>
            <div class="small text-muted">Actions à traiter</div>
        </div></div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="card text-center shadow-sm"><div class="card-body py-3"><div class="fw-bold"><?= number_format($s['users_total'], 0, ',', ' ') ?></div><div class="small text-muted">Comptes étudiants</div></div></div></div>
    <div class="col-6 col-md-3"><div class="card text-center shadow-sm"><div class="card-body py-3"><div class="fw-bold"><?= number_format($s['docs_total'], 0, ',', ' ') ?></div><div class="small text-muted">Documents (total)</div></div></div></div>
    <div class="col-6 col-md-3"><div class="card text-center shadow-sm"><div class="card-body py-3"><div class="fw-bold"><?= number_format($s['total_downloads'], 0, ',', ' ') ?></div><div class="small text-muted">Téléchargements</div></div></div></div>
    <div class="col-6 col-md-3"><div class="card text-center shadow-sm"><div class="card-body py-3"><div class="fw-bold"><?= number_format($s['total_comments'], 0, ',', ' ') ?></div><div class="small text-muted">Commentaires visibles</div></div></div></div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100"><div class="card-body">
            <h6 class="fw-bold mb-3">Documents par type</h6>
            <div class="chart-responsive">
                <canvas id="chartTypes" height="220"></canvas>
            </div>
        </div></div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm h-100"><div class="card-body">
            <h6 class="fw-bold mb-3">Dépôts approuvés (6 derniers mois)</h6>
            <div class="chart-responsive">
                <canvas id="chartMonthly" height="220"></canvas>
            </div>
        </div></div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Top 5 contributeurs</div>
            <ul class="list-group list-group-flush">
                <?php if (empty($topUploaders)): ?>
                    <li class="list-group-item text-muted small">Aucune donnée.</li>
                <?php else: foreach ($topUploaders as $u): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= h(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''))) ?></span>
                        <span class="badge bg-primary rounded-pill"><?= (int) $u['upload_count'] ?> docs</span>
                    </li>
                <?php endforeach; endif; ?>
            </ul>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Top 5 documents téléchargés</div>
            <ul class="list-group list-group-flush">
                <?php if (empty($topDocs)): ?>
                    <li class="list-group-item text-muted small">Aucune donnée.</li>
                <?php else: foreach ($topDocs as $d): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="text-truncate" style="max-width:70%;"><?= h((string) $d['title']) ?></span>
                        <span class="badge bg-success rounded-pill"><?= (int) $d['download_count'] ?></span>
                    </li>
                <?php endforeach; endif; ?>
            </ul>
        </div>
    </div>
</div>

<script src="<?= asset('js/chart.min.js') ?>"></script>
<script>
new Chart(document.getElementById('chartTypes'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_keys($typesData), JSON_UNESCAPED_UNICODE) ?>,
        datasets: [{ data: <?= json_encode(array_values($typesData)) ?>, backgroundColor: ['#4e73df','#1cc88a','#36b9cc','#f6c23e','#e74a3b'] }]
    }
});
new Chart(document.getElementById('chartMonthly'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($uploadsMonthly, 'mois')) ?>,
        datasets: [{ label: 'Documents approuvés', data: <?= json_encode(array_column($uploadsMonthly, 'cnt')) ?>, backgroundColor: '#4e73df' }]
    },
    options: { scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});
</script>
