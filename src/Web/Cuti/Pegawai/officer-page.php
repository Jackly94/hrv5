<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var array{slug: string, title: string, subtitle: string, icon: string, iconClass: string} $page
 * @var list<array<string, mixed>> $rows
 * @var bool $authorized
 * @var list<array<string, mixed>> $menuSections
 * @var string $legacyUrl
 * @var int $year
 * @var int|null $month
 */

$this->setTitle($page['title'] . ' - Cuti - ' . $applicationParams->name);
$statusClass = static fn (string $status): string => match (strtoupper($status)) {
    'APPROVED', 'VERIFIED', 'CHECKED' => 'status-badge-success',
    'REJECTED', 'RETURNED' => 'status-badge-danger',
    'ENTRY', 'AGREED', 'CHECK', 'BSMCHECK' => 'status-badge-warning',
    default => '',
};
$rowNumber = 1;
$months = [
    1 => 'Januari',
    2 => 'Februari',
    3 => 'Mac',
    4 => 'April',
    5 => 'Mei',
    6 => 'Jun',
    7 => 'Julai',
    8 => 'Ogos',
    9 => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Disember',
];
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
                <?php if ($authorized && $page['slug'] === 'list'): ?>
                    <form class="attendance-standard-filter" method="get" action="/cuti/pegawai/list">
                        <div class="attendance-standard-fields">
                            <label>Year
                                <select name="year">
                                    <?php for ($optionYear = (int) date('Y') + 1; $optionYear >= (int) date('Y') - 5; $optionYear--): ?>
                                        <option value="<?= Html::encode((string) $optionYear) ?>"<?= $optionYear === $year ? ' selected' : '' ?>><?= Html::encode((string) $optionYear) ?></option>
                                    <?php endfor; ?>
                                </select>
                            </label>
                            <label>Month
                                <select name="month">
                                    <option value="">All</option>
                                    <?php foreach ($months as $optionMonth => $label): ?>
                                        <option value="<?= Html::encode((string) $optionMonth) ?>"<?= $month === $optionMonth ? ' selected' : '' ?>><?= Html::encode($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        </div>
                        <div class="attendance-standard-actions">
                            <button class="button-link" type="submit"><i data-lucide="search"></i><span>Search</span></button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="attendance-standard-count">
                        <span>Records</span>
                        <strong><?= Html::encode((string) count($rows)) ?></strong>
                    </div>
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
                            <?php if ($page['slug'] === 'staff-leave'): ?>
                                <thead><tr><th>Bil</th><th>Nama</th><th>Staff No</th><th>Jabatan</th><th>Jenis Cuti</th><th>Peraku</th><th>Pelulus</th><th>Sejarah</th></tr></thead>
                                <tbody>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td><?= Html::encode((string) $rowNumber++) ?></td>
                                            <td><?= Html::encode((string) ($row['staff_name'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['staff_no'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['department_label'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['jenis_cuti_label'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['peraku_name'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['pelulus_name'] ?? '-')) ?></td>
                                            <td><a class="grid-action" href="<?= Html::encode($legacyUrl) ?>" target="_blank" rel="noopener"><i data-lucide="external-link"></i><span>Yii2</span></a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            <?php elseif ($page['slug'] === 'gcr-list-checked'): ?>
                                <thead><tr><th>Bil</th><th>Nama</th><th>Jabatan</th><th>Tahun</th><th>Mohon</th><th>Baki</th><th>GCR</th><th>CBTH</th><th>Status</th><th>Tindakan</th></tr></thead>
                                <tbody>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td><?= Html::encode((string) $rowNumber++) ?></td>
                                            <td><?= Html::encode((string) ($row['staff_name'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['department_label'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['gcr_year'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['mohon_dt'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['leave_balance'] ?? '0')) ?></td>
                                            <td><?= Html::encode((string) ($row['gcr_applied'] ?? '0')) ?></td>
                                            <td><?= Html::encode((string) ($row['cbth_applied'] ?? '0')) ?></td>
                                            <td><span class="status-badge <?= Html::encode($statusClass((string) ($row['status'] ?? ''))) ?>"><?= Html::encode((string) ($row['status'] ?? '-')) ?></span></td>
                                            <td><a class="grid-action attendance-primary-action" href="<?= Html::encode($legacyUrl) ?>" target="_blank" rel="noopener"><i data-lucide="external-link"></i><span>Yii2</span></a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            <?php elseif ($page['slug'] === 'sort'): ?>
                                <thead><tr><th>Bil</th><th>Jabatan</th><th>Permohonan</th><th>Tindakan</th></tr></thead>
                                <tbody>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td><?= Html::encode((string) $rowNumber++) ?></td>
                                            <td><?= Html::encode((string) ($row['department_label'] ?? '-')) ?></td>
                                            <td><span class="status-badge status-badge-warning"><?= Html::encode((string) ($row['total'] ?? '0')) ?></span></td>
                                            <td><a class="grid-action attendance-primary-action" href="<?= Html::encode($legacyUrl) ?>" target="_blank" rel="noopener"><i data-lucide="external-link"></i><span>Yii2</span></a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            <?php elseif ($page['slug'] === 'bod-list'): ?>
                                <thead><tr><th>Bil</th><th>Nama</th><th>Jabatan</th><th>Jenis Cuti</th><th>Tarikh Cuti</th><th>Tarikh BOD</th><th>Catatan</th><th>Status</th><th>Tindakan</th></tr></thead>
                                <tbody>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td><?= Html::encode((string) $rowNumber++) ?></td>
                                            <td><?= Html::encode((string) ($row['staff_name'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['department_label'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['jenis_cuti_label'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['date_label'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['date_bod'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['remark'] ?? '-')) ?></td>
                                            <td><span class="status-badge status-badge-warning"><?= Html::encode((string) ($row['status'] ?? '-')) ?></span></td>
                                            <td><a class="grid-action attendance-primary-action" href="<?= Html::encode($legacyUrl) ?>" target="_blank" rel="noopener"><i data-lucide="external-link"></i><span>Yii2</span></a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            <?php else: ?>
                                <thead><tr><th>Bil</th><th>Nama</th><th>Jabatan</th><th>Jenis Cuti</th><th>Tarikh</th><th>Tempoh</th><th>Catatan</th><th>Status</th><th>Tindakan</th></tr></thead>
                                <tbody>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td><?= Html::encode((string) $rowNumber++) ?></td>
                                            <td><?= Html::encode((string) ($row['staff_name'] ?? '-')) ?><br><small><?= Html::encode((string) ($row['staff_no'] ?? '-')) ?></small></td>
                                            <td><?= Html::encode((string) ($row['department_label'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['jenis_cuti_label'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['date_label'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['tempoh'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['remark'] ?? '-')) ?></td>
                                            <td><span class="status-badge <?= Html::encode($statusClass((string) ($row['status'] ?? ''))) ?>"><?= Html::encode((string) ($row['status_label'] ?? '-')) ?></span></td>
                                            <td><a class="grid-action attendance-primary-action" href="<?= Html::encode($legacyUrl) ?>" target="_blank" rel="noopener"><i data-lucide="external-link"></i><span>Yii2</span></a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
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
