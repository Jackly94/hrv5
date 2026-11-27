<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;
use Yiisoft\Yii\View\Renderer\Csrf;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var Csrf $csrf
 * @var array<string, mixed> $page
 */

$title = (string) ($page['title'] ?? 'Pengesahan Rekod Keahlian');
$this->setTitle($title . ' - ' . $applicationParams->name);
$queue = $page['queue'] ?? [];
$rows = $queue['rows'] ?? [];
$total = (int) ($queue['total'] ?? count($rows));
$pageNumber = (int) ($queue['page'] ?? 1);
$pageCount = (int) ($queue['pageCount'] ?? 1);
$offset = (int) ($queue['offset'] ?? 0);
$visibleCount = count($rows);
$firstVisible = $visibleCount === 0 ? 0 : $offset + 1;
$lastVisible = $offset + $visibleCount;
$searchType = (string) ($page['searchType'] ?? '1');
$search = (string) ($page['search'] ?? '');
$pageLink = static function (int $targetPage) use ($page, $searchType, $search): string {
    $query = ['page' => (string) $targetPage];
    if ($search !== '') {
        $query['jenis_carian'] = $searchType;
        $query['carian_data'] = $search;
    }

    return (string) $page['searchAction'] . '?' . http_build_query($query);
};
$startPage = max(1, $pageNumber - 2);
$endPage = min($pageCount, $pageNumber + 2);
if ($endPage - $startPage < 4) {
    $startPage = max(1, $endPage - 4);
    $endPage = min($pageCount, $startPage + 4);
}
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p>Pengesahan Data Staf</p>
            </div>
            <div class="profile-actions">
                <a class="button-link button-link-secondary" href="<?= Html::encode((string) $page['backUrl']) ?>">
                    <i data-lucide="arrow-left"></i>
                    <span>Kembali</span>
                </a>
            </div>
        </div>

        <div class="page-card-section page-section-body staff-overview-stack">
            <?php if (($page['signal'] ?? '') === 'updated'): ?>
                <div class="record-form-alert">
                    <div class="alert alert-success">Keahlian berjaya disahkan.</div>
                </div>
            <?php elseif (($page['signal'] ?? '') === 'error'): ?>
                <div class="record-form-alert">
                    <div class="alert alert-danger">Pengesahan keahlian tidak berjaya disimpan.</div>
                </div>
            <?php endif; ?>

            <section class="content-inner-card">
                <div class="profile-card-header grid-card-header">
                    <h2>Ruang Carian</h2>
                </div>
                <form class="record-form" action="<?= Html::encode((string) $page['searchAction']) ?>" method="get">
                    <div class="record-form-grid pds-keahlian-search-grid">
                        <label class="grid-field">
                            <span>Jenis Carian</span>
                            <select name="jenis_carian">
                                <option value="1"<?= $searchType === '1' ? ' selected' : '' ?>>IC</option>
                                <option value="2"<?= $searchType === '2' ? ' selected' : '' ?>>Nama</option>
                            </select>
                        </label>
                        <label class="grid-field">
                            <span>Nama / Nombor IC</span>
                            <input type="text" name="carian_data" value="<?= Html::encode($search) ?>">
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

            <section class="content-inner-card">
                <div class="profile-card-header grid-card-header grid-result-header">
                    <h2>Senarai Rekod Keahlian Belum Disahkan</h2>
                </div>
                <form class="record-form" action="<?= Html::encode((string) $page['verifyAction']) ?>" method="post">
                    <?= $csrf->hiddenInput() ?>
                    <input type="hidden" name="mode" value="staff-groups">
                    <div class="table-scroll grid-table-wrap">
                        <table class="data-table staff-overview-table staff-overview-table-approval-list pds-keahlian-queue-table">
                            <thead>
                            <tr>
                                <th>No. KP / Paspot</th>
                                <th>Nama</th>
                                <th>Jabatan</th>
                                <th>Belum Disahkan</th>
                                <th>Tindakan</th>
                                <th>
                                    <label class="table-check-control">
                                        <input type="checkbox" data-check-all="keahlian-queue">
                                        <span>Semua</span>
                                    </label>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if ($rows === []): ?>
                                <tr>
                                    <td class="grid-empty" colspan="6">Tiada Rekod</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td class="grid-name"><?= Html::encode((string) ($row['cells'][0] ?? '-')) ?></td>
                                    <td><?= Html::encode((string) ($row['cells'][1] ?? '-')) ?></td>
                                    <td><?= Html::encode((string) ($row['cells'][2] ?? '-')) ?></td>
                                    <td><span class="status-badge status-badge-warning"><?= Html::encode((string) ($row['cells'][3] ?? '0')) ?></span></td>
                                    <td>
                                        <?php if (($row['actionUrl'] ?? '') !== ''): ?>
                                            <a class="grid-action" href="<?= Html::encode((string) $row['actionUrl']) ?>">
                                                <i data-lucide="eye"></i>
                                                <span>Lihat</span>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <input type="checkbox" name="selection[]" value="<?= Html::encode((string) ($row['icno'] ?? '')) ?>" data-check-item="keahlian-queue">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="grid-count-footer">
                        <?php if ($pageCount > 1): ?>
                            <nav class="grid-pagination" aria-label="Halaman Keahlian">
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
                    <div class="record-form-actions">
                        <a class="button-link button-link-secondary" href="<?= Html::encode((string) $page['backUrl']) ?>">
                            <i data-lucide="arrow-left"></i>
                            <span>Kembali</span>
                        </a>
                        <button class="button-link-success" type="submit">
                            <i data-lucide="check"></i>
                            <span>Sahkan</span>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </section>
</main>
