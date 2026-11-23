<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var array<string, mixed> $page
 */

$title = (string) ($page['title'] ?? 'Senarai Permohonan Akaun Bank');
$this->setTitle($title . ' - ' . $applicationParams->name);
$renderTable = static function (array $section) use ($page): void {
    $columns = $section['columns'] ?? [];
    $rows = $section['rows'] ?? [];
    $columnCount = count($columns) + 1;
    $total = (int) ($section['total'] ?? count($rows));
    $pageNumber = (int) ($section['page'] ?? 1);
    $pageCount = (int) ($section['pageCount'] ?? 1);
    $offset = (int) ($section['offset'] ?? 0);
    $visibleCount = count($rows);
    $firstVisible = $visibleCount === 0 ? 0 : $offset + 1;
    $lastVisible = $offset + $visibleCount;
    $query = [];
    $search = (string) ($page['search'] ?? '');
    if ($search !== '') {
        $query['icno'] = $search;
    }
    foreach (['pending', 'history'] as $key) {
        if (isset($page[$key]['pageParam'], $page[$key]['page'])) {
            $query[(string) $page[$key]['pageParam']] = (string) $page[$key]['page'];
        }
    }
    $pageParam = (string) ($section['pageParam'] ?? 'page');
    $pageLink = static function (int $targetPage) use ($page, $query, $pageParam): string {
        $targetQuery = $query;
        $targetQuery[$pageParam] = (string) $targetPage;

        return (string) $page['searchAction'] . '?' . http_build_query($targetQuery);
    };
    $startPage = max(1, $pageNumber - 2);
    $endPage = min($pageCount, $pageNumber + 2);
    if ($endPage - $startPage < 4) {
        $startPage = max(1, $endPage - 4);
        $endPage = min($pageCount, $startPage + 4);
    }
    $statusClass = static function (string $status): string {
        return match ($status) {
            'Diluluskan' => 'status-badge-success',
            'Ditolak', 'Dikembalikan' => 'status-badge-danger',
            default => 'status-badge-warning',
        };
    };
    ?>
    <section class="content-inner-card">
        <div class="profile-card-header grid-card-header grid-result-header">
            <div>
                <h2><?= Html::encode((string) $section['title']) ?></h2>
            </div>
        </div>

        <div class="table-scroll grid-table-wrap">
            <table class="data-table staff-overview-table staff-overview-table-approval-list">
                <thead>
                <tr>
                    <?php foreach ($columns as $column): ?>
                        <th><?= Html::encode((string) $column) ?></th>
                    <?php endforeach; ?>
                    <th>Tindakan</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($rows === []): ?>
                    <tr>
                        <td class="grid-empty" colspan="<?= Html::encode((string) $columnCount) ?>">Tiada Rekod</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <?php foreach ($row['cells'] as $index => $cell): ?>
                            <?php $isStatusColumn = ($columns[$index] ?? '') === 'Status'; ?>
                            <td<?= $index === 0 ? ' class="grid-name"' : '' ?>>
                                <?php if ($isStatusColumn): ?>
                                    <span class="status-badge <?= Html::encode($statusClass((string) $cell)) ?>"><?= Html::encode((string) $cell) ?></span>
                                <?php else: ?>
                                    <?= Html::encode((string) $cell) ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        <td>
                            <div class="grid-actions-inline">
                                <?php if (($row['actionUrl'] ?? '') !== ''): ?>
                                    <a class="grid-action" href="<?= Html::encode((string) $row['actionUrl']) ?>">
                                        <i data-lucide="eye"></i>
                                        <span>Lihat</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="grid-count-footer">
            <?php if ($pageCount > 1): ?>
                <nav class="grid-pagination" aria-label="Halaman <?= Html::encode((string) $section['title']) ?>">
                    <a class="<?= $pageNumber === 1 ? 'is-disabled' : '' ?>" href="<?= Html::encode($pageLink(max(1, $pageNumber - 1))) ?>" aria-label="Halaman sebelumnya">
                        <i data-lucide="chevron-left"></i>
                        <span>Sebelum</span>
                    </a>
                    <?php for ($targetPage = $startPage; $targetPage <= $endPage; $targetPage++): ?>
                        <a class="<?= $targetPage === $pageNumber ? 'is-active' : '' ?>" href="<?= Html::encode($pageLink($targetPage)) ?>">
                            <?= Html::encode((string) $targetPage) ?>
                        </a>
                    <?php endfor; ?>
                    <a class="<?= $pageNumber === $pageCount ? 'is-disabled' : '' ?>" href="<?= Html::encode($pageLink(min($pageCount, $pageNumber + 1))) ?>" aria-label="Halaman seterusnya">
                        <span>Seterus</span>
                        <i data-lucide="chevron-right"></i>
                    </a>
                </nav>
            <?php endif; ?>
            <span class="grid-count">Papar <?= Html::encode((string) $firstVisible) ?>-<?= Html::encode((string) $lastVisible) ?> daripada <?= Html::encode((string) $total) ?> rekod</span>
        </div>
    </section>
    <?php
};
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p>Akaun bank gaji kakitangan</p>
            </div>
            <div class="profile-actions">
                <a class="button-link button-link-secondary" href="<?= Html::encode((string) $page['backUrl']) ?>">
                    <i data-lucide="arrow-left"></i>
                    <span>Kembali</span>
                </a>
            </div>
        </div>

        <div class="page-card-section page-section-body staff-overview-stack">
            <section class="content-inner-card">
                <div class="profile-card-header grid-card-header">
                    <h2>Ruang Carian</h2>
                </div>
                <form class="record-form" action="<?= Html::encode((string) $page['searchAction']) ?>" method="get">
                    <div class="record-form-grid">
                        <label class="grid-field grid-field-wide">
                            <span>Nama / Nombor IC</span>
                            <input type="text" name="icno" value="<?= Html::encode((string) ($page['search'] ?? '')) ?>">
                        </label>
                    </div>
                    <div class="record-form-actions">
                        <button class="button-link" type="submit">
                            <i data-lucide="search"></i>
                            <span>Cari</span>
                        </button>
                    </div>
                </form>
            </section>

            <?php $renderTable($page['pending']); ?>
            <?php $renderTable($page['history']); ?>
        </div>
    </section>
</main>
