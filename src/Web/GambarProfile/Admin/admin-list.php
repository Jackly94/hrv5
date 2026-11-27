<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var array<string, mixed> $list
 * @var string $notice
 * @var string $message
 */

$title = (string) $list['title'];
$columns = $list['columns'] ?? [];
$rows = $list['rows'] ?? [];
$columnCount = count($columns) + 1;
$total = (int) ($list['total'] ?? count($rows));
$page = max(1, (int) ($list['page'] ?? 1));
$pageCount = max(1, (int) ($list['pageCount'] ?? 1));
$offset = (int) ($list['offset'] ?? 0);
$visibleCount = count($rows);
$firstVisible = $visibleCount === 0 ? 0 : $offset + 1;
$lastVisible = $offset + $visibleCount;
$search = (string) ($list['search'] ?? '');
$searchAction = (string) ($list['searchAction'] ?? $list['currentUrl'] ?? '/gambar-profile/senarai-admin');
$pageLink = static function (int $targetPage) use ($searchAction, $search): string {
    $query = [];
    if ($search !== '') {
        $query['carian'] = $search;
    }
    if ($targetPage > 1) {
        $query['page'] = (string) $targetPage;
    }

    return $searchAction . ($query === [] ? '' : '?' . http_build_query($query));
};
$startPage = max(1, $page - 2);
$endPage = min($pageCount, $page + 2);
if ($endPage - $startPage < 4) {
    $startPage = max(1, $endPage - 4);
    $endPage = min($pageCount, $startPage + 4);
}
$statusClass = static fn (string $status): string => $status === 'Aktif' ? 'status-badge-success' : 'status-badge-danger';
$this->setTitle($title . ' - ' . $applicationParams->name);
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p>Pengurusan akses admin gambar profil</p>
            </div>
            <div class="profile-actions">
                <a class="button-link" href="<?= Html::encode((string) $list['createUrl']) ?>">
                    <i data-lucide="user-plus"></i>
                    <span>Tambah Admin</span>
                </a>
            </div>
        </div>

        <div class="page-card-section page-section-body staff-overview-stack">
            <?php if ($message !== ''): ?>
                <div class="record-form-alert">
                    <div class="alert <?= $notice === 'success' ? 'alert-success' : 'alert-danger' ?>"><?= Html::encode($message) ?></div>
                </div>
            <?php endif; ?>

            <section class="content-inner-card">
                <div class="profile-card-header grid-card-header">
                    <h2>Ruang Carian</h2>
                </div>
                <form class="record-form" action="<?= Html::encode($searchAction) ?>" method="get">
                    <div class="record-form-grid gambar-profile-admin-search-grid">
                        <label class="grid-field grid-field-wide">
                            <span>Nama / Nombor IC / Pendaftar</span>
                            <input type="text" name="carian" value="<?= Html::encode($search) ?>">
                        </label>
                    </div>
                    <div class="record-form-actions">
                        <button class="button-link" type="submit">
                            <i data-lucide="search"></i>
                            <span>Cari</span>
                        </button>
                        <?php if ($search !== ''): ?>
                            <a class="button-link button-link-secondary" href="<?= Html::encode($searchAction) ?>">
                                <i data-lucide="rotate-ccw"></i>
                                <span>Reset</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </section>

            <section class="content-inner-card">
                <div class="profile-card-header grid-card-header grid-result-header">
                    <div>
                        <h2><?= Html::encode((string) $list['sectionTitle']) ?></h2>
                        <p>Jumlah Carian: <?= Html::encode((string) $total) ?></p>
                    </div>
                </div>

                <div class="table-scroll grid-table-wrap">
                    <table class="data-table staff-overview-table staff-overview-table-approval-list gambar-profile-admin-table">
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
                                        <?php if (($row['viewUrl'] ?? '') !== ''): ?>
                                            <a class="grid-action" href="<?= Html::encode((string) $row['viewUrl']) ?>">
                                                <i data-lucide="<?= Html::encode((string) $row['actionIcon']) ?>"></i>
                                                <span><?= Html::encode((string) $row['actionLabel']) ?></span>
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
                        <nav class="grid-pagination" aria-label="Halaman <?= Html::encode((string) $list['sectionTitle']) ?>">
                            <a class="<?= $page === 1 ? 'is-disabled' : '' ?>" href="<?= Html::encode($pageLink(max(1, $page - 1))) ?>" aria-label="Halaman sebelumnya">
                                <i data-lucide="chevron-left"></i>
                                <span>Sebelum</span>
                            </a>
                            <?php for ($targetPage = $startPage; $targetPage <= $endPage; $targetPage++): ?>
                                <a class="<?= $targetPage === $page ? 'is-active' : '' ?>" href="<?= Html::encode($pageLink($targetPage)) ?>">
                                    <?= Html::encode((string) $targetPage) ?>
                                </a>
                            <?php endfor; ?>
                            <a class="<?= $page === $pageCount ? 'is-disabled' : '' ?>" href="<?= Html::encode($pageLink(min($pageCount, $page + 1))) ?>" aria-label="Halaman seterusnya">
                                <span>Seterus</span>
                                <i data-lucide="chevron-right"></i>
                            </a>
                        </nav>
                    <?php endif; ?>
                    <span class="grid-count">Papar <?= Html::encode((string) $firstVisible) ?>-<?= Html::encode((string) $lastVisible) ?> daripada <?= Html::encode((string) $total) ?> rekod</span>
                </div>
            </section>
        </div>
    </section>
</main>
