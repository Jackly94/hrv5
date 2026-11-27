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
 * @var array<string, mixed>|null $page
 */

$title = $page === null ? 'Pengesahan Rekod Keahlian' : (string) ($page['title'] ?? 'Pengesahan Rekod Keahlian');
$this->setTitle($title . ' - ' . $applicationParams->name);
$staff = $page['staff'] ?? [];
$records = $page['records'] ?? [];
$rows = $records['rows'] ?? [];
$total = (int) ($records['total'] ?? count($rows));
$pageNumber = (int) ($records['page'] ?? 1);
$pageCount = (int) ($records['pageCount'] ?? 1);
$offset = (int) ($records['offset'] ?? 0);
$visibleCount = count($rows);
$firstVisible = $visibleCount === 0 ? 0 : $offset + 1;
$lastVisible = $offset + $visibleCount;
$icno = (string) ($staff['icno'] ?? '');
$pageLink = static function (int $targetPage) use ($icno): string {
    return '/pengesahan-data-staf/keahlian/staf?' . http_build_query([
        'icno' => $icno,
        'page' => (string) $targetPage,
    ]);
};
$startPage = max(1, $pageNumber - 2);
$endPage = min($pageCount, $pageNumber + 2);
if ($endPage - $startPage < 4) {
    $startPage = max(1, $endPage - 4);
    $endPage = min($pageCount, $startPage + 4);
}
$statusClass = static fn (string $status): string => $status === 'Aktif' ? 'status-badge-success' : 'status-badge-danger';
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p>Pengesahan Data Staf</p>
            </div>
            <?php if ($page !== null): ?>
                <div class="profile-actions">
                    <a class="button-link button-link-secondary" href="<?= Html::encode((string) $page['backUrl']) ?>">
                        <i data-lucide="arrow-left"></i>
                        <span>Kembali</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($page === null): ?>
            <div class="profile-empty">Rekod kakitangan tidak dijumpai.</div>
        <?php else: ?>
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
                    <div class="profile-card-header">
                        <h2>Maklumat Kakitangan</h2>
                    </div>
                    <dl class="detail-list">
                        <div>
                            <dt>No. KP / Paspot</dt>
                            <dd><?= Html::encode((string) ($staff['icno'] ?? '-')) ?></dd>
                        </div>
                        <div>
                            <dt>Nama Kakitangan</dt>
                            <dd><?= Html::encode((string) ($staff['name'] ?? '-')) ?></dd>
                        </div>
                        <div>
                            <dt>Jabatan</dt>
                            <dd><?= Html::encode((string) ($staff['department'] ?? '-')) ?></dd>
                        </div>
                        <div>
                            <dt>Status</dt>
                            <dd><?= Html::encode((string) ($staff['serviceStatus'] ?? '-')) ?></dd>
                        </div>
                    </dl>
                </section>

                <section class="content-inner-card">
                    <div class="profile-card-header grid-card-header grid-result-header">
                        <h2>Keahlian Belum Disahkan</h2>
                    </div>
                    <form class="record-form" action="<?= Html::encode((string) $page['verifyAction']) ?>" method="post">
                        <?= $csrf->hiddenInput() ?>
                        <input type="hidden" name="mode" value="rows">
                        <input type="hidden" name="icno" value="<?= Html::encode($icno) ?>">
                        <div class="table-scroll grid-table-wrap">
                            <table class="data-table staff-overview-table staff-overview-table-approval-list pds-keahlian-staff-table">
                                <thead>
                                <tr>
                                    <th>Nama Badan / Keahlian</th>
                                    <th>Taraf Keahlian</th>
                                    <th>No. Keahlian</th>
                                    <th>Status</th>
                                    <th>
                                        <label class="table-check-control">
                                            <input type="checkbox" data-check-all="keahlian-staff">
                                            <span>Semua</span>
                                        </label>
                                    </th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if ($rows === []): ?>
                                    <tr>
                                        <td class="grid-empty" colspan="5">Tiada Rekod</td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td class="grid-name">
                                            <?php if (($row['detailUrl'] ?? '') !== ''): ?>
                                                <a class="table-text-link" href="<?= Html::encode((string) $row['detailUrl']) ?>" target="_blank" rel="noopener">
                                                    <?= Html::encode((string) ($row['cells'][0] ?? '-')) ?>
                                                </a>
                                            <?php else: ?>
                                                <?= Html::encode((string) ($row['cells'][0] ?? '-')) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= Html::encode((string) ($row['cells'][1] ?? '-')) ?></td>
                                        <td><?= Html::encode((string) ($row['cells'][3] ?? '-')) ?></td>
                                        <td><span class="status-badge <?= Html::encode($statusClass((string) ($row['cells'][6] ?? ''))) ?>"><?= Html::encode((string) ($row['cells'][6] ?? '-')) ?></span></td>
                                        <td>
                                            <input type="checkbox" name="selection[]" value="<?= Html::encode((string) ($row['id'] ?? '')) ?>" data-check-item="keahlian-staff">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="grid-count-footer">
                            <?php if ($pageCount > 1): ?>
                                <nav class="grid-pagination" aria-label="Halaman Rekod Keahlian">
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
        <?php endif; ?>
    </section>
</main>
