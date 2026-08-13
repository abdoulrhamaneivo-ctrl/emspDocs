<?php
/**
 * Carousel visuel pour les pages auth (register, login).
 * @var string|null $carouselModifier Classe additionnelle (ex. --compact pour mobile)
 */
$carouselModifier = $carouselModifier ?? '';
$slides = [
    [
        'src' => 'images/emsp-campus-ceremony.jpg',
        'alt' => 'Cérémonie de remise — étudiants EMSP en uniforme',
        'caption' => 'Une communauté étudiante unie autour des moments forts du campus.',
    ],
    [
        'src' => 'images/emsp-ivoire-tech-forum-2025.jpg',
        'alt' => 'Équipe EMSP au Ivoirienne Tech Forum 2025',
        'caption' => 'Étudiants et encadrants au cœur de l\'innovation numérique.',
    ],
    [
        'src' => 'images/campus-2.jpg',
        'alt' => 'Remise officielle — étudiants et personnalités EMSP',
        'caption' => 'Des parcours reconnus, portés par une équipe engagée.',
    ],
    [
        'src' => 'images/media-thumb-1.jpg',
        'alt' => 'Rencontre institutionnelle sur le campus EMSP',
        'caption' => 'Partenariats et échanges au service de votre réussite.',
    ],
    [
        'src' => 'images/media-thumb-2.jpg',
        'alt' => 'Étudiants en session — campus EMSP',
        'caption' => 'Apprendre ensemble, dans un environnement exigeant et bienveillant.',
    ],
    [
        'src' => 'images/media-thumb-3.jpg',
        'alt' => 'Encadrement et étudiants en salle — EMSP',
        'caption' => 'Un accompagnement de proximité tout au long de votre formation.',
    ],
];
$carouselClass = trim('emsp-auth-aside-carousel ' . $carouselModifier);
?>
<div class="<?= h($carouselClass) ?>" data-emsp-auth-aside-carousel aria-roledescription="carousel" aria-label="Vie du campus EMSP">
    <div class="emsp-auth-aside-carousel__viewport">
        <div class="emsp-auth-aside-carousel__track" data-emsp-auth-aside-track>
            <?php foreach ($slides as $i => $slide): ?>
                <figure
                    class="emsp-auth-aside-carousel__slide<?= $i === 0 ? ' is-active' : '' ?>"
                    data-emsp-auth-aside-slide
                    aria-hidden="<?= $i === 0 ? 'false' : 'true' ?>"
                >
                    <img
                        src="<?= h(asset($slide['src'])) ?>"
                        alt="<?= h($slide['alt']) ?>"
                        loading="<?= $i === 0 ? 'eager' : 'lazy' ?>"
                        decoding="async"
                        width="640"
                        height="480"
                    >
                    <?php if (!empty($slide['caption'])): ?>
                        <figcaption class="emsp-auth-aside-carousel__caption">
                            <?= h($slide['caption']) ?>
                        </figcaption>
                    <?php endif; ?>
                </figure>
            <?php endforeach; ?>
        </div>
        <div class="emsp-auth-aside-carousel__overlay" aria-hidden="true"></div>
    </div>
    <?php if (count($slides) > 1): ?>
        <div class="emsp-auth-aside-carousel__dots" role="tablist" aria-label="Choisir une image">
            <?php foreach ($slides as $i => $slide): ?>
                <button
                    type="button"
                    class="emsp-auth-aside-carousel__dot<?= $i === 0 ? ' is-active' : '' ?>"
                    role="tab"
                    data-emsp-auth-aside-dot
                    aria-label="Image <?= (int) ($i + 1) ?> sur <?= count($slides) ?>"
                    aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"
                ></button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
