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
 * @var array<string, mixed>|null $attendance
 * @var string $identityId
 * @var array{slug: string, title: string} $page
 * @var list<int> $years
 * @var int $year
 * @var int $month
 * @var string $date
 * @var array<string, mixed> $scope
 * @var array<string, mixed>|null $dailyAttendance
 * @var array<string, mixed>|null $wfhSchedule
 * @var array<string, mixed>|null $thtcList
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
    'APPROVED', 'VERIFIED', 'GREEN', 'WFH' => 'status-badge-success',
    'REJECTED', 'RED', 'NO' => 'status-badge-danger',
    'ENTRY', 'RETURNED', 'YELLOW' => 'status-badge-warning',
    default => '',
};
$staffName = static fn (array $staff): string => (string) ($staff['name'] ?? $staff['staffName'] ?? 'Tiada Rekod');
$staffMeta = static fn (array $staff): string => trim((string) ($staff['grade'] ?? '-') . ' / ' . (string) ($staff['departmentShort'] ?? $staff['department'] ?? '-'), ' /');
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
$monthYearFields = static function () use ($years, $year, $month, $months): string {
    $html = '<label>Year<select name="tahun">';
    foreach ($years as $optionYear) {
        $html .= '<option value="' . Html::encode((string) $optionYear) . '"' . ($optionYear === $year ? ' selected' : '') . '>' . Html::encode((string) $optionYear) . '</option>';
    }
    $html .= '</select></label><label>Month<select name="bulan">';
    foreach ($months as $optionMonth => $monthName) {
        $html .= '<option value="' . Html::encode((string) $optionMonth) . '"' . ($optionMonth === $month ? ' selected' : '') . '>' . Html::encode($monthName) . '</option>';
    }

    return $html . '</select></label>';
};
$toolbar = static function (string $action, string $icon, string $iconClass, string $title, string $subtitle, string $fields = '', ?string $countLabel = null, ?int $count = null): void {
    ?>
    <div class="page-section-body page-card-section">
        <section class="content-inner-card attendance-standard-toolbar">
            <div class="attendance-standard-title">
                <span class="attendance-standard-icon <?= Html::encode($iconClass) ?>" aria-hidden="true"><i data-lucide="<?= Html::encode($icon) ?>"></i></span>
                <div>
                    <h2><?= Html::encode($title) ?></h2>
                    <?php if ($subtitle !== ''): ?><p><?= Html::encode($subtitle) ?></p><?php endif; ?>
                </div>
            </div>
            <?php if ($fields !== ''): ?>
                <form class="attendance-standard-filter" method="get" action="<?= Html::encode($action) ?>">
                    <div class="attendance-standard-fields attendance-standard-fields-wide"><?= $fields ?></div>
                    <div class="attendance-standard-actions">
                        <button class="button-link" type="submit"><i data-lucide="search"></i><span>Search</span></button>
                    </div>
                </form>
            <?php elseif ($countLabel !== null && $count !== null): ?>
                <div class="attendance-standard-count"><span><?= Html::encode($countLabel) ?></span><strong><?= Html::encode((string) $count) ?></strong></div>
            <?php endif; ?>
        </section>
    </div>
    <?php
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
                <a class="button-link" href="/login"><i data-lucide="log-in"></i><span>Back to login</span></a>
            </div>
        </section>
    </main>
<?php else: ?>
    <?php
    /** @var list<array<string, mixed>> $menuSections */
    $menuSections = $attendance['menuSections'];
    $department = is_array($scope['department'] ?? null) ? $scope['department'] : null;
    $departmentName = $department === null ? 'Department chief access required' : (string) ($department['shortName'] ?? $department['name'] ?? '');
    ?>
    <main class="attendance-page">
        <section class="profile-card page-main-card attendance-main-card">
            <div class="dashboard-band dashboard-heading page-card-heading">
                <div>
                    <h1>STARS</h1>
                    <p><?= Html::encode($page['title']) ?></p>
                </div>
                <a class="button-link button-link-secondary" href="/kehadiran/index"><i data-lucide="arrow-left"></i><span>Kembali</span></a>
            </div>

            <?php require __DIR__ . '/_topMenu.php'; ?>

            <?php if (!(bool) ($scope['authorized'] ?? false)): ?>
                <?php $toolbar('', 'shield-alert', 'attendance-icon-danger', 'Head of Department', 'Department chief setup is not available for this account.', '', 'Records', 0); ?>
            <?php endif; ?>

            <?php if ($page['slug'] === 'kj-pantau-kehadiran' && $dailyAttendance !== null): ?>
                <?php /** @var list<array<string, mixed>> $rows */
                $rows = $dailyAttendance['rows']; ?>
                <?php $toolbar('/kehadiran/kj-pantau-kehadiran', 'calendar-search', '', 'Staff attendance', (string) $dailyAttendance['dateLabel'] . ' - ' . $departmentName, '<label>Date<input type="date" name="date" value="' . Html::encode((string) $dailyAttendance['date']) . '"></label>'); ?>
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

            <?php if ($page['slug'] === 'wfh-jadual-kj' && $wfhSchedule !== null): ?>
                <?php $toolbar('/kehadiran/wfh-jadual-kj', 'calendar-check', 'attendance-icon-success', 'Jadual WFH', (string) $wfhSchedule['monthName'] . ' ' . (string) $year . ' - ' . $departmentName, $monthYearFields()); ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel attendance-compact-panel">
                        <div class="responsive-table">
                            <table class="data-table attendance-compact-table attendance-calendar-table">
                                <thead><tr><th>Nama</th><?php foreach ($wfhSchedule['days'] as $day): ?><th><?= Html::encode((string) $day['day']) ?><br><small><?= Html::encode((string) $day['weekday']) ?></small></th><?php endforeach; ?></tr></thead>
                                <tbody>
                                    <?php if ($wfhSchedule['rows'] === []): ?><tr><td colspan="<?= Html::encode((string) (count($wfhSchedule['days']) + 1)) ?>" class="staff-empty">Tiada kakitangan untuk dipaparkan.</td></tr><?php endif; ?>
                                    <?php foreach ($wfhSchedule['rows'] as $row): ?>
                                        <?php $person = $row['staff']; ?>
                                        <tr>
                                            <td class="grid-name"><?= Html::encode($staffName($person)) ?><br><small><?= Html::encode($staffMeta($person)) ?></small></td>
                                            <?php foreach ($row['cells'] as $cell): ?>
                                                <td><span class="status-badge <?= Html::encode($badgeClass((string) $cell['status'])) ?>"><?= Html::encode((string) $cell['status']) ?></span></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            <?php endif; ?>

            <?php if ($page['slug'] === 'list-kj' && $thtcList !== null): ?>
                <?php /** @var list<array<string, mixed>> $rows */
                $rows = $thtcList['rows']; ?>
                <?php $toolbar('', 'calendar-x', 'attendance-icon-danger', 'Tidak Hadir Tanpa Cuti (THTC)', $departmentName, '', 'Open list', count($rows)); ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel attendance-compact-panel">
                        <div class="responsive-table">
                            <table class="data-table attendance-compact-table">
                                <thead><tr><th>Bil</th><th>Tarikh Absent</th><th>Day</th><th>Nama</th><th>JFPIB</th><th>Remark Absent</th><th>Remark Permohonan THTC</th><th>Dokumen</th><th>Status</th><th>Tindakan</th></tr></thead>
                                <tbody>
                                    <?php if ($rows === []): ?><tr><td colspan="10" class="staff-empty">Tiada rekod untuk dipaparkan.</td></tr><?php endif; ?>
                                    <?php foreach ($rows as $index => $row): ?>
                                        <tr>
                                            <td><span class="attendance-row-number"><?= Html::encode((string) ($index + 1)) ?></span></td>
                                            <td><?= Html::encode((string) $row['date']) ?></td>
                                            <td><?= Html::encode((string) $row['day']) ?></td>
                                            <td><?= Html::encode((string) $row['staffName']) ?></td>
                                            <td><?= Html::encode((string) $row['department']) ?></td>
                                            <td><?= Html::encode((string) $row['absentRemark']) ?></td>
                                            <td><?= Html::encode((string) $row['remark']) ?></td>
                                            <td><?= $row['documentUrl'] === '' ? '-' : '<a class="grid-action" href="' . Html::encode((string) $row['documentUrl']) . '" target="_blank" rel="noopener"><i data-lucide="download"></i><span>File</span></a>' ?></td>
                                            <td><?= $row['status'] === '' ? '-' : '<span class="status-badge ' . Html::encode($badgeClass((string) $row['status'])) . '">' . Html::encode((string) $row['status']) . '</span>' ?></td>
                                            <td><a class="grid-action attendance-primary-action" href="<?= Html::encode((string) $row['actionUrl']) ?>"><i data-lucide="pencil"></i><span>Review</span></a></td>
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
