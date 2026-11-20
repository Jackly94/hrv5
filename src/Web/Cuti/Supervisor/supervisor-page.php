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
 * @var string $legacyUrl
 * @var list<array{id: string, name: string}> $departments
 * @var list<array{id: string, name: string}> $campuses
 * @var list<array{id: string, name: string}> $leaveTypes
 * @var array<string, mixed> $query
 * @var int $year
 * @var int $month
 */

$this->setTitle($page['title'] . ' - Cuti - ' . $applicationParams->name);
$statusClass = static fn (string $status): string => match (strtoupper($status)) {
    'APPROVED', 'VERIFIED', 'CHECKED' => 'status-badge-success',
    'REJECTED', 'RETURNED', 'CANCELLED' => 'status-badge-danger',
    'ENTRY', 'AGREED', 'CHECK', 'BSMCHECK' => 'status-badge-warning',
    default => '',
};
$selected = static fn (string $key, string $value): string => isset($query[$key]) && (string) $query[$key] === $value ? ' selected' : '';
$value = static fn (string $key): string => isset($query[$key]) && is_scalar($query[$key]) ? (string) $query[$key] : '';
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
$rowNumber = 1;
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
                <?php if ($authorized && $page['slug'] === 'supervised-staff'): ?>
                    <form class="attendance-standard-filter" method="get" action="/cuti/supervisor/supervised-staff">
                        <div class="attendance-standard-fields attendance-standard-fields-wide">
                            <label>Search <input type="text" name="q" value="<?= Html::encode($value('q')) ?>"></label>
                            <label>Department
                                <select name="department" data-searchable-select>
                                    <option value="">All</option>
                                    <?php foreach ($departments as $option): ?>
                                        <option value="<?= Html::encode($option['id']) ?>"<?= $selected('department', $option['id']) ?>><?= Html::encode($option['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>Campus
                                <select name="campus" data-searchable-select>
                                    <option value="">All</option>
                                    <?php foreach ($campuses as $option): ?>
                                        <option value="<?= Html::encode($option['id']) ?>"<?= $selected('campus', $option['id']) ?>><?= Html::encode($option['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        </div>
                        <div class="attendance-standard-actions"><button class="button-link" type="submit"><i data-lucide="search"></i><span>Search</span></button></div>
                    </form>
                <?php elseif ($authorized && in_array($page['slug'], ['leave-statement-report', 'leave-monitoring', 'calender'], true)): ?>
                    <form class="attendance-standard-filter" method="get" action="/cuti/supervisor/<?= Html::encode($page['slug']) ?>">
                        <div class="attendance-standard-fields attendance-standard-fields-wide">
                            <?php if ($page['slug'] === 'leave-monitoring'): ?>
                                <label>Search <input type="text" name="q" value="<?= Html::encode($value('q')) ?>"></label>
                            <?php endif; ?>
                            <label>Department
                                <select name="department" data-searchable-select>
                                    <option value="">All</option>
                                    <?php foreach ($departments as $option): ?>
                                        <option value="<?= Html::encode($option['id']) ?>"<?= $selected('department', $option['id']) ?>><?= Html::encode($option['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>Year
                                <select name="year">
                                    <?php for ($optionYear = (int) date('Y') + 1; $optionYear >= (int) date('Y') - 5; $optionYear--): ?>
                                        <option value="<?= Html::encode((string) $optionYear) ?>"<?= $optionYear === $year ? ' selected' : '' ?>><?= Html::encode((string) $optionYear) ?></option>
                                    <?php endfor; ?>
                                </select>
                            </label>
                            <label>Month
                                <select name="month">
                                    <?php if ($page['slug'] !== 'calender'): ?><option value="">All</option><?php endif; ?>
                                    <?php foreach ($months as $optionMonth => $label): ?>
                                        <?php $monthIsSelected = $page['slug'] === 'calender' ? $optionMonth === $month : $selected('month', (string) $optionMonth) !== ''; ?>
                                        <option value="<?= Html::encode((string) $optionMonth) ?>"<?= $monthIsSelected ? ' selected' : '' ?>><?= Html::encode($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <?php if ($page['slug'] !== 'calender'): ?>
                                <label>Leave Type
                                    <select name="jenis_cuti_id" data-searchable-select>
                                        <option value="">All</option>
                                        <?php foreach ($leaveTypes as $option): ?>
                                            <option value="<?= Html::encode($option['id']) ?>"<?= $selected('jenis_cuti_id', $option['id']) ?>><?= Html::encode($option['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            <?php endif; ?>
                            <?php if ($page['slug'] === 'leave-monitoring'): ?>
                                <label>Status <input type="text" name="status" value="<?= Html::encode($value('status')) ?>"></label>
                            <?php endif; ?>
                        </div>
                        <div class="attendance-standard-actions">
                            <button class="button-link" type="submit"><i data-lucide="search"></i><span>Search</span></button>
                            <?php if ($page['slug'] === 'leave-statement-report'): ?>
                                <a class="button-link button-link-secondary" href="<?= Html::encode($legacyUrl) ?>" target="_blank" rel="noopener"><i data-lucide="download"></i><span>Yii2</span></a>
                            <?php endif; ?>
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
                            <?php if ($page['slug'] === 'supervised-staff'): ?>
                                <thead><tr><th>Bil</th><th>Nama</th><th>Staff No</th><th>Jabatan</th><th>Kampus</th><th>Status</th><th>Tindakan</th></tr></thead>
                                <tbody>
                                    <?php foreach ($rows as $row): ?>
                                        <?php $staffIcno = (string) ($row['ICNO'] ?? ''); ?>
                                        <tr>
                                            <td><?= Html::encode((string) $rowNumber++) ?></td>
                                            <td><?= Html::encode((string) ($row['staff_name'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['staff_no'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['department_label'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['campus_label'] ?? '-')) ?></td>
                                            <td><span class="status-badge"><?= Html::encode((string) ($row['Status'] ?? '-')) ?></span></td>
                                            <td>
                                                <a class="grid-action" href="<?= Html::encode(LegacyUrl::to('cuti/supervisor/set-leave') . '?id=' . rawurlencode($staffIcno)) ?>" target="_blank" rel="noopener"><i data-lucide="settings"></i><span>Yii2</span></a>
                                                <a class="grid-action" href="<?= Html::encode(LegacyUrl::to('cuti/supervisor/leave-list-sv') . '?id=' . rawurlencode($staffIcno)) ?>" target="_blank" rel="noopener"><i data-lucide="list"></i><span>Yii2</span></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            <?php elseif (in_array($page['slug'], ['gcr-list', 'gcr-list-checked'], true)): ?>
                                <thead><tr><th>Bil</th><th>Nama</th><th>Jabatan</th><th>Tahun</th><th>Mohon</th><th>Baki</th><th>GCR</th><th>CBTH</th><th>Status</th><th>Tindakan</th></tr></thead>
                                <tbody>
                                    <?php foreach ($rows as $row): ?>
                                        <?php $id = rawurlencode((string) ($row['id'] ?? '')); ?>
                                        <tr>
                                            <td><?= Html::encode((string) $rowNumber++) ?></td>
                                            <td><?= Html::encode((string) ($row['staff_name'] ?? '-')) ?><br><small><?= Html::encode((string) ($row['staff_no'] ?? '-')) ?></small></td>
                                            <td><?= Html::encode((string) ($row['department_label'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['gcr_year'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['mohon_dt'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['leave_balance'] ?? '0')) ?></td>
                                            <td><?= Html::encode((string) ($row['gcr_applied'] ?? '0')) ?></td>
                                            <td><?= Html::encode((string) ($row['cbth_applied'] ?? '0')) ?></td>
                                            <td><span class="status-badge <?= Html::encode($statusClass((string) ($row['status'] ?? ''))) ?>"><?= Html::encode((string) ($row['status'] ?? '-')) ?></span></td>
                                            <td>
                                                <?php if ($page['slug'] === 'gcr-list'): ?>
                                                    <a class="grid-action attendance-primary-action" href="<?= Html::encode(LegacyUrl::to('cuti/supervisor/sv-gcr-verify') . '?id=' . $id) ?>" target="_blank" rel="noopener"><i data-lucide="pencil"></i><span>Yii2</span></a>
                                                <?php else: ?>
                                                    <a class="grid-action grid-action-danger" href="<?= Html::encode(LegacyUrl::to('cuti/supervisor/reset-gcr') . '?id=' . $id) ?>" target="_blank" rel="noopener"><i data-lucide="rotate-ccw"></i><span>Yii2</span></a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            <?php elseif ($page['slug'] === 'leave-statement-report'): ?>
                                <thead><tr><th>Bil</th><th>Nama</th><th>Staff No</th><th>Jabatan</th><th>Bilangan</th><th>Hari</th><th>Julat</th><th>Penyata</th></tr></thead>
                                <tbody>
                                    <?php foreach ($rows as $row): ?>
                                        <?php $staffIcno = (string) ($row['ICNO'] ?? ''); ?>
                                        <tr>
                                            <td><?= Html::encode((string) $rowNumber++) ?></td>
                                            <td><?= Html::encode((string) ($row['staff_name'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['staff_no'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['department_label'] ?? '-')) ?></td>
                                            <td><span class="status-badge status-badge-warning"><?= Html::encode((string) ($row['total_records'] ?? '0')) ?></span></td>
                                            <td><?= Html::encode((string) ($row['total_days'] ?? '0')) ?></td>
                                            <td><?= Html::encode((string) ($row['first_leave'] ?? '-')) ?> - <?= Html::encode((string) ($row['last_leave'] ?? '-')) ?></td>
                                            <td><a class="grid-action" href="<?= Html::encode(LegacyUrl::to('cuti/supervisor/leave-statement') . '?id=' . rawurlencode($staffIcno) . '&year=' . $year) ?>" target="_blank" rel="noopener"><i data-lucide="file-text"></i><span>Yii2</span></a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            <?php else: ?>
                                <thead><tr><th>Bil</th><th>Nama</th><th>Jabatan</th><th>Jenis Cuti</th><th>Tarikh</th><th>Tempoh</th><th>Catatan</th><th>Status</th><th>Tindakan</th></tr></thead>
                                <tbody>
                                    <?php foreach ($rows as $row): ?>
                                        <?php $id = rawurlencode((string) ($row['id'] ?? '')); ?>
                                        <tr>
                                            <td><?= Html::encode((string) $rowNumber++) ?></td>
                                            <td><?= Html::encode((string) ($row['staff_name'] ?? '-')) ?><br><small><?= Html::encode((string) ($row['staff_no'] ?? '-')) ?></small></td>
                                            <td><?= Html::encode((string) ($row['department_label'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['jenis_cuti_label'] ?? '-')) ?><br><small><?= Html::encode((string) ($row['queue_label'] ?? '')) ?></small></td>
                                            <td><?= Html::encode((string) ($row['date_label'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['tempoh'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['remark'] ?? '-')) ?></td>
                                            <td><span class="status-badge <?= Html::encode($statusClass((string) ($row['status'] ?? ''))) ?>"><?= Html::encode((string) ($row['status_label'] ?? '-')) ?></span></td>
                                            <td>
                                                <?php if ($page['slug'] === 'sick-leave-list'): ?>
                                                    <a class="grid-action attendance-primary-action" href="<?= Html::encode(LegacyUrl::to('cuti/supervisor/leave-detail-sl') . '?id=' . $id) ?>" target="_blank" rel="noopener"><i data-lucide="pencil"></i><span>Yii2</span></a>
                                                <?php else: ?>
                                                    <a class="grid-action" href="<?= Html::encode(LegacyUrl::to('cuti/supervisor/leave-details') . '?id=' . $id) ?>" target="_blank" rel="noopener"><i data-lucide="eye"></i><span>Yii2</span></a>
                                                <?php endif; ?>
                                            </td>
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
