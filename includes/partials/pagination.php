<?php
/**
 * Partial pagination admin EMSP.
 *
 * @var array<string, mixed> $pagination  Retour de emsp_paginate()
 * @var string               $baseRoute   Route sans query (ex. admin/utilisateurs)
 * @var array<string, mixed> $queryParams Paramètres GET à conserver (hors page)
 * @var string               $pageParam   Nom du paramètre page (défaut : page)
 */
$pagination = is_array($pagination ?? null) ? $pagination : [];
$pageParam = (string) ($pageParam ?? 'page');
$baseRoute = (string) ($baseRoute ?? '');
$queryParams = is_array($queryParams ?? null) ? $queryParams : [];

$total = (int) ($pagination['total'] ?? 0);
$pageNum = (int) ($pagination['page'] ?? 1);
$totalPages = (int) ($pagination['totalPages'] ?? 1);
$from = (int) ($pagination['from'] ?? 0);
$to = (int) ($pagination['to'] ?? 0);

if ($total <= 0) {
    return;
}

unset($queryParams[$pageParam]);

$buildPageUrl = static function (int $page) use ($baseRoute, $queryParams, $pageParam): string {
    $params = $queryParams;
    if ($page > 1) {
        $params[$pageParam] = $page;
    }
    $qs = $params !== [] ? '?' . http_build_query($params) : '';
    return url($baseRoute . $qs);
};

$windowStart = max(1, $pageNum - 2);
$windowEnd = min($totalPages, $pageNum + 2);
$pageNumbers = [1];
for ($i = $windowStart; $i <= $windowEnd; $i++) {
    $pageNumbers[] = $i;
}
$pageNumbers[] = $totalPages;
$pageNumbers = array_values(array_unique($pageNumbers));
sort($pageNumbers);
?>
<div class="emsp-pagination-bar d-flex flex-column flex-sm-row align-items-sm-center justify-content-sm-between gap-2 mt-3">
    <p class="emsp-pagination-summary text-body-secondary small mb-0">
        Affichage <strong><?= $from ?></strong>–<strong><?= $to ?></strong> sur <strong><?= $total ?></strong>
    </p>
    <?php if ($totalPages > 1): ?>
        <nav aria-label="Pagination">
            <ul class="pagination justify-content-center justify-content-sm-end mb-0">
                <li class="page-item <?= $pageNum <= 1 ? 'disabled' : '' ?>">
                    <?php if ($pageNum <= 1): ?>
                        <span class="page-link" aria-disabled="true"><i class="bi bi-chevron-left"></i><span class="d-none d-sm-inline ms-1">Préc.</span></span>
                    <?php else: ?>
                        <a class="page-link" href="<?= h($buildPageUrl($pageNum - 1)) ?>" aria-label="Page précédente"><i class="bi bi-chevron-left"></i><span class="d-none d-sm-inline ms-1">Préc.</span></a>
                    <?php endif; ?>
                </li>
                <?php
                $lastRendered = null;
                foreach ($pageNumbers as $pageNumber):
                    if ($lastRendered !== null && $pageNumber > $lastRendered + 1): ?>
                        <li class="page-item disabled d-none d-md-block"><span class="page-link">…</span></li>
                    <?php endif; ?>
                    <li class="page-item <?= $pageNumber === $pageNum ? 'active' : '' ?> <?= ($pageNumber !== $pageNum && $pageNumber !== 1 && $pageNumber !== $totalPages && ($pageNumber < $windowStart || $pageNumber > $windowEnd)) ? 'd-none d-md-block' : '' ?>">
                        <?php if ($pageNumber === $pageNum): ?>
                            <span class="page-link" aria-current="page"><?= $pageNumber ?></span>
                        <?php else: ?>
                            <a class="page-link" href="<?= h($buildPageUrl($pageNumber)) ?>"><?= $pageNumber ?></a>
                        <?php endif; ?>
                    </li>
                    <?php $lastRendered = $pageNumber; ?>
                <?php endforeach; ?>
                <li class="page-item <?= $pageNum >= $totalPages ? 'disabled' : '' ?>">
                    <?php if ($pageNum >= $totalPages): ?>
                        <span class="page-link" aria-disabled="true"><span class="d-none d-sm-inline me-1">Suiv.</span><i class="bi bi-chevron-right"></i></span>
                    <?php else: ?>
                        <a class="page-link" href="<?= h($buildPageUrl($pageNum + 1)) ?>" aria-label="Page suivante"><span class="d-none d-sm-inline me-1">Suiv.</span><i class="bi bi-chevron-right"></i></a>
                    <?php endif; ?>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>
