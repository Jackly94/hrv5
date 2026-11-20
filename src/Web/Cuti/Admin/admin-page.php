<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use App\Shared\LegacyUrl;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var array{slug: string, title: string, subtitle: string, icon: string, iconClass: string} $page
 * @var list<array<string, mixed>> $rows
 * @var bool $authorized
 * @var list<array<string, mixed>> $menuSections
 * @var list<array{id: string, name: string}> $departments
 * @var list<array{id: string, name: string}> $years
 * @var array<string, mixed> $query
 * @var int $year
 * @var string $legacyUrl
 */

$this->setTitle($page['title'] . ' - Cuti - ' . $applicationParams->name);
$statusClass = static fn (string $status): string => match (strtoupper($status)) {
    'APPROVED', 'VERIFIED', 'CHECKED', '1' => 'status-badge-success',
    'REJECTED', 'RETURNED', '0' => 'status-badge-danger',
    'ENTRY', 'AGREED', 'CHECK', 'BSMCHECK', 'VERIFIED_KJ' => 'status-badge-warning',
    default => '',
};
$selected = static fn (string $key, string $value): string => isset($query[$key]) && (string) $query[$key] === $value ? ' selected' : '';
$value = static fn (string $key): string => isset($query[$key]) && is_scalar($query[$key]) ? (string) $query[$key] : '';
$rowNumber = 1;
$filterPages = ['cr2-list', 'gcr-application-list', 'ph-list', 'cb-list', 'staff-list', 'report', 'report-leave-list', 'access'];
?>

<main class="cuti-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($page['title']) ?></h1>
                <p>Cuti</p>
            </div>
            <a class="button-link button-link-secondary" href="/cuti/individu/index"><i data-lucide="arrow-left"></i><span>Kembali</span></a>
        </div>

        <div class="page-section-body page-card-section">
            <?php require dirname(__DIR__) . '/Shared/menu.php'; ?>
        </div>

        <div class="page-section-body page-card-section">
            <section class="content-inner-card attendance-standard-toolbar">
                <div class="attendance-standard-title">
                    <span class="attendance-standard-icon <?= Html::encode($page['iconClass']) ?>" aria-hidden="true"><i data-lucide="<?= Html::encode($page['icon']) ?>"></i></span>
                    <div>
                        <h2><?= Html::encode($page['title']) ?></h2>
                        <p><?= Html::encode($authorized ? $page['subtitle'] : 'Access is not available for this account.') ?></p>
                    </div>
                </div>
                <?php if ($authorized && in_array($page['slug'], $filterPages, true)): ?>
                    <form class="attendance-standard-filter" method="get" action="/cuti/admin/<?= Html::encode($page['slug']) ?>">
                        <div class="attendance-standard-fields attendance-standard-fields-wide">
                            <?php if (in_array($page['slug'], ['access', 'staff-list'], true)): ?>
                                <label>Search <input type="text" name="q" value="<?= Html::encode($value('q')) ?>"></label>
                            <?php endif; ?>
                            <?php if (in_array($page['slug'], ['ph-list', 'cr2-list', 'gcr-application-list', 'cb-list', 'report', 'report-leave-list'], true)): ?>
                                <label>Year
                                    <select name="year">
                                        <?php foreach ($years === [] ? [['id' => (string) $year, 'name' => (string) $year]] : $years as $option): ?>
                                            <option value="<?= Html::encode($option['id']) ?>"<?= (string) $year === $option['id'] || $selected('year', $option['id']) !== '' ? ' selected' : '' ?>><?= Html::encode($option['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            <?php endif; ?>
                            <?php if (in_array($page['slug'], ['staff-list', 'report', 'report-leave-list', 'cr2-list', 'cb-list'], true)): ?>
                                <label>Department
                                    <select name="department" data-searchable-select>
                                        <option value="">All</option>
                                        <?php foreach ($departments as $option): ?>
                                            <option value="<?= Html::encode($option['id']) ?>"<?= $selected('department', $option['id']) ?>><?= Html::encode($option['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            <?php endif; ?>
                            <?php if (in_array($page['slug'], ['gcr-application-list', 'cb-list', 'report'], true)): ?>
                                <label>Status <input type="text" name="status" value="<?= Html::encode($value('status')) ?>"></label>
                            <?php endif; ?>
                        </div>
                        <div class="attendance-standard-actions">
                            <button class="button-link" type="submit"><i data-lucide="search"></i><span>Search</span></button>
                            <?php if (in_array($page['slug'], ['report', 'report-leave-list'], true)): ?>
                                <a class="button-link button-link-secondary" href="<?= Html::encode($legacyUrl) ?>" target="_blank" rel="noopener"><i data-lucide="download"></i><span>Yii2</span></a>
                            <?php endif; ?>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="attendance-standard-count"><span>Records</span><strong><?= Html::encode((string) count($rows)) ?></strong></div>
                <?php endif; ?>
            </section>
        </div>

        <div class="page-section-body page-card-section">
            <section class="content-inner-card staff-page-panel attendance-compact-panel">
                <?php if (!$authorized): ?>
                    <div class="profile-empty">Anda tidak mempunyai akses untuk paparan ini.</div>
                <?php elseif ($rows === []): ?>
                    <div class="profile-empty">Tiada rekod ditemui.</div>
                <?php else: ?>
                    <div class="responsive-table">
                        <table class="data-table attendance-compact-table">
                            <?php if ($page['slug'] === 'behalf-list'): ?>
                                <thead><tr><th>Bil</th><th>Pemberi Kuasa</th><th>Pegawai Tindakan</th><th>Catatan</th><th>Status</th><th>Tindakan</th></tr></thead>
                                <tbody><?php foreach ($rows as $row): ?><tr>
                                    <td><?= Html::encode((string) $rowNumber++) ?></td>
                                    <td><?= Html::encode((string) ($row['officer_name'] ?? '-')) ?></td>
                                    <td><?= Html::encode((string) ($row['actioner_name'] ?? '-')) ?></td>
                                    <td><?= Html::encode((string) ($row['catatan'] ?? '-')) ?></td>
                                    <td><span class="status-badge <?= Html::encode($statusClass((string) ($row['status'] ?? ''))) ?>"><?= Html::encode((string) ($row['status'] ?? '-')) ?></span></td>
                                    <td><a class="grid-action" href="<?= Html::encode(LegacyUrl::to('cuti/admin/update-action') . '?id=' . rawurlencode((string) ($row['id'] ?? ''))) ?>" target="_blank" rel="noopener"><i data-lucide="pencil"></i><span>Yii2</span></a></td>
                                </tr><?php endforeach; ?></tbody>
                            <?php elseif ($page['slug'] === 'access'): ?>
                                <thead><tr><th>Bil</th><th>Nama</th><th>Staff No</th><th>Akses</th><th>Jabatan</th><th>Kampus</th><th>Tindakan</th></tr></thead>
                                <tbody><?php foreach ($rows as $row): ?><tr>
                                    <td><?= Html::encode((string) $rowNumber++) ?></td>
                                    <td><?= Html::encode((string) ($row['staff_name'] ?? '-')) ?></td>
                                    <td><?= Html::encode((string) ($row['staff_no'] ?? '-')) ?></td>
                                    <td><span class="status-badge"><?= Html::encode((string) ($row['access_label'] ?? '-')) ?></span></td>
                                    <td><?= Html::encode((string) ($row['department_label'] ?? '-')) ?></td>
                                    <td><?= Html::encode((string) ($row['campus_label'] ?? '-')) ?></td>
                                    <td><a class="grid-action" href="<?= Html::encode(LegacyUrl::to('cuti/admin/update-access') . '?id=' . rawurlencode((string) ($row['akses_cuti_id'] ?? ''))) ?>" target="_blank" rel="noopener"><i data-lucide="pencil"></i><span>Yii2</span></a></td>
                                </tr><?php endforeach; ?></tbody>
                            <?php elseif ($page['slug'] === 'gcr-application-list'): ?>
                                <thead><tr><th>Bil</th><th>Nama</th><th>Jabatan</th><th>Tahun</th><th>Mohon</th><th>Baki</th><th>GCR</th><th>CBTH</th><th>Status</th><th>Tindakan</th></tr></thead>
                                <tbody><?php foreach ($rows as $row): ?><tr>
                                    <td><?= Html::encode((string) $rowNumber++) ?></td>
                                    <td><?= Html::encode((string) ($row['staff_name'] ?? '-')) ?><br><small><?= Html::encode((string) ($row['staff_no'] ?? '-')) ?></small></td>
                                    <td><?= Html::encode((string) ($row['department_label'] ?? '-')) ?></td>
                                    <td><?= Html::encode((string) ($row['gcr_year'] ?? '-')) ?></td>
                                    <td><?= Html::encode((string) ($row['mohon_dt'] ?? '-')) ?></td>
                                    <td><?= Html::encode((string) ($row['leave_balance'] ?? '0')) ?></td>
                                    <td><?= Html::encode((string) ($row['gcr_applied'] ?? '0')) ?></td>
                                    <td><?= Html::encode((string) ($row['cbth_applied'] ?? '0')) ?></td>
                                    <td><span class="status-badge <?= Html::encode($statusClass((string) ($row['status'] ?? ''))) ?>"><?= Html::encode((string) ($row['status'] ?? '-')) ?></span></td>
                                    <td><a class="grid-action" href="<?= Html::encode(LegacyUrl::to('cuti/admin/update-gcr') . '?id=' . rawurlencode((string) ($row['id'] ?? ''))) ?>" target="_blank" rel="noopener"><i data-lucide="pencil"></i><span>Yii2</span></a></td>
                                </tr><?php endforeach; ?></tbody>
                            <?php elseif ($page['slug'] === 'open-application'): ?>
                                <thead><tr><th>Bil</th><th>Tarikh</th><th>Catatan</th><th>Status</th><th>Open By</th><th>Tindakan</th></tr></thead>
                                <tbody><?php foreach ($rows as $row): ?><tr>
                                    <td><?= Html::encode((string) $rowNumber++) ?></td>
                                    <td><?= Html::encode((string) ($row['start_date'] ?? '-')) ?> - <?= Html::encode((string) ($row['end_date'] ?? '-')) ?></td>
                                    <td><?= Html::encode((string) ($row['remark'] ?? '-')) ?></td>
                                    <td><span class="status-badge <?= Html::encode($statusClass((string) ($row['status'] ?? ''))) ?>"><?= Html::encode((string) ($row['status'] ?? '-')) ?></span></td>
                                    <td><?= Html::encode((string) ($row['open_by_name'] ?? '-')) ?></td>
                                    <td><a class="grid-action" href="<?= Html::encode(LegacyUrl::to('cuti/admin/update-open') . '?id=' . rawurlencode((string) ($row['id'] ?? ''))) ?>" target="_blank" rel="noopener"><i data-lucide="pencil"></i><span>Yii2</span></a></td>
                                </tr><?php endforeach; ?></tbody>
                            <?php elseif ($page['slug'] === 'ph-list'): ?>
                                <thead><tr><th>Bil</th><th>Tarikh</th><th>Nama Cuti</th><th>Sabah</th><th>Wilayah</th><th>Catatan</th><th>Tindakan</th></tr></thead>
                                <tbody><?php foreach ($rows as $row): ?><tr>
                                    <td><?= Html::encode((string) $rowNumber++) ?></td>
                                    <td><?= Html::encode((string) ($row['tarikh_cuti'] ?? '-')) ?></td>
                                    <td><?= Html::encode((string) ($row['nama_cuti'] ?? '-')) ?></td>
                                    <td><?= Html::encode((string) ($row['sabah_sahaja'] ?? '-')) ?></td>
                                    <td><?= Html::encode((string) ($row['wilayah_sahaja'] ?? '-')) ?></td>
                                    <td><?= Html::encode((string) ($row['catatan'] ?? '-')) ?></td>
                                    <td><a class="grid-action" href="<?= Html::encode(LegacyUrl::to('cuti/admin/update-ph') . '?id=' . rawurlencode((string) ($row['id'] ?? ''))) ?>" target="_blank" rel="noopener"><i data-lucide="pencil"></i><span>Yii2</span></a></td>
                                </tr><?php endforeach; ?></tbody>
                            <?php elseif ($page['slug'] === 'admin-index'): ?>
                                <thead><tr><th>Bil</th><th>Nama</th><th>Staff No</th><th>Status</th><th>Added By</th><th>Added At</th><th>Tindakan</th></tr></thead>
                                <tbody><?php foreach ($rows as $row): ?><tr>
                                    <td><?= Html::encode((string) $rowNumber++) ?></td>
                                    <td><?= Html::encode((string) ($row['staff_name'] ?? '-')) ?></td>
                                    <td><?= Html::encode((string) ($row['staff_no'] ?? '-')) ?></td>
                                    <td><span class="status-badge <?= Html::encode($statusClass((string) ($row['allowed'] ?? ''))) ?>"><?= Html::encode((string) ($row['allowed'] ?? '-')) ?></span></td>
                                    <td><?= Html::encode((string) ($row['added_by_name'] ?? '-')) ?></td>
                                    <td><?= Html::encode((string) ($row['added_dt'] ?? '-')) ?></td>
                                    <td><a class="grid-action" href="<?= Html::encode(LegacyUrl::to('cuti/admin/updates') . '?id=' . rawurlencode((string) ($row['id'] ?? ''))) ?>" target="_blank" rel="noopener"><i data-lucide="pencil"></i><span>Yii2</span></a></td>
                                </tr><?php endforeach; ?></tbody>
                            <?php elseif ($page['slug'] === 'staff-list'): ?>
                                <thead><tr><th>Bil</th><th>Nama</th><th>Staff No</th><th>Jabatan</th><th>Kampus</th><th>Status</th><th>Laporan</th></tr></thead>
                                <tbody><?php foreach ($rows as $row): ?><tr>
                                    <td><?= Html::encode((string) $rowNumber++) ?></td>
                                    <td><?= Html::encode((string) ($row['staff_name'] ?? '-')) ?></td>
                                    <td><?= Html::encode((string) ($row['staff_no'] ?? '-')) ?></td>
                                    <td><?= Html::encode((string) ($row['department_label'] ?? '-')) ?></td>
                                    <td><?= Html::encode((string) ($row['campus_label'] ?? '-')) ?></td>
                                    <td><span class="status-badge"><?= Html::encode((string) ($row['Status'] ?? '-')) ?></span></td>
                                    <td><a class="grid-action" href="<?= Html::encode(LegacyUrl::to('cuti/admin/report-absent') . '?id=' . rawurlencode((string) ($row['ICNO'] ?? ''))) ?>" target="_blank" rel="noopener"><i data-lucide="bar-chart-3"></i><span>Yii2</span></a></td>
                                </tr><?php endforeach; ?></tbody>
                            <?php elseif ($page['slug'] === 'report-leave-list'): ?>
                                <thead><tr><th>Bil</th><th>Jenis Cuti</th><th>Bilangan</th><th>Hari</th></tr></thead>
                                <tbody><?php foreach ($rows as $row): ?><tr>
                                    <td><?= Html::encode((string) $rowNumber++) ?></td>
                                    <td><?= Html::encode((string) ($row['jenis_cuti_label'] ?? '-')) ?></td>
                                    <td><span class="status-badge status-badge-warning"><?= Html::encode((string) ($row['total_records'] ?? '0')) ?></span></td>
                                    <td><?= Html::encode((string) ($row['total_days'] ?? '0')) ?></td>
                                </tr><?php endforeach; ?></tbody>
                            <?php else: ?>
                                <thead><tr><th>Bil</th><th>Nama</th><th>Jabatan</th><th>Jenis Cuti</th><th>Tarikh</th><th>Tempoh</th><th>Status</th><th>Tindakan</th></tr></thead>
                                <tbody><?php foreach ($rows as $row): ?><tr>
                                    <td><?= Html::encode((string) $rowNumber++) ?></td>
                                    <td><?= Html::encode((string) ($row['staff_name'] ?? '-')) ?><br><small><?= Html::encode((string) ($row['staff_no'] ?? '-')) ?></small></td>
                                    <td><?= Html::encode((string) ($row['department_label'] ?? '-')) ?></td>
                                    <td><?= Html::encode((string) ($row['jenis_cuti_label'] ?? '-')) ?></td>
                                    <td><?= Html::encode((string) ($row['date_label'] ?? '-')) ?></td>
                                    <td><?= Html::encode((string) ($row['tempoh'] ?? '-')) ?></td>
                                    <td><span class="status-badge <?= Html::encode($statusClass((string) ($row['status'] ?? ''))) ?>"><?= Html::encode((string) ($row['status_label'] ?? '-')) ?></span></td>
                                    <td><a class="grid-action" href="<?= Html::encode(LegacyUrl::to('cuti/admin/cr2-detail') . '?id=' . rawurlencode((string) ($row['id'] ?? ''))) ?>" target="_blank" rel="noopener"><i data-lucide="eye"></i><span>Yii2</span></a></td>
                                </tr><?php endforeach; ?></tbody>
                            <?php endif; ?>
                        </table>
                    </div>
                <?php endif; ?>
                <div class="grid-count-footer">
                    <span class="grid-count">Papar <?= Html::encode((string) count($rows)) ?> rekod</span>
                </div>
            </section>
        </div>
    </section>
</main>
