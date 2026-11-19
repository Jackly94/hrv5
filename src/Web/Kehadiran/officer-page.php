<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var array<string, mixed>|null $attendance
 * @var string $identityId
 * @var array{slug: string, title: string} $page
 * @var list<int> $years
 * @var int $year
 * @var int $month
 * @var string $date
 * @var string $legacyBase
 * @var array{q: string, status: string} $wfhFilters
 * @var array<string, mixed>|null $cardColorReport
 * @var array<string, mixed>|null $monthlySummary
 * @var array<string, mixed>|null $dailyAttendance
 * @var list<array<string, mixed>>|null $wfhApplications
 * @var list<array<string, mixed>>|null $pendingWfhApprovals
 * @var list<array<string, mixed>>|null $pendingReasonApprovals
 * @var array<string, mixed>|null $thtcDepartment
 */

$this->setTitle($page['title'] . ' - STARS - ' . $applicationParams->name);
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
$badgeClass = static fn (string $status): string => match (strtoupper($status)) {
    'APPROVED', 'VERIFIED', 'GREEN' => 'status-badge-success',
    'REJECTED', 'RED', 'NO' => 'status-badge-danger',
    'ENTRY', 'RETURNED', 'YELLOW' => 'status-badge-warning',
    default => '',
};
$staffName = static fn (array $staff): string => (string) ($staff['name'] ?? 'Tiada Rekod');
$staffMeta = static fn (array $staff): string => trim((string) ($staff['grade'] ?? '-') . ' / ' . (string) ($staff['departmentShort'] ?? '-'), ' /');
$statusBadges = static function (array $labels) use ($badgeClass): string {
    if ($labels === []) {
        return '-';
    }

    $html = '<div class="attendance-status-stack">';
    foreach ($labels as $label) {
        $html .= '<span class="status-badge ' . Html::encode($badgeClass((string) $label)) . '">' . Html::encode((string) $label) . '</span>';
    }

    return $html . '</div>';
};
$tagBadges = static function (array $labels): string {
    if ($labels === []) {
        return '-';
    }

    $html = '<div class="attendance-status-stack">';
    foreach ($labels as $label) {
        $html .= '<span class="status-badge">' . Html::encode((string) $label) . '</span>';
    }

    return $html . '</div>';
};
?>

<?php if ($attendance === null): ?>
    <main class="attendance-page">
        <section class="profile-card page-main-card">
            <div class="dashboard-band page-card-heading">
                <h1>STARS unavailable</h1>
                <p>The current legacy session <?= Html::encode($identityId) ?> is not a staff attendance session.</p>
            </div>
            <div class="page-section-body">
                <a class="button-link" href="/login">
                    <i data-lucide="log-in"></i>
                    <span>Back to login</span>
                </a>
            </div>
        </section>
    </main>
<?php else: ?>
    <?php
    /** @var list<array<string, mixed>> $menuSections */
    $menuSections = $attendance['menuSections'];
    ?>
    <main class="attendance-page">
        <section class="profile-card page-main-card attendance-main-card">
            <div class="dashboard-band dashboard-heading page-card-heading">
                <div>
                    <h1>STARS</h1>
                    <p><?= Html::encode($page['title']) ?></p>
                </div>
                <a class="button-link button-link-secondary" href="/kehadiran/index">
                    <i data-lucide="arrow-left"></i>
                    <span>Kembali</span>
                </a>
            </div>

            <?php require __DIR__ . '/_topMenu.php'; ?>

            <?php if ($page['slug'] === 'staff-color-card' && $cardColorReport !== null): ?>
                <?php /** @var list<array<string, mixed>> $rows */
                $rows = $cardColorReport['rows']; ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card attendance-standard-toolbar">
                        <div class="attendance-standard-title">
                            <span class="attendance-standard-icon attendance-icon-warning" aria-hidden="true"><i data-lucide="palette"></i></span>
                            <div>
                                <h2>Card Colour Report</h2>
                                <p><?= Html::encode((string) $cardColorReport['monthName']) ?> <?= Html::encode((string) $year) ?></p>
                            </div>
                        </div>
                        <form class="attendance-standard-filter" method="get" action="/kehadiran/staff-color-card">
                            <div class="attendance-standard-fields">
                                <label>Year
                                    <select name="tahun">
                                        <?php foreach ($years as $optionYear): ?><option value="<?= Html::encode((string) $optionYear) ?>"<?= $optionYear === $year ? ' selected' : '' ?>><?= Html::encode((string) $optionYear) ?></option><?php endforeach; ?>
                                    </select>
                                </label>
                                <label>Month
                                    <select name="bulan">
                                        <?php foreach ($months as $optionMonth => $monthName): ?><option value="<?= Html::encode((string) $optionMonth) ?>"<?= $optionMonth === $month ? ' selected' : '' ?>><?= Html::encode($monthName) ?></option><?php endforeach; ?>
                                    </select>
                                </label>
                            </div>
                            <div class="attendance-standard-actions">
                                <button class="button-link" type="submit"><i data-lucide="search"></i><span>Search</span></button>
                            </div>
                        </form>
                    </section>
                </div>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel attendance-compact-panel">
                        <div class="responsive-table">
                            <table class="data-table attendance-compact-table">
                                <thead><tr><th>Bil</th><th>Nama Kakitangan</th><th>Gred / JFPIB</th><th>Ketidakpatuhan</th><th>Diterima</th><th>Tidak Diterima</th><th>Warna Kad</th></tr></thead>
                                <tbody>
                                    <?php if ($rows === []): ?><tr><td colspan="7" class="staff-empty">Tiada kakitangan untuk dipantau.</td></tr><?php endif; ?>
                                    <?php foreach ($rows as $index => $row): ?>
                                        <?php /** @var array<string, mixed> $person */
                                        $person = $row['staff']; ?>
                                        <tr>
                                            <td><span class="attendance-row-number"><?= Html::encode((string) ($index + 1)) ?></span></td>
                                            <td><?= Html::encode($staffName($person)) ?></td>
                                            <td><?= Html::encode($staffMeta($person)) ?></td>
                                            <td><?= Html::encode((string) $row['nonCompliance']) ?></td>
                                            <td><?= Html::encode((string) $row['approved']) ?></td>
                                            <td><?= Html::encode((string) $row['disapproved']) ?></td>
                                            <td><span class="status-badge <?= Html::encode($badgeClass((string) $row['color'])) ?>"><?= Html::encode((string) $row['color']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            <?php endif; ?>

            <?php if ($page['slug'] === 'monthly-summary' && $monthlySummary !== null): ?>
                <?php /** @var list<array<string, mixed>> $rows */
                $rows = $monthlySummary['rows'];
                /** @var array<string, mixed> $totals */
                $totals = $monthlySummary['totals']; ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card attendance-standard-toolbar">
                        <div class="attendance-standard-title">
                            <span class="attendance-standard-icon attendance-icon-info" aria-hidden="true"><i data-lucide="area-chart"></i></span>
                            <div>
                                <h2>Monthly report</h2>
                                <p><?= Html::encode((string) $monthlySummary['monthName']) ?> <?= Html::encode((string) $year) ?></p>
                            </div>
                        </div>
                        <form class="attendance-standard-filter" method="get" action="/kehadiran/monthly-summary">
                            <div class="attendance-standard-fields">
                                <label>Year
                                    <select name="tahun">
                                        <?php foreach ($years as $optionYear): ?><option value="<?= Html::encode((string) $optionYear) ?>"<?= $optionYear === $year ? ' selected' : '' ?>><?= Html::encode((string) $optionYear) ?></option><?php endforeach; ?>
                                    </select>
                                </label>
                                <label>Month
                                    <select name="bulan">
                                        <?php foreach ($months as $optionMonth => $monthName): ?><option value="<?= Html::encode((string) $optionMonth) ?>"<?= $optionMonth === $month ? ' selected' : '' ?>><?= Html::encode($monthName) ?></option><?php endforeach; ?>
                                    </select>
                                </label>
                            </div>
                            <div class="attendance-standard-actions">
                                <button class="button-link" type="submit"><i data-lucide="search"></i><span>Search</span></button>
                            </div>
                        </form>
                    </section>
                </div>
                <div class="dashboard-grid dashboard-metrics page-section-body page-card-section" aria-label="Officer monthly totals">
                    <article class="metric-card"><span>Working Hours</span><strong><?= Html::encode((string) $totals['workingHours']) ?></strong></article>
                    <article class="metric-card"><span>Completed Days</span><strong><?= Html::encode((string) $totals['completedDays']) ?></strong></article>
                    <article class="metric-card"><span>Late In</span><strong><?= Html::encode((string) $totals['lateIn']) ?></strong></article>
                    <article class="metric-card"><span>Absent</span><strong><?= Html::encode((string) $totals['absent']) ?></strong></article>
                </div>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel attendance-compact-panel">
                        <div class="responsive-table">
                            <table class="data-table attendance-compact-table">
                                <thead><tr><th>Bil</th><th>Nama Kakitangan</th><th>Jam bekerja</th><th>Hari bekerja</th><th>Late In</th><th>Early Out</th><th>Incomplete</th><th>Absent</th><th>External</th></tr></thead>
                                <tbody>
                                    <?php if ($rows === []): ?><tr><td colspan="9" class="staff-empty">Tiada kakitangan untuk dipantau.</td></tr><?php endif; ?>
                                    <?php foreach ($rows as $index => $row): ?>
                                        <?php /** @var array<string, mixed> $person */
                                        $person = $row['staff']; ?>
                                        <tr>
                                            <td><span class="attendance-row-number"><?= Html::encode((string) ($index + 1)) ?></span></td>
                                            <td><?= Html::encode($staffName($person)) ?></td>
                                            <td><?= Html::encode((string) $row['workingHours']) ?></td>
                                            <td><?= Html::encode((string) $row['completedDays']) ?></td>
                                            <td><?= Html::encode((string) $row['lateIn']) ?></td>
                                            <td><?= Html::encode((string) $row['earlyOut']) ?></td>
                                            <td><?= Html::encode((string) $row['incomplete']) ?></td>
                                            <td><?= Html::encode((string) $row['absent']) ?></td>
                                            <td><?= Html::encode((string) $row['external']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            <?php endif; ?>

            <?php if ($page['slug'] === 'pantau-kehadiran' && $dailyAttendance !== null): ?>
                <?php /** @var list<array<string, mixed>> $rows */
                $rows = $dailyAttendance['rows']; ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card attendance-standard-toolbar">
                        <div class="attendance-standard-title">
                            <span class="attendance-standard-icon" aria-hidden="true"><i data-lucide="calendar-search"></i></span>
                            <div>
                                <h2>Attendance report</h2>
                                <p><?= Html::encode((string) $dailyAttendance['dateLabel']) ?></p>
                            </div>
                        </div>
                        <form class="attendance-standard-filter" method="get" action="/kehadiran/pantau-kehadiran">
                            <div class="attendance-standard-fields attendance-standard-fields-single">
                                <label>Date
                                    <input type="date" name="date" value="<?= Html::encode((string) $dailyAttendance['date']) ?>">
                                </label>
                            </div>
                            <div class="attendance-standard-actions">
                                <button class="button-link" type="submit"><i data-lucide="search"></i><span>Search</span></button>
                            </div>
                        </form>
                    </section>
                </div>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel attendance-compact-panel">
                        <div class="responsive-table">
                            <table class="data-table attendance-compact-table">
                                <thead><tr><th>Bil</th><th>Nama Kakitangan</th><th>WBB</th><th>Masa Masuk</th><th>Masa Keluar</th><th>Cuti/Outstation</th><th>Ketidakpatuhan</th><th>Lokasi</th><th>Perincian</th><th>Laporan</th><th>Kesalahan</th></tr></thead>
                                <tbody>
                                    <?php if ($rows === []): ?><tr><td colspan="11" class="staff-empty">Tiada kakitangan untuk dipantau.</td></tr><?php endif; ?>
                                    <?php foreach ($rows as $index => $row): ?>
                                        <?php /** @var array<string, mixed> $person */
                                        $person = $row['staff']; ?>
                                        <tr>
                                            <td><span class="attendance-row-number"><?= Html::encode((string) ($index + 1)) ?></span></td>
                                            <td><?= Html::encode($staffName($person)) ?><br><small><?= Html::encode($staffMeta($person)) ?></small></td>
                                            <td><?= Html::encode((string) $row['wbb']) ?></td>
                                            <td><?= Html::encode((string) $row['timeIn']) ?></td>
                                            <td><?= Html::encode((string) $row['timeOut']) ?></td>
                                            <td><?= $tagBadges($row['leaveOutstation']) ?></td>
                                            <td><?= $statusBadges($row['statusLabels']) ?></td>
                                            <td>
                                                <?php if ($row['locations'] === []): ?>-<?php else: ?>
                                                    <?php foreach ($row['locations'] as $label => $url): ?><a class="grid-action" href="<?= Html::encode((string) $url) ?>" target="_blank" rel="noopener"><i data-lucide="map-pin"></i><span><?= Html::encode((string) $label) ?></span></a><?php endforeach; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $row['detailUrl'] === '' ? '-' : '<a class="grid-action" href="' . Html::encode((string) $row['detailUrl']) . '" target="_blank" rel="noopener"><i data-lucide="eye"></i><span>Yii2</span></a>' ?></td>
                                            <td><a class="grid-action" href="<?= Html::encode((string) $row['reportUrl']) ?>" target="_blank" rel="noopener"><i data-lucide="bar-chart-3"></i><span>Yii2</span></a></td>
                                            <td><a class="grid-action" href="<?= Html::encode((string) $row['historyUrl']) ?>" target="_blank" rel="noopener"><i data-lucide="list"></i><span>Yii2</span></a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            <?php endif; ?>

            <?php if ($page['slug'] === 'pantau-permohonan-wfh-pegawai' && $wfhApplications !== null): ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card attendance-standard-toolbar">
                        <div class="attendance-standard-title">
                            <span class="attendance-standard-icon attendance-icon-success" aria-hidden="true"><i data-lucide="home"></i></span>
                            <div>
                                <h2>Senarai Permohonan WFH</h2>
                                <p><?= Html::encode((string) count($wfhApplications)) ?> records</p>
                            </div>
                        </div>
                        <form class="attendance-standard-filter" method="get" action="/kehadiran/pantau-permohonan-wfh-pegawai">
                            <div class="attendance-standard-fields attendance-standard-fields-wide">
                                <label>Search
                                    <input type="text" name="q" value="<?= Html::encode($wfhFilters['q']) ?>" placeholder="Name, remark, date">
                                </label>
                                <label>Status
                                    <select name="status">
                                        <?php foreach (['' => 'All', 'ENTRY' => 'ENTRY', 'APPROVED' => 'APPROVED', 'REJECTED' => 'REJECTED', 'VERIFIED' => 'VERIFIED'] as $option => $label): ?>
                                            <option value="<?= Html::encode($option) ?>"<?= $option === $wfhFilters['status'] ? ' selected' : '' ?>><?= Html::encode($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            </div>
                            <div class="attendance-standard-actions">
                                <button class="button-link" type="submit"><i data-lucide="search"></i><span>Search</span></button>
                            </div>
                        </form>
                    </section>
                </div>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel attendance-compact-panel">
                        <div class="responsive-table">
                            <table class="data-table attendance-compact-table">
                                <thead><tr><th>Bil</th><th>Tarikh WFH</th><th>Nama</th><th>JFPIB</th><th>Catatan</th><th>Status</th><th>Tarikh Mohon</th><th>Tarikh Kelulusan</th><th>Buang</th></tr></thead>
                                <tbody>
                                    <?php if ($wfhApplications === []): ?><tr><td colspan="9" class="staff-empty">Tiada rekod untuk dipaparkan.</td></tr><?php endif; ?>
                                    <?php foreach ($wfhApplications as $index => $row): ?>
                                        <tr>
                                            <td><span class="attendance-row-number"><?= Html::encode((string) ($index + 1)) ?></span></td>
                                            <td><?= Html::encode((string) $row['date']) ?></td>
                                            <td><?= Html::encode((string) $row['staffName']) ?></td>
                                            <td><?= Html::encode((string) $row['department']) ?></td>
                                            <td><?= Html::encode((string) $row['remark']) ?></td>
                                            <td><span class="status-badge <?= Html::encode($badgeClass((string) $row['status'])) ?>"><?= Html::encode((string) $row['status']) ?></span></td>
                                            <td><?= Html::encode((string) $row['entryDate']) ?></td>
                                            <td><?= Html::encode((string) $row['approvalDate']) ?></td>
                                            <td><a class="grid-action" href="<?= Html::encode((string) $row['deleteUrl']) ?>" target="_blank" rel="noopener"><i data-lucide="trash-2"></i><span>Yii2</span></a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            <?php endif; ?>

            <?php if ($page['slug'] === 'wfh-lulus' && $pendingWfhApprovals !== null): ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card attendance-standard-toolbar">
                        <div class="attendance-standard-title">
                            <span class="attendance-standard-icon attendance-icon-success" aria-hidden="true"><i data-lucide="users"></i></span>
                            <div>
                                <h2>Senarai Tindakan WFH</h2>
                                <p>Pending approval queue</p>
                            </div>
                        </div>
                        <div class="attendance-standard-count">
                            <span>Open items</span>
                            <strong><?= Html::encode((string) count($pendingWfhApprovals)) ?></strong>
                        </div>
                    </section>
                </div>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel attendance-compact-panel">
                        <div class="responsive-table">
                            <table class="data-table attendance-compact-table">
                                <thead><tr><th>Bil</th><th>Nama</th><th>Gred</th><th>JFPIB</th><th>Tarikh</th><th>Tempoh</th><th>Catatan</th><th>Tarikh Mohon</th><th>Status</th><th>Tindakan</th></tr></thead>
                                <tbody>
                                    <?php if ($pendingWfhApprovals === []): ?><tr><td colspan="10" class="staff-empty">Belum ada tindakan lagi.</td></tr><?php endif; ?>
                                    <?php foreach ($pendingWfhApprovals as $index => $row): ?>
                                        <tr>
                                            <td><span class="attendance-row-number"><?= Html::encode((string) ($index + 1)) ?></span></td>
                                            <td><?= Html::encode((string) $row['staffName']) ?></td>
                                            <td><?= Html::encode((string) $row['grade']) ?></td>
                                            <td><?= Html::encode((string) $row['department']) ?></td>
                                            <td><?= Html::encode((string) $row['date']) ?></td>
                                            <td><?= Html::encode((string) $row['period']) ?></td>
                                            <td><?= Html::encode((string) $row['remark']) ?></td>
                                            <td><?= Html::encode((string) $row['entryDate']) ?></td>
                                            <td><span class="status-badge <?= Html::encode($badgeClass((string) $row['status'])) ?>"><?= Html::encode((string) $row['status']) ?></span></td>
                                            <td><a class="grid-action attendance-primary-action" href="<?= Html::encode((string) $row['actionUrl']) ?>" target="_blank" rel="noopener"><i data-lucide="pencil"></i><span>Yii2</span></a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            <?php endif; ?>

            <?php if ($page['slug'] === 'senarai_tindakan' && $pendingReasonApprovals !== null): ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card attendance-standard-toolbar">
                        <div class="attendance-standard-title">
                            <span class="attendance-standard-icon attendance-icon-danger" aria-hidden="true"><i data-lucide="alert-triangle"></i></span>
                            <div>
                                <h2>Approval (Reason)</h2>
                                <p>Pending attendance reason queue</p>
                            </div>
                        </div>
                        <div class="attendance-standard-count">
                            <span>Open items</span>
                            <strong><?= Html::encode((string) count($pendingReasonApprovals)) ?></strong>
                        </div>
                    </section>
                </div>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel attendance-compact-panel">
                        <div class="responsive-table">
                            <table class="data-table attendance-compact-table">
                                <thead><tr><th>Bil</th><th>Nama Kakitangan</th><th>Tarikh</th><th>Masa Masuk</th><th>Masa Keluar</th><th>Status</th><th>Cuti/Keluar Pejabat</th><th>Alasan/Sebab</th><th>Pengesahan</th></tr></thead>
                                <tbody>
                                    <?php if ($pendingReasonApprovals === []): ?><tr><td colspan="9" class="staff-empty">Belum ada tindakan lagi.</td></tr><?php endif; ?>
                                    <?php foreach ($pendingReasonApprovals as $index => $row): ?>
                                        <tr>
                                            <td><span class="attendance-row-number"><?= Html::encode((string) ($index + 1)) ?></span></td>
                                            <td><?= Html::encode((string) $row['staffName']) ?></td>
                                            <td><?= Html::encode((string) $row['date']) ?></td>
                                            <td><?= Html::encode((string) $row['timeIn']) ?></td>
                                            <td><?= Html::encode((string) $row['timeOut']) ?></td>
                                            <td><?= $statusBadges($row['statusLabels']) ?></td>
                                            <td><?= $tagBadges($row['leaveOutstation']) ?></td>
                                            <td><?= Html::encode((string) $row['remark']) ?></td>
                                            <td><a class="grid-action attendance-primary-action" href="<?= Html::encode((string) $row['actionUrl']) ?>" target="_blank" rel="noopener"><i data-lucide="pencil"></i><span>Yii2</span></a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            <?php endif; ?>

            <?php if ($page['slug'] === 'absent-list-kp' && $thtcDepartment !== null): ?>
                <?php if (!$thtcDepartment['authorized']): ?>
                    <div class="page-section-body page-card-section">
                        <section class="content-inner-card attendance-standard-toolbar">
                            <div class="attendance-standard-title">
                                <span class="attendance-standard-icon attendance-icon-danger" aria-hidden="true"><i data-lucide="shield-alert"></i></span>
                                <div>
                                    <h2>Tidak Hadir Tanpa Cuti (THTC)</h2>
                                    <p>Access is limited to the department PP.</p>
                                </div>
                            </div>
                        </section>
                    </div>
                <?php else: ?>
                    <?php /** @var array<string, mixed> $department */
                    $department = $thtcDepartment['department'];
                    /** @var list<array<string, mixed>> $rows */
                    $rows = $thtcDepartment['rows']; ?>
                    <div class="page-section-body page-card-section">
                        <section class="content-inner-card attendance-standard-toolbar">
                            <div class="attendance-standard-title">
                                <span class="attendance-standard-icon attendance-icon-danger" aria-hidden="true"><i data-lucide="calendar-x"></i></span>
                                <div>
                                    <h2>Tidak Hadir Tanpa Cuti (THTC)</h2>
                                    <p><?= Html::encode((string) $department['shortName']) ?> &middot; <?= Html::encode((string) count($rows)) ?> records</p>
                                </div>
                            </div>
                            <div class="attendance-standard-count">
                                <span>Open list</span>
                                <strong><?= Html::encode((string) count($rows)) ?></strong>
                            </div>
                        </section>
                    </div>
                    <div class="page-section-body page-card-section">
                        <section class="content-inner-card staff-page-panel attendance-compact-panel">
                            <div class="responsive-table">
                                <table class="data-table attendance-compact-table">
                                    <thead><tr><th>Bil</th><th>Tarikh Absent</th><th>Day</th><th>Nama</th><th>Remark Absent</th><th>Remark Permohonan THTC</th><th>Bercuti</th><th>Dokumen</th><th>Status THTC</th><th>Tarikh Mohon</th></tr></thead>
                                    <tbody>
                                        <?php if ($rows === []): ?><tr><td colspan="10" class="staff-empty">Tiada rekod untuk dipaparkan.</td></tr><?php endif; ?>
                                        <?php foreach ($rows as $index => $row): ?>
                                            <tr>
                                                <td><span class="attendance-row-number"><?= Html::encode((string) ($index + 1)) ?></span></td>
                                                <td><?= Html::encode((string) $row['date']) ?></td>
                                                <td><?= Html::encode((string) $row['day']) ?></td>
                                                <td><?= Html::encode((string) $row['staffName']) ?></td>
                                                <td><?= Html::encode((string) $row['absentRemark']) ?></td>
                                                <td><?= Html::encode((string) $row['remark']) ?></td>
                                                <td><span class="status-badge status-badge-danger"><?= Html::encode((string) $row['hasLeave']) ?></span></td>
                                                <td><?= $row['documentUrl'] === '' ? '-' : '<a class="grid-action" href="' . Html::encode((string) $row['documentUrl']) . '" target="_blank" rel="noopener"><i data-lucide="download"></i><span>File</span></a>' ?></td>
                                                <td><?= $row['status'] === '' ? '-' : '<span class="status-badge ' . Html::encode($badgeClass((string) $row['status'])) . '">' . Html::encode((string) $row['status']) . '</span>' ?></td>
                                                <td><?= Html::encode((string) $row['entryDate']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>
<?php endif; ?>
