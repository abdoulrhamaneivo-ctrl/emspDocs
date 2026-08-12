<style>
.reveal-init {
    opacity: 0;
    transform: translateY(28px);
    transition: opacity .6s ease, transform .6s ease;
}
.reveal-init.is-visible {
    opacity: 1;
    transform: translateY(0);
}
.institution-stat {
    border-radius: 16px;
    border: 1px solid rgba(0,48,135,.1);
    background: #fff;
    box-shadow: 0 8px 24px rgba(0,0,0,.04);
    padding: 1.1rem;
    text-align: center;
}
.institution-stat .stat-number {
    font-size: 2rem;
    font-weight: 800;
    color: var(--emsp-color-primary);
}
.institution-stat .stat-label {
    color: #6b7a90;
    font-size: .85rem;
}
.institution-image--full img {
    width: 100%; max-height: 500px; object-fit: cover;
}
.institution-image--center { text-align: center; }
.institution-image--center img { max-width: 600px; width: 100%; }
.institution-image--left img { float: left; margin: 0 1.5rem 1rem 0; max-width: 45%; }
.institution-image--right img { float: right; margin: 0 0 1rem 1.5rem; max-width: 45%; }
.institution-image figcaption {
    font-size: .85rem; color: #64748b;
    text-align: center; margin-top: .5rem; font-style: italic;
}
.institution-quote {
    border: 1px solid rgba(0,48,135,.12);
    padding: 1.1rem 1.4rem;
    background: #f8f9fc;
    border-radius: 16px;
    margin: 1.6rem 0;
}
.institution-quote p {
    font-size: 1.15rem; font-style: italic; margin-bottom: .5rem;
}
.institution-stat { background: #fff; border-radius: 16px; }
.institution-stat .stat-number { line-height: 1.1; }
.clearfix::after { content:''; display:table; clear:both; }
.card-dg { border-left: 4px solid var(--emsp-color-primary) !important; border-radius: 18px; }
.card-de { border-left: 4px solid var(--emsp-color-primary) !important; border-radius: 18px; }
.dg-photo,
.de-photo {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border: 4px solid var(--emsp-color-primary);
}
.de-photo {
    border-color: var(--emsp-color-primary);
}
.dg-avatar-placeholder,
.de-avatar-placeholder {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    font-size: 3rem;
    color: rgba(255,255,255,0.7);
}
.dg-avatar-placeholder {
    background: linear-gradient(135deg, var(--emsp-color-primary), var(--emsp-color-primary));
}
.de-avatar-placeholder {
    background: linear-gradient(135deg, var(--emsp-color-primary), #0f6a30);
}
.dg-quote-mark,
.de-quote-mark {
    font-size: 5rem;
    line-height: 0.5;
    opacity: 0.15;
    font-family: Georgia, serif;
    margin-bottom: 0.5rem;
}
.dg-quote-mark { color: var(--emsp-color-primary); }
.de-quote-mark { color: var(--emsp-color-primary); }
.dg-text,
.de-text {
    font-style: italic;
    line-height: 1.8;
    color: #2d3748;
}
.text-emsp { color: var(--emsp-color-primary); }
.text-emsp-green { color: var(--emsp-color-primary); }

.institution-panel-grid {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}
.institution-panel {
    border: 1px solid rgba(0,48,135,.1);
    border-radius: 18px;
    background: #fff;
    box-shadow: 0 10px 26px rgba(0,0,0,.04);
    padding: 1.1rem 1.2rem;
    min-height: 100%;
}
.institution-panel h3 {
    font-size: 1.05rem;
    font-weight: 700;
    margin-bottom: .6rem;
}
.institution-panel p {
    color: #526178;
    line-height: 1.6;
}
.institution-panel--wide { grid-column: span 7; }
.institution-panel--mid { grid-column: span 5; }
.institution-panel--half { grid-column: span 6; }
.institution-panel--full { grid-column: span 12; }
.institution-panel--accent {
    background: linear-gradient(160deg, rgba(0,48,135,.08), rgba(0,85,204,.04));
}
@media (max-width: 991px) {
    .institution-panel--wide,
    .institution-panel--mid,
    .institution-panel--half,
    .institution-panel--full {
        grid-column: span 12;
    }
}
</style>

<section class="page-header emsp-editorial-bg emsp-institutional-hero emsp-animate-in mb-4">
    <div class="container py-2">
        <span class="home-section-kicker emsp-kicker">Institution EMSP · emsp.int</span>
        <h1>L'Institution</h1>
        <p class="mb-0 emsp-prose">Présentation dynamique de l'École Multinationale Supérieure des Postes — mission, campus et partenaires.</p>
        <?php
        $institutionRole = strtolower(trim((string) ($_SESSION['auth_role'] ?? ($_SESSION['auth_user']['role'] ?? ''))));
        $canEditInstitution = in_array($institutionRole, ['admin', 'moderateur'], true);
        ?>
        <?php if ($canEditInstitution): ?>
        <a class="btn btn-emsp btn-sm mt-3" href="<?= url('admin/edit-institution.php') ?>">
            <i class="bi bi-pencil-square me-1"></i>Modifier le contenu institutionnel
        </a>
        <?php endif; ?>
    </div>
</section>

<section class="section-pad emsp-page-shell">
    <div class="container">
        <?php if (!$use_legacy): ?>
            <?php foreach ($blocs as $bloc): ?>
                <?php
                $cfg = [];
                if (!empty($bloc['config_json'])) {
                    $cfg = json_decode(emsp_institution_fix_text((string)$bloc['config_json']), true);
                    if (!is_array($cfg)) { $cfg = []; }
                }
                $type = (string)($bloc['bloc_type'] ?? 'texte');
                ?>

                <?php if ($type === 'texte'): ?>
                    <div class="reveal-init mb-5">
                        <?= emsp_sanitize_html((string)($bloc['contenu'] ?? '')) ?>
                    </div>

                <?php elseif ($type === 'image'): ?>
                    <?php
                    $align = $cfg['align'] ?? 'full';
                    $align = in_array($align, ['full','center','left','right'], true) ? $align : 'full';
                    $legende = emsp_institution_fix_text((string)($cfg['legende'] ?? ''));
                    $img = emsp_institution_media_src((string)($bloc['image_path'] ?? ''));
                    ?>
                    <?php if ($img !== ''): ?>
                    <div class="reveal-init mb-4 clearfix">
                        <figure class="institution-image institution-image--<?= htmlspecialchars($align) ?>">
                            <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($legende) ?>" class="img-fluid">
                            <?php if ($legende !== ''): ?>
                            <figcaption><?= htmlspecialchars($legende) ?></figcaption>
                            <?php endif; ?>
                        </figure>
                    </div>
                    <?php endif; ?>

                <?php elseif ($type === 'galerie'): ?>
                    <?php
                    $cols = intval($cfg['cols'] ?? 3);
                    if (!in_array($cols, [2,3,4], true)) { $cols = 3; }
                    $colClass = 'col-' . (12 / $cols);
                    $images = is_array($cfg['images'] ?? null) ? $cfg['images'] : [];
                    ?>
                    <?php if (!empty($images)): ?>
                    <div class="reveal-init row g-2 mb-4">
                        <?php foreach ($images as $img): ?>
                        <?php $img = emsp_institution_media_src((string) $img); ?>
                        <?php if ($img === '') { continue; } ?>
                        <div class="<?= $colClass ?>">
                            <img src="<?= htmlspecialchars((string)$img) ?>" class="img-fluid rounded w-100 emsp-institution-media" alt="">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                <?php elseif ($type === 'stats'): ?>
                    <?php $items = is_array($cfg['items'] ?? null) ? $cfg['items'] : []; ?>
                    <?php if (!empty($items)): ?>
                    <div class="row g-3 mb-5 reveal-init">
                        <?php foreach ($items as $item): ?>
                        <?php
                        $numRaw = (string)($item['number'] ?? '');
                        $numVal = (int)preg_replace('/[^0-9]/', '', $numRaw);
                        ?>
                        <div class="col-md-4">
                            <div class="institution-stat text-center p-3">
                                <div class="stat-number display-4 fw-bold text-primary" data-counter="<?= $numVal ?>">0</div>
                                <div class="stat-label text-muted"><?= htmlspecialchars((string)($item['label'] ?? '')) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                <?php elseif ($type === 'citation'): ?>
                    <?php $texte = emsp_institution_fix_text((string)($cfg['texte'] ?? '')); ?>
                    <?php $auteur = emsp_institution_fix_text((string)($cfg['auteur'] ?? '')); ?>
                    <?php if ($texte !== ''): ?>
                    <blockquote class="reveal-init institution-quote mb-4">
                        <p><?= htmlspecialchars($texte) ?></p>
                        <?php if ($auteur !== ''): ?>
                        <footer class="blockquote-footer"><?= htmlspecialchars($auteur) ?></footer>
                        <?php endif; ?>
                    </blockquote>
                    <?php endif; ?>

                <?php elseif ($type === 'colonnes'): ?>
                    <?php
                    $nbCols = intval($cfg['cols'] ?? 2);
                    if ($nbCols !== 3) { $nbCols = 2; }
                    $colClass2 = $nbCols === 3 ? 'col-md-4' : 'col-md-6';
                    $contenuCols = json_decode(emsp_institution_fix_text((string)($bloc['contenu'] ?? '')), true);
                    if (!is_array($contenuCols)) { $contenuCols = []; }
                    ?>
                    <div class="reveal-init row g-4 mb-4">
                        <?php for ($i=0; $i<$nbCols; $i++): ?>
                        <div class="<?= $colClass2 ?>">
                            <?= emsp_sanitize_html((string)($contenuCols[$i] ?? '')) ?>
                        </div>
                        <?php endfor; ?>
                    </div>

                <?php elseif ($type === 'separateur'): ?>
                    <?php
                    $sepStyle = in_array($cfg['style'] ?? '', ['solid','dashed','dotted'], true) ? $cfg['style'] : 'solid';
                    $sepColor = preg_replace('/[^#a-fA-F0-9]/', '', (string)($cfg['color'] ?? '#e2e8f0'));
                    if ($sepColor === '') { $sepColor = '#e2e8f0'; }
                    ?>
                    <hr class="reveal-init my-5 emsp-dynamic-separator"
                        data-emsp-border-style="<?= htmlspecialchars($sepStyle, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                        data-emsp-border-color="<?= htmlspecialchars($sepColor, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                        data-emsp-border-width="2px">
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <?php if ($aboutText !== ''): ?>
            <div class="reveal-init mb-5" id="a-propos">
                <h2 class="section-title text-uppercase">A propos</h2>
                <div class="mb-0"><?= emsp_sanitize_html($aboutText) ?></div>
            </div>
            <?php endif; ?>

            <div class="row g-3 mb-5 reveal-init">
                <div class="col-md-4">
                    <div class="institution-stat">
                        <div class="stat-number" data-counter="<?= max($activeStudents, 0) ?>">0</div>
                        <div class="stat-label">Etudiants actifs</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="institution-stat">
                        <div class="stat-number" data-counter="<?= max($approvedDocs, 0) ?>">0</div>
                        <div class="stat-label">Documents approuves</div>
                    </div>
                </div>
                <?php if ($countries !== null): ?>
                <div class="col-md-4">
                    <div class="institution-stat">
                        <div class="stat-number" data-counter="<?= max($countries, 0) ?>">0</div>
                        <div class="stat-label">Pays membres</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="institution-panel-grid reveal-init">
                <?php if ($missionText !== ''): ?>
                <div class="institution-panel institution-panel--wide" id="mission">
                    <h3>Mission</h3>
                    <div class="mb-0"><?= emsp_sanitize_html($missionText) ?></div>
                </div>
                <?php endif; ?>
                <?php if ($visionText !== ''): ?>
                <div class="institution-panel institution-panel--mid institution-panel--accent" id="vision">
                    <h3>Vision</h3>
                    <div class="mb-0"><?= emsp_sanitize_html($visionText) ?></div>
                </div>
                <?php endif; ?>
                <?php if ($valuesText !== ''): ?>
                <div class="institution-panel institution-panel--half" id="valeurs">
                    <h3>Valeurs</h3>
                    <div class="mb-0"><?= emsp_sanitize_html($valuesText) ?></div>
                </div>
                <?php endif; ?>
                <?php if ($perspectivesText !== ''): ?>
                <div class="institution-panel institution-panel--half" id="perspectives">
                    <h3>Perspectives</h3>
                    <div class="mb-0"><?= emsp_sanitize_html($perspectivesText) ?></div>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($motDGText !== ''): ?>
            <div class="card shadow-sm border-0 mb-4 reveal-init card-dg" id="mot-dg">
                <div class="card-body p-4">
                    <div class="row align-items-center g-4">
                        <div class="col-md-3 text-center">
                            <?php if (!empty($motDGPhoto)): ?>
                                <img src="<?= htmlspecialchars(emsp_media_src($motDGPhoto)) ?>"
                                     alt="<?= htmlspecialchars($motDGName !== '' ? $motDGName : 'Directeur Général') ?>"
                                     class="dg-photo rounded-circle shadow">
                            <?php else: ?>
                                <div class="dg-avatar-placeholder">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                            <?php endif; ?>
                            <div class="mt-2 fw-bold text-emsp"><?= htmlspecialchars($motDGName !== '' ? $motDGName : 'Direction Générale') ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($motDGTitre) ?></div>
                        </div>
                        <div class="col-md-9">
                            <div class="dg-quote-mark">"</div>
                            <div class="dg-text"><?= emsp_sanitize_html($motDGText) ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($motDEText !== ''): ?>
            <div class="card shadow-sm border-0 mb-4 reveal-init card-de" id="mot-de">
                <div class="card-body p-4">
                    <div class="row align-items-center g-4">
                        <div class="col-md-3 text-center">
                            <?php if (!empty($motDEPhoto)): ?>
                                <img src="<?= htmlspecialchars(emsp_media_src($motDEPhoto)) ?>"
                                     alt="<?= htmlspecialchars($motDEName !== '' ? $motDEName : 'Directeur des Études') ?>"
                                     class="de-photo rounded-circle shadow">
                            <?php else: ?>
                                <div class="de-avatar-placeholder">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                            <?php endif; ?>
                            <div class="mt-2 fw-bold text-emsp-green"><?= htmlspecialchars($motDEName !== '' ? $motDEName : 'Direction des Études') ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($motDETitre) ?></div>
                        </div>
                        <div class="col-md-9">
                            <div class="de-quote-mark">"</div>
                            <div class="de-text"><?= emsp_sanitize_html($motDEText) ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($perspectivesText !== '' && $valuesText === '' && $missionText === '' && $visionText === ''): ?>
            <div class="institution-panel institution-panel--full reveal-init" id="perspectives">
                <h3>Perspectives</h3>
                <div class="mb-0"><?= emsp_sanitize_html($perspectivesText) ?></div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<script>
const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
        if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
        }
    });
}, { threshold: 0.15 });

document.querySelectorAll('.reveal-init').forEach((el) => observer.observe(el));

function animateCounter(el) {
    const target = parseInt(el.dataset.counter || '0', 10);
    const duration = 1200;
    const start = performance.now();
    function tick(now) {
        const progress = Math.min((now - start) / duration, 1);
        const value = Math.floor(target * progress);
        el.textContent = value.toLocaleString('fr-FR');
        if (progress < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
}

const counterObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
        if (entry.isIntersecting && !entry.target.dataset.animated) {
            entry.target.dataset.animated = '1';
            animateCounter(entry.target);
        }
    });
}, { threshold: 0.4 });

document.querySelectorAll('[data-counter]').forEach((el) => counterObserver.observe(el));
</script>
