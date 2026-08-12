<section class="emsp-section py-5 emsp-page-shell">
    <div class="emsp-container emsp-container-narrow">
        <header class="emsp-page-hero emsp-editorial-bg emsp-animate-in mb-5 p-4 p-md-5">
            <span class="home-section-kicker">Aide EMSP</span>
            <h1 class="h2 fw-bold font-heading mb-2">FAQ EMSP Docs</h1>
            <p class="emsp-text-lead mb-0">Questions fréquentes sur les comptes, les dépôts, la modération et la consultation.</p>
        </header>

        <div class="emsp-float-card p-3 p-md-4">
        <div class="accordion accordion-flush" id="faqAccordion">
            <?php
            $faqs = [
                ['Comment créer un compte étudiant ?', 'Utilise le bouton <strong>Inscription</strong>, puis choisis une méthode : email école ou validation par carte étudiante. Renseigne les informations demandées, puis confirme ton mot de passe avant l\'envoi du formulaire.'],
                ['Pourquoi mon compte ne se connecte pas tout de suite ?', 'Si tu as choisi la validation par carte étudiante, le compte est en attente de modération admin avant activation. Tant que le statut reste "pending", la connexion est refusée automatiquement.'],
                ['Quels fichiers sont acceptés pour un dépôt ?', 'Formats autorisés : PDF, JPG, PNG. Taille maximale : 10 Mo. Les documents sont modérés avant publication pour garantir la qualité et la conformité.'],
                ['Comment récupérer mon mot de passe ?', 'Va sur <a href="' . url('forgot-password') . '">Mot de passe oublié</a> pour lancer une réinitialisation. Suis ensuite le lien reçu par email.'],
                ['Comment contacter l\'administration ?', 'Tu peux utiliser les contacts institutionnels affichés en haut et en bas du site : <strong>contact@emsp.int</strong>.'],
                ['Pourquoi mon document n\'apparaît pas dans la bibliothèque ?', 'Après dépôt, le document passe en statut "pending". Il devient visible seulement après validation par un administrateur ou modérateur.'],
                ['Puis-je modifier un document déjà déposé ?', 'La modification directe n\'est pas prévue dans ce sprint. La bonne pratique est de déposer une nouvelle version propre avec un titre explicite.'],
                ['Comment fonctionnent les favoris ?', 'Depuis la fiche d\'un document, clique sur "Ajouter aux favoris". Tu retrouveras ensuite la liste dans <a href="' . url('favoris') . '">Mes favoris</a>.'],
                ['Les documents concours sont-ils publics ?', 'Certains documents peuvent être marqués publics (par exemple concours/annales). Les autres restent réservés aux comptes connectés.'],
                ['Qui peut accéder à l\'administration ?', 'Seuls les comptes avec rôle admin ou modérateur peuvent accéder au panneau d\'administration, à la modération des comptes et des documents.'],
            ];
            foreach ($faqs as $i => $faq): $n = $i + 1;
            ?>
                <div class="accordion-item border-bottom">
                    <h2 class="accordion-header" id="q<?= $n ?>">
                        <button class="accordion-button <?= $n > 1 ? 'collapsed' : '' ?> bg-transparent shadow-none fw-semibold" type="button" data-bs-toggle="collapse"
                                data-bs-target="#a<?= $n ?>" aria-expanded="<?= $n === 1 ? 'true' : 'false' ?>" aria-controls="a<?= $n ?>">
                            <?= h($faq[0]) ?>
                        </button>
                    </h2>
                    <div id="a<?= $n ?>" class="accordion-collapse collapse <?= $n === 1 ? 'show' : '' ?>" aria-labelledby="q<?= $n ?>" data-bs-parent="#faqAccordion">
                        <div class="accordion-body pt-0 text-secondary"><?= $faq[1] ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        </div>
    </div>
</section>

<section class="emsp-section py-5 emsp-page-shell">
    <div class="emsp-container emsp-container-narrow">
        <div class="emsp-float-card p-3 p-md-4">
        <h2 class="h6 fw-bold text-uppercase mb-4" style="letter-spacing:0.06em;color:#31513E;">Guides rapides</h2>
        <div class="emsp-editorial-list">
            <a href="<?= url('register') ?>" class="emsp-editorial-item">
                <div>
                    <h3 class="emsp-editorial-item-title">Démarrage</h3>
                    <p class="emsp-editorial-item-meta">Créer un compte, se connecter et configurer son profil étudiant.</p>
                </div>
                <span class="emsp-editorial-item-arrow"><i class="bi bi-arrow-right"></i></span>
            </a>
            <a href="<?= url('documents') ?>" class="emsp-editorial-item">
                <div>
                    <h3 class="emsp-editorial-item-title">Consulter</h3>
                    <p class="emsp-editorial-item-meta">Rechercher par type, filière, module et semestre dans la bibliothèque.</p>
                </div>
                <span class="emsp-editorial-item-arrow"><i class="bi bi-arrow-right"></i></span>
            </a>
            <a href="<?= url('upload') ?>" class="emsp-editorial-item">
                <div>
                    <h3 class="emsp-editorial-item-title">Contribuer</h3>
                    <p class="emsp-editorial-item-meta">Déposer des ressources utiles, conformes et bien nommées pour ta promotion.</p>
                </div>
                <span class="emsp-editorial-item-arrow"><i class="bi bi-arrow-right"></i></span>
            </a>
        </div>
        </div>
    </div>
</section>
