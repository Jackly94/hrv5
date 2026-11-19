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
 * @var string|null $date
 * @var string $legacyBase
 * @var array<string, mixed>|null $report
 * @var list<array<string, mixed>>|null $pendingReasons
 * @var array<string, mixed>|null $cardColorList
 * @var list<array<string, mixed>>|null $wfhRows
 * @var list<array<string, mixed>>|null $absentRows
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
    'APPROVED', 'CEMERLANG' => 'status-badge-success',
    'REJECTED', 'RED', 'NO REMARK / NOT APPROVED' => 'status-badge-danger',
    'RETURNED', 'YELLOW' => 'status-badge-warning',
    default => '',
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

            <?php if ($page['slug'] === 'laporan-kehadiran' && $report !== null): ?>
                <?php
                /** @var array<string, mixed> $profile */
                $profile = $report['profile'];
                /** @var array<string, mixed> $summary */
                $summary = $report['summary'];
                /** @var array<string, mixed> $cardColor */
                $cardColor = $report['cardColor'];
                /** @var list<array<string, mixed>> $rows */
                $rows = $report['rows'];
                $cardColour = strtolower((string) $cardColor['color']);
                $cardClass = match ($cardColour) {
                    'green' => 'attendance-card-green',
                    'yellow' => 'attendance-card-yellow',
                    'red' => 'attendance-card-red',
                    default => 'attendance-card-neutral',
                };
                ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card attendance-standard-toolbar">
                        <div class="attendance-standard-title">
                            <span class="attendance-standard-icon" aria-hidden="true"><i data-lucide="calendar-search"></i></span>
                            <div>
                                <h2>Attendance Report</h2>
                                <p><?= Html::encode((string) $report['monthName']) ?> <?= Html::encode((string) $year) ?></p>
                            </div>
                        </div>
                        <form class="attendance-standard-filter" method="get" action="/kehadiran/laporan-kehadiran">
                            <div class="attendance-standard-fields">
                                <label>Year
                                    <select name="tahun">
                                        <?php foreach ($years as $optionYear): ?>
                                            <option value="<?= Html::encode((string) $optionYear) ?>"<?= $optionYear === $year ? ' selected' : '' ?>><?= Html::encode((string) $optionYear) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>Month
                                    <select name="bulan">
                                        <?php foreach ($months as $optionMonth => $monthName): ?>
                                            <option value="<?= Html::encode((string) $optionMonth) ?>"<?= $optionMonth === $month ? ' selected' : '' ?>><?= Html::encode($monthName) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            </div>
                            <div class="attendance-standard-actions">
                                <button class="button-link" type="submit"><i data-lucide="search"></i><span>Search</span></button>
                                <a class="button-link button-link-secondary" href="<?= Html::encode($legacyBase . '/kehadiran/report?tahun=' . $year . '&bulan=' . sprintf('%02d', $month)) ?>" target="_blank" rel="noopener"><i data-lucide="printer"></i><span>Print</span></a>
                            </div>
                        </form>
                    </section>
                </div>

                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel staff-detail-panel">
                        <div class="staff-detail-hero">
                            <div class="staff-detail-person">
                                <span class="staff-detail-icon" aria-hidden="true"><i data-lucide="user-round"></i></span>
                                <div>
                                    <h3><?= Html::encode((string) $profile['name']) ?></h3>
                                    <p><?= Html::encode((string) $profile['staffNo']) ?> &middot; <?= Html::encode((string) $profile['campus']) ?></p>
                                </div>
                            </div>
                            <div class="staff-card-status">
                                <span class="staff-card-dot <?= Html::encode($cardClass) ?>" aria-hidden="true"></span>
                                <div>
                                    <span>Card Colour Status</span>
                                    <strong><?= Html::encode($cardColour) ?></strong>
                                </div>
                            </div>
                        </div>

                        <dl class="staff-detail-grid">
                            <div>
                                <dt><i data-lucide="briefcase-business"></i><span>Position</span></dt>
                                <dd><?= Html::encode((string) $profile['position']) ?></dd>
                            </div>
                            <div>
                                <dt><i data-lucide="building-2"></i><span>J / F / P / I / U</span></dt>
                                <dd><?= Html::encode((string) $profile['department']) ?></dd>
                            </div>
                            <div>
                                <dt><i data-lucide="calendar-days"></i><span>Period</span></dt>
                                <dd><?= Html::encode((string) $report['monthName']) ?> <?= Html::encode((string) $year) ?></dd>
                            </div>
                            <div>
                                <dt><i data-lucide="badge-check"></i><span>Card Record</span></dt>
                                <dd><?= Html::encode((string) $cardColor['nonCompliance']) ?> non-compliance &middot; <?= Html::encode((string) $cardColor['approved']) ?> approved</dd>
                            </div>
                        </dl>
                    </section>
                </div>

                <div class="dashboard-grid dashboard-metrics page-section-body page-card-section" aria-label="Monthly summary">
                    <?php foreach ([
                        'Working day(Completed)' => $summary['completedDays'],
                        'Working Hours' => $summary['workingHours'],
                        'Late In' => $summary['lateIn'],
                        'Early Out' => $summary['earlyOut'],
                        'Incomplete' => $summary['incomplete'],
                        'Absent' => $summary['absent'],
                        'External' => $summary['external'],
                    ] as $label => $value): ?>
                        <article class="metric-card"><span><?= Html::encode($label) ?></span><strong><?= Html::encode((string) $value) ?></strong></article>
                    <?php endforeach; ?>
                </div>

                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel">
                        <div class="responsive-table">
                            <table class="data-table staff-attendance-table">
                                <thead>
                                    <tr>
                                        <th>Date</th><th>Day</th><th>WBB</th><th>In</th><th>Out</th><th>Non-compliance status</th><th>Leave/Outstation</th><th>Working Hours</th><th>Remark?</th><th>Location</th><th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td><?= Html::encode((string) $row['dateLabel']) ?></td>
                                            <td><?= Html::encode((string) $row['day']) ?></td>
                                            <td><?= Html::encode((string) $row['wbb']) ?></td>
                                            <td><?= Html::encode((string) $row['timeIn']) ?></td>
                                            <td><?= Html::encode((string) $row['timeOut']) ?></td>
                                            <td>
                                                <?php if ($row['statusLabels'] === []): ?>-<?php else: ?>
                                                    <?php foreach ($row['statusLabels'] as $label): ?><span class="status-badge status-badge-danger"><?= Html::encode((string) $label) ?></span><?php endforeach; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($row['leaveOutstation'] === []): ?>-<?php else: ?>
                                                    <?php foreach ($row['leaveOutstation'] as $label): ?><span class="status-badge"><?= Html::encode((string) $label) ?></span><?php endforeach; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= Html::encode((string) $row['workingHours']) ?></td>
                                            <td><span class="status-badge <?= Html::encode($badgeClass((string) $row['remark'])) ?>"><?= Html::encode((string) $row['remark']) ?></span></td>
                                            <td>
                                                <?php if ($row['locations'] === []): ?>-<?php else: ?>
                                                    <?php foreach ($row['locations'] as $label => $url): ?><a class="grid-action" href="<?= Html::encode((string) $url) ?>" target="_blank" rel="noopener"><i data-lucide="map-pin"></i><span><?= Html::encode((string) $label) ?></span></a><?php endforeach; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $row['recordId'] === null ? '-' : '<a class="grid-action" href="' . Html::encode($legacyBase . '/kehadiran/detailrekod?icno=' . $attendance['icno'] . '&tarikh=' . $row['date']) . '" target="_blank" rel="noopener"><i data-lucide="eye"></i><span>Yii2</span></a>' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            <?php endif; ?>

            <?php if ($page['slug'] === 'tindakan_ketidakpatuhan' && $pendingReasons !== null): ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card attendance-standard-toolbar">
                        <div class="attendance-standard-title">
                            <span class="attendance-standard-icon attendance-icon-danger" aria-hidden="true"><i data-lucide="alert-triangle"></i></span>
                            <div>
                                <h2>Pending for reason</h2>
                                <p><?= Html::encode((string) $attendance['staffName']) ?></p>
                            </div>
                        </div>
                        <div class="attendance-standard-count">
                            <span>Open items</span>
                            <strong><?= Html::encode((string) count($pendingReasons)) ?></strong>
                        </div>
                    </section>
                </div>

                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel attendance-compact-panel">
                        <div class="responsive-table">
                            <table class="data-table attendance-compact-table">
                                <thead><tr><th>Bil</th><th>Tarikh</th><th>Masa Masuk</th><th>Masa Keluar</th><th>Status Ketidakpatuhan</th><th>Alasan/Catatan</th></tr></thead>
                                <tbody>
                                    <?php if ($pendingReasons === []): ?>
                                        <tr><td colspan="6" class="staff-empty">Tiada rekod untuk dipaparkan.</td></tr>
                                    <?php endif; ?>
                                    <?php foreach ($pendingReasons as $index => $row): ?>
                                        <tr>
                                            <td><span class="attendance-row-number"><?= Html::encode((string) ($index + 1)) ?></span></td>
                                            <td><?= Html::encode((string) $row['date']) ?></td>
                                            <td><?= Html::encode((string) $row['timeIn']) ?></td>
                                            <td><?= Html::encode((string) $row['timeOut']) ?></td>
                                            <td>
                                                <div class="attendance-status-stack">
                                                    <?php foreach ($row['statusLabels'] as $label): ?><span class="status-badge status-badge-danger"><?= Html::encode((string) $label) ?></span><?php endforeach; ?>
                                                </div>
                                            </td>
                                            <td><a class="grid-action attendance-primary-action" href="<?= Html::encode((string) $row['remarkUrl']) ?>" target="_blank" rel="noopener"><i data-lucide="pencil"></i><span>Remark</span></a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            <?php endif; ?>

            <?php if ($page['slug'] === 'staf-color-list' && $cardColorList !== null): ?>
                <?php /** @var array<string, int> $totals */
                $totals = $cardColorList['totals']; ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card attendance-standard-toolbar">
                        <div class="attendance-standard-title">
                            <span class="attendance-standard-icon attendance-icon-warning" aria-hidden="true"><i data-lucide="palette"></i></span>
                            <div>
                                <h2>Card Color report</h2>
                                <p><?= Html::encode((string) $year) ?> &middot; <?= Html::encode((string) $cardColorList['performance']) ?></p>
                            </div>
                        </div>
                        <form class="attendance-standard-filter" method="get" action="/kehadiran/staf-color-list">
                            <div class="attendance-standard-fields attendance-standard-fields-single">
                                <label>Year
                                    <select name="year">
                                        <?php foreach ($years as $optionYear): ?><option value="<?= Html::encode((string) $optionYear) ?>"<?= $optionYear === $year ? ' selected' : '' ?>><?= Html::encode((string) $optionYear) ?></option><?php endforeach; ?>
                                    </select>
                                </label>
                            </div>
                            <div class="attendance-standard-actions">
                                <button class="button-link" type="submit"><i data-lucide="search"></i><span>Search</span></button>
                            </div>
                        </form>
                    </section>
                </div>
                <div class="dashboard-grid dashboard-metrics page-section-body page-card-section" aria-label="Card color summary">
                    <article class="metric-card"><span>Yellow</span><strong><?= Html::encode((string) $totals['YELLOW']) ?></strong></article>
                    <article class="metric-card"><span>Green</span><strong><?= Html::encode((string) $totals['GREEN']) ?></strong></article>
                    <article class="metric-card"><span>Red</span><strong><?= Html::encode((string) $totals['RED']) ?></strong></article>
                    <article class="metric-card"><span>Performance Indicator</span><strong><?= Html::encode((string) $cardColorList['performance']) ?></strong></article>
                </div>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel">
                        <div class="responsive-table">
                            <table class="data-table">
                                <thead><tr><th>Month</th><th>Ketidakpatuhan</th><th>Approved</th><th>Disapproved</th><th>Color</th></tr></thead>
                                <tbody>
                                    <?php foreach ($cardColorList['rows'] as $row): ?>
                                        <tr><td><?= Html::encode((string) $row['month']) ?></td><td><?= Html::encode((string) $row['ketidakpatuhan']) ?></td><td><?= Html::encode((string) $row['approved']) ?></td><td><?= Html::encode((string) $row['disapproved']) ?></td><td><span class="status-badge <?= Html::encode($badgeClass((string) $row['color'])) ?>"><?= Html::encode((string) $row['color']) ?></span></td></tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            <?php endif; ?>

            <?php if (in_array($page['slug'], ['kad-warna', 'kad_warna'], true)): ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card attendance-standard-toolbar">
                        <div class="attendance-standard-title">
                            <span class="attendance-standard-icon attendance-icon-info" aria-hidden="true"><i data-lucide="info"></i></span>
                            <div>
                                <h2>Card Colour info</h2>
                                <p>Reference guide</p>
                            </div>
                        </div>
                        <div class="attendance-standard-count">
                            <span>Format</span>
                            <strong>PNG</strong>
                        </div>
                    </section>
                </div>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel staff-card-info">
                        <img src="/images/kad_warna.png" alt="Card colour information">
                    </section>
                </div>
            <?php endif; ?>

            <?php if ($page['slug'] === 'wfh-list' && $wfhRows !== null): ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card attendance-standard-toolbar">
                        <div class="attendance-standard-title">
                            <span class="attendance-standard-icon attendance-icon-success" aria-hidden="true"><i data-lucide="home"></i></span>
                            <div>
                                <h2>Senarai WFH / WFO</h2>
                                <p>Bekerja dari Rumah / Work from Home</p>
                            </div>
                        </div>
                        <div class="attendance-standard-count">
                            <span>Total records</span>
                            <strong><?= Html::encode((string) count($wfhRows)) ?></strong>
                        </div>
                    </section>
                </div>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel">
                        <div class="responsive-table">
                            <table class="data-table">
                                <thead><tr><th>Bil</th><th>Tarikh</th><th>Tempoh</th><th>Catatan</th><th>Tarikh / Masa Permohonan</th><th>Status</th></tr></thead>
                                <tbody>
                                    <?php foreach ($wfhRows as $index => $row): ?>
                                        <tr><td><?= Html::encode((string) ($index + 1)) ?></td><td><?= Html::encode((string) $row['date']) ?></td><td><?= Html::encode((string) $row['period']) ?></td><td><?= Html::encode((string) $row['remark']) ?></td><td><?= Html::encode((string) $row['entryDate']) ?></td><td><span class="status-badge <?= Html::encode($badgeClass((string) $row['status'])) ?>"><?= Html::encode((string) $row['status']) ?></span></td></tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            <?php endif; ?>

            <?php if ($page['slug'] === 'absent-list' && $absentRows !== null): ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card attendance-standard-toolbar">
                        <div class="attendance-standard-title">
                            <span class="attendance-standard-icon attendance-icon-danger" aria-hidden="true"><i data-lucide="calendar-x"></i></span>
                            <div>
                                <h2>Tidak Hadir Tanpa Cuti (THTC)</h2>
                                <p><?= Html::encode((string) $year) ?> &middot; <?= Html::encode((string) count($absentRows)) ?> records</p>
                            </div>
                        </div>
                        <form class="attendance-standard-filter" method="get" action="/kehadiran/absent-list">
                            <div class="attendance-standard-fields">
                                <label>Year
                                    <select name="tahun">
                                        <?php foreach ($years as $optionYear): ?><option value="<?= Html::encode((string) $optionYear) ?>"<?= $optionYear === $year ? ' selected' : '' ?>><?= Html::encode((string) $optionYear) ?></option><?php endforeach; ?>
                                    </select>
                                </label>
                                <label>Tarikh
                                    <input type="date" name="tarikh" value="<?= Html::encode((string) ($date ?? '')) ?>">
                                </label>
                            </div>
                            <div class="attendance-standard-actions">
                                <button class="button-link" type="submit"><i data-lucide="search"></i><span>Search</span></button>
                            </div>
                        </form>
                    </section>
                </div>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel">
                        <div class="responsive-table">
                            <table class="data-table staff-absent-table">
                                <thead><tr><th>Tarikh</th><th>Day</th><th>Remark Status</th><th>Catatan</th><th>Catatan Pada</th><th>Approver Remark</th><th>Bercuti?</th><th>Catatan Penyelia JAFPIB</th><th>Dokumen Sokongan</th><th>Status THTC</th><th>Penyelia</th><th>Penyelia Peraku</th><th>Tindakan</th></tr></thead>
                                <tbody>
                                    <?php foreach ($absentRows as $row): ?>
                                        <tr>
                                            <td><?= Html::encode((string) $row['date']) ?></td>
                                            <td><?= Html::encode((string) $row['day']) ?></td>
                                            <td><span class="status-badge <?= Html::encode($badgeClass((string) $row['remarkStatus'])) ?>"><?= Html::encode((string) $row['remarkStatus']) ?></span></td>
                                            <td><?= Html::encode((string) $row['remark']) ?></td>
                                            <td><?= Html::encode((string) $row['remarkDate']) ?></td>
                                            <td><?= Html::encode((string) $row['approverRemark']) ?></td>
                                            <td><span class="status-badge status-badge-danger">No</span></td>
                                            <td><?= Html::encode((string) $row['verifierRemark']) ?></td>
                                            <td><?= $row['documentUrl'] === '' ? '-' : '<a class="grid-action" href="' . Html::encode((string) $row['documentUrl']) . '" target="_blank" rel="noopener"><i data-lucide="download"></i><span>File</span></a>' ?></td>
                                            <td><?= $row['thtcStatus'] === '' ? '-' : '<span class="status-badge ' . Html::encode($badgeClass((string) $row['thtcStatus'])) . '">' . Html::encode((string) $row['thtcStatus']) . '</span>' ?></td>
                                            <td><?= Html::encode((string) $row['verifier']) ?></td>
                                            <td><?= Html::encode((string) $row['approver']) ?></td>
                                            <td><?= $row['actionUrl'] === '' ? '-' : '<a class="grid-action" href="' . Html::encode((string) $row['actionUrl']) . '" target="_blank" rel="noopener"><i data-lucide="pencil"></i><span>Yii2</span></a>' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            <?php endif; ?>
        </section>
    </main>
<?php endif; ?>
