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
 * @var int $listPage
 * @var int $pageSize
 * @var int|null $departmentId
 * @var array<string, mixed> $staffFilters
 * @var array<string, string> $wfhFilters
 * @var array<string, string> $statFilters
 * @var array<string, string> $rawFilters
 * @var array<string, mixed> $thtcFilters
 * @var array<string, mixed> $adminScope
 * @var array<string, mixed>|null $staffList
 * @var array<string, mixed>|null $monthlyReport
 * @var array<string, mixed>|null $warningLetters
 * @var array<string, mixed>|null $rawAttendance
 * @var list<array<string, mixed>>|null $wfhApplications
 * @var array<string, mixed>|null $wfhSchedule
 * @var array<string, mixed>|null $wfhStatistics
 * @var array<string, mixed>|null $adminPosts
 * @var array<string, mixed>|null $thtcOverview
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
$departments = is_array($adminScope['departments'] ?? null) ? $adminScope['departments'] : [];
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
$paginateRows = static function (array $rows) use ($listPage, $pageSize): array {
    $total = count($rows);
    $pageCount = max(1, (int) ceil($total / $pageSize));
    $page = min(max(1, $listPage), $pageCount);
    $offset = ($page - 1) * $pageSize;

    return [
        'rows' => array_slice($rows, $offset, $pageSize),
        'total' => $total,
        'page' => $page,
        'pageCount' => $pageCount,
        'offset' => $offset,
    ];
};
$cleanQuery = static fn (array $query): array => array_filter($query, static fn (mixed $value): bool => $value !== null && $value !== '');
$renderPagination = static function (string $path, array $query, array $pageData) use ($cleanQuery): void {
    $total = (int) $pageData['total'];
    $page = (int) $pageData['page'];
    $pageCount = (int) $pageData['pageCount'];
    $visibleCount = count($pageData['rows']);
    $firstVisible = $visibleCount === 0 ? 0 : (int) $pageData['offset'] + 1;
    $lastVisible = (int) $pageData['offset'] + $visibleCount;
    $pageLink = static fn (int $targetPage): string => $path . '?' . http_build_query(array_merge($cleanQuery($query), ['page' => (string) $targetPage]));
    $startPage = max(1, $page - 2);
    $endPage = min($pageCount, $page + 2);
    if ($endPage - $startPage < 4) {
        $startPage = max(1, $endPage - 4);
        $endPage = min($pageCount, $startPage + 4);
    }
    ?>
    <div class="grid-count-footer">
        <?php if ($pageCount > 1): ?>
            <nav class="grid-pagination" aria-label="Halaman <?= Html::encode($path) ?>">
                <a class="<?= $page === 1 ? 'is-disabled' : '' ?>" href="<?= Html::encode($pageLink(max(1, $page - 1))) ?>" aria-label="Halaman sebelumnya"><i data-lucide="chevron-left"></i><span>Sebelum</span></a>
                <?php for ($pageNumber = $startPage; $pageNumber <= $endPage; $pageNumber++): ?>
                    <a class="<?= $pageNumber === $page ? 'is-active' : '' ?>" href="<?= Html::encode($pageLink($pageNumber)) ?>"><?= Html::encode((string) $pageNumber) ?></a>
                <?php endfor; ?>
                <a class="<?= $page === $pageCount ? 'is-disabled' : '' ?>" href="<?= Html::encode($pageLink(min($pageCount, $page + 1))) ?>" aria-label="Halaman seterusnya"><span>Seterus</span><i data-lucide="chevron-right"></i></a>
            </nav>
        <?php endif; ?>
        <span class="grid-count">Papar <?= Html::encode((string) $firstVisible) ?>-<?= Html::encode((string) $lastVisible) ?> daripada <?= Html::encode((string) $total) ?> rekod</span>
    </div>
    <?php
};
$monthYearFields = static function (bool $includeMonth = true) use ($years, $year, $month, $months): string {
    $html = '<label>Year<select name="tahun">';
    foreach ($years as $optionYear) {
        $html .= '<option value="' . Html::encode((string) $optionYear) . '"' . ($optionYear === $year ? ' selected' : '') . '>' . Html::encode((string) $optionYear) . '</option>';
    }
    $html .= '</select></label>';
    if ($includeMonth) {
        $html .= '<label>Month<select name="bulan">';
        foreach ($months as $optionMonth => $monthName) {
            $html .= '<option value="' . Html::encode((string) $optionMonth) . '"' . ($optionMonth === $month ? ' selected' : '') . '>' . Html::encode($monthName) . '</option>';
        }
        $html .= '</select></label>';
    }

    return $html;
};
$departmentField = static function (string $name = 'dept_id') use ($departments, $departmentId): string {
    $html = '<label>Department<select name="' . Html::encode($name) . '" data-searchable-select><option value="">All</option>';
    foreach ($departments as $department) {
        $id = (int) ($department['id'] ?? 0);
        $label = (string) ($department['shortName'] ?? $department['name'] ?? $id);
        $html .= '<option value="' . Html::encode((string) $id) . '"' . ($departmentId === $id ? ' selected' : '') . '>' . Html::encode($label) . '</option>';
    }

    return $html . '</select></label>';
};
$toolbar = static function (string $action, string $icon, string $iconClass, string $title, string $subtitle, string $fields = '', ?string $countLabel = null, ?int $count = null, string $fieldsClass = 'attendance-standard-fields-wide'): void {
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
                    <div class="attendance-standard-fields <?= Html::encode($fieldsClass) ?>"><?= $fields ?></div>
                    <div class="attendance-standard-actions"><button class="button-link" type="submit"><i data-lucide="search"></i><span>Search</span></button></div>
                </form>
            <?php elseif ($countLabel !== null && $count !== null): ?>
                <div class="attendance-standard-count"><span><?= Html::encode($countLabel) ?></span><strong><?= Html::encode((string) $count) ?></strong></div>
            <?php endif; ?>
        </section>
    </div>
    <?php
};
$staffFields = '<label>Search<input type="text" name="q" value="' . Html::encode((string) ($staffFilters['q'] ?? '')) . '" placeholder="Name, staff no, IC"></label>' . $departmentField();
$staffGradeFields = $staffFields . '<label>Grade<input type="text" name="grade" value="' . Html::encode((string) ($staffFilters['grade'] ?? '')) . '" placeholder="Grade or position"></label>';
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

            <?php if (!(bool) ($adminScope['admin'] ?? false)): ?>
                <?php $toolbar('', 'shield-alert', 'attendance-icon-danger', 'Administrator', 'Administrator access is not available for this account.', '', 'Records', 0); ?>
            <?php endif; ?>

            <?php if ($page['slug'] === 'staff-all' && $staffList !== null): ?>
                <?php $allRows = $staffList['rows'];
                $pageData = $paginateRows($allRows);
                $rows = $pageData['rows'];
                $numberOffset = (int) $pageData['offset']; ?>
                <?php $toolbar('/kehadiran/staff-all', 'address-book', '', 'Staff lists', count($allRows) . ' staff', $staffGradeFields, null, null, 'attendance-standard-fields-staff-list-standard'); ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel attendance-compact-panel">
                        <div class="responsive-table">
                            <table class="data-table attendance-compact-table">
                                <thead><tr><th>Bil</th><th>Nama Kakitangan</th><th>Staff No</th><th>Jawatan</th><th>JFPIB</th><th>Campus</th><th>Kehadiran</th></tr></thead>
                                <tbody>
                                    <?php if ($rows === []): ?><tr><td colspan="7" class="staff-empty">Tiada kakitangan untuk dipaparkan.</td></tr><?php endif; ?>
                                    <?php foreach ($rows as $index => $person): ?>
                                        <tr>
                                            <td><span class="attendance-row-number"><?= Html::encode((string) ($numberOffset + $index + 1)) ?></span></td>
                                            <td><?= Html::encode($staffName($person)) ?><br><small><?= Html::encode((string) ($person['icno'] ?? '-')) ?></small></td>
                                            <td><?= Html::encode((string) ($person['staffNo'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($person['position'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($person['departmentShort'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($person['campus'] ?? '-')) ?></td>
                                            <td><a class="grid-action" href="<?= Html::encode((string) ($person['reportUrl'] ?? '#')) ?>" target="_blank" rel="noopener"><i data-lucide="bar-chart-3"></i><span>Yii2</span></a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php $renderPagination('/kehadiran/staff-all', ['q' => $staffFilters['q'] ?? '', 'dept_id' => $departmentId, 'grade' => $staffFilters['grade'] ?? ''], $pageData); ?>
                    </section>
                </div>
            <?php endif; ?>

            <?php if ($page['slug'] === 'admin-mth-rpt' && $monthlyReport !== null): ?>
                <?php $allRows = $monthlyReport['rows'];
                $pageData = $paginateRows($allRows);
                $rows = $pageData['rows'];
                $numberOffset = (int) $pageData['offset'];
                $totals = $monthlyReport['totals']; ?>
                <?php $toolbar('/kehadiran/admin-mth-rpt', 'bar-chart-3', 'attendance-icon-info', 'Statistic (JFPIU)', (string) $monthlyReport['monthName'] . ' ' . (string) $year, $monthYearFields(true) . $departmentField()); ?>
                <div class="dashboard-grid dashboard-metrics page-section-body page-card-section" aria-label="Admin monthly totals">
                    <article class="metric-card"><span>Total Days</span><strong><?= Html::encode((string) ($totals['totalDay'] ?? 0)) ?></strong></article>
                    <article class="metric-card"><span>Late In</span><strong><?= Html::encode((string) ($totals['lateIn'] ?? 0)) ?></strong></article>
                    <article class="metric-card"><span>Absent</span><strong><?= Html::encode((string) ($totals['absent'] ?? 0)) ?></strong></article>
                    <article class="metric-card"><span>Rejected</span><strong><?= Html::encode((string) ($totals['rejected'] ?? 0)) ?></strong></article>
                </div>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel attendance-compact-panel">
                        <div class="responsive-table">
                            <table class="data-table attendance-compact-table">
                                <thead><tr><th>Bil</th><th>Nama</th><th>Gred</th><th>JFPIB</th><th>Total Day</th><th>Late In</th><th>Early Out</th><th>Incomplete</th><th>Absent</th><th>External</th><th>Approved</th><th>Rejected</th></tr></thead>
                                <tbody>
                                    <?php if ($rows === []): ?><tr><td colspan="12" class="staff-empty">Tiada rekod untuk bulan ini.</td></tr><?php endif; ?>
                                    <?php foreach ($rows as $index => $row): ?>
                                        <tr>
                                            <td><span class="attendance-row-number"><?= Html::encode((string) ($numberOffset + $index + 1)) ?></span></td>
                                            <td><?= Html::encode((string) $row['name']) ?></td>
                                            <td><?= Html::encode((string) $row['grade']) ?></td>
                                            <td><?= Html::encode((string) $row['department']) ?></td>
                                            <td><?= Html::encode((string) $row['totalDay']) ?></td>
                                            <td><?= Html::encode((string) $row['lateIn']) ?></td>
                                            <td><?= Html::encode((string) $row['earlyOut']) ?></td>
                                            <td><?= Html::encode((string) $row['incomplete']) ?></td>
                                            <td><?= Html::encode((string) $row['absent']) ?></td>
                                            <td><?= Html::encode((string) $row['external']) ?></td>
                                            <td><?= Html::encode((string) $row['approved']) ?></td>
                                            <td><?= Html::encode((string) $row['rejected']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php $renderPagination('/kehadiran/admin-mth-rpt', ['tahun' => $year, 'bulan' => $month, 'dept_id' => $departmentId], $pageData); ?>
                    </section>
                </div>
            <?php endif; ?>

            <?php if ($page['slug'] === 'color-rpt' && $warningLetters !== null): ?>
                <?php $allRows = $warningLetters['rows'];
                $pageData = $paginateRows($allRows);
                $rows = $pageData['rows'];
                $numberOffset = (int) $pageData['offset'];
                $color = (string) ($warningLetters['color'] ?? 'RED');
                $colorFields = $monthYearFields(true) . '<label>Color<select name="color">';
                foreach (['RED', 'YELLOW', 'GREEN'] as $option) {
                    $colorFields .= '<option value="' . Html::encode($option) . '"' . ($option === $color ? ' selected' : '') . '>' . Html::encode($option) . '</option>';
                }
                $colorFields .= '</select></label>'; ?>
                <?php $toolbar('/kehadiran/color-rpt', 'file-warning', 'attendance-icon-warning', 'Warning Letters', (string) ($warningLetters['monthName'] ?? '') . ' ' . (string) $year, $colorFields); ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel attendance-compact-panel">
                        <div class="responsive-table">
                            <table class="data-table attendance-compact-table">
                                <thead><tr><th>Bil</th><th>Nama</th><th>Gred</th><th>JFPIB</th><th>Ketidakpatuhan</th><th>Diterima</th><th>Tidak Diterima</th><th>Warna Kad</th></tr></thead>
                                <tbody>
                                    <?php if ($rows === []): ?><tr><td colspan="8" class="staff-empty">Tiada warning letter untuk filter ini.</td></tr><?php endif; ?>
                                    <?php foreach ($rows as $index => $row): ?>
                                        <tr>
                                            <td><span class="attendance-row-number"><?= Html::encode((string) ($numberOffset + $index + 1)) ?></span></td>
                                            <td><?= Html::encode((string) $row['staffName']) ?></td>
                                            <td><?= Html::encode((string) $row['grade']) ?></td>
                                            <td><?= Html::encode((string) $row['department']) ?></td>
                                            <td><?= Html::encode((string) $row['nonCompliance']) ?></td>
                                            <td><?= Html::encode((string) $row['approved']) ?></td>
                                            <td><?= Html::encode((string) $row['disapproved']) ?></td>
                                            <td><span class="status-badge <?= Html::encode($badgeClass((string) $row['color'])) ?>"><?= Html::encode((string) $row['color']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php $renderPagination('/kehadiran/color-rpt', ['tahun' => $year, 'bulan' => $month, 'color' => $color], $pageData); ?>
                    </section>
                </div>
            <?php endif; ?>

            <?php if ($page['slug'] === 'index' && $rawAttendance !== null): ?>
                <?php $allRows = $rawAttendance['rows'];
                $pageData = $paginateRows($allRows);
                $rows = $pageData['rows'];
                $numberOffset = (int) $pageData['offset'];
                $rawFields = '<label>Search<input type="text" name="q" value="' . Html::encode($rawFilters['q']) . '" placeholder="Name, staff no, IC"></label><label>Date<input type="date" name="date" value="' . Html::encode($rawFilters['date']) . '"></label>'; ?>
                <?php $toolbar('/attendance/index', 'database', 'attendance-icon-info', 'Raw Data', count($allRows) . ' records', $rawFields); ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel attendance-compact-panel">
                        <div class="responsive-table">
                            <table class="data-table attendance-compact-table">
                                <thead><tr><th>Bil</th><th>Tarikh</th><th>Nama</th><th>JFPIB</th><th>WBB</th><th>Masa Masuk</th><th>Masa Keluar</th><th>Status</th><th>Perincian</th></tr></thead>
                                <tbody>
                                    <?php if ($rows === []): ?><tr><td colspan="9" class="staff-empty">Tiada rekod mentah untuk dipaparkan.</td></tr><?php endif; ?>
                                    <?php foreach ($rows as $index => $row): ?>
                                        <tr>
                                            <td><span class="attendance-row-number"><?= Html::encode((string) ($numberOffset + $index + 1)) ?></span></td>
                                            <td><?= Html::encode((string) $row['date']) ?></td>
                                            <td><?= Html::encode((string) $row['staffName']) ?></td>
                                            <td><?= Html::encode((string) $row['department']) ?></td>
                                            <td><?= Html::encode((string) $row['wbb']) ?></td>
                                            <td><?= Html::encode((string) $row['timeIn']) ?></td>
                                            <td><?= Html::encode((string) $row['timeOut']) ?></td>
                                            <td><?= $statusBadges($row['statusLabels']) ?></td>
                                            <td><a class="grid-action" href="<?= Html::encode((string) $row['detailUrl']) ?>" target="_blank" rel="noopener"><i data-lucide="eye"></i><span>Yii2</span></a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php $renderPagination('/attendance/index', ['q' => $rawFilters['q'], 'date' => $rawFilters['date']], $pageData); ?>
                    </section>
                </div>
            <?php endif; ?>

            <?php if ($page['slug'] === 'pantau-permohonan-wfh-admin' && $wfhApplications !== null): ?>
                <?php $allRows = $wfhApplications;
                $pageData = $paginateRows($allRows);
                $rows = $pageData['rows'];
                $numberOffset = (int) $pageData['offset'];
                $wfhFields = '<label>Search<input type="text" name="q" value="' . Html::encode($wfhFilters['q']) . '" placeholder="Name, remark, date"></label><label>Status<select name="status">';
                foreach (['' => 'All', 'ENTRY' => 'ENTRY', 'APPROVED' => 'APPROVED', 'REJECTED' => 'REJECTED', 'VERIFIED' => 'VERIFIED'] as $option => $label) {
                    $wfhFields .= '<option value="' . Html::encode($option) . '"' . ($option === $wfhFilters['status'] ? ' selected' : '') . '>' . Html::encode($label) . '</option>';
                }
                $wfhFields .= '</select></label>'; ?>
                <?php $toolbar('/kehadiran/pantau-permohonan-wfh-admin', 'home', 'attendance-icon-success', 'Pantau Permohonan JAFPIB', count($allRows) . ' records', $wfhFields); ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel attendance-compact-panel">
                        <div class="responsive-table">
                            <table class="data-table attendance-compact-table">
                                <thead><tr><th>Bil</th><th>Tarikh WFH</th><th>Nama</th><th>Gred</th><th>JFPIB</th><th>Catatan</th><th>Status</th><th>Tarikh Mohon</th><th>Buang</th></tr></thead>
                                <tbody>
                                    <?php if ($rows === []): ?><tr><td colspan="9" class="staff-empty">Tiada rekod untuk dipaparkan.</td></tr><?php endif; ?>
                                    <?php foreach ($rows as $index => $row): ?>
                                        <tr>
                                            <td><span class="attendance-row-number"><?= Html::encode((string) ($numberOffset + $index + 1)) ?></span></td>
                                            <td><?= Html::encode((string) $row['date']) ?></td>
                                            <td><?= Html::encode((string) $row['staffName']) ?></td>
                                            <td><?= Html::encode((string) $row['grade']) ?></td>
                                            <td><?= Html::encode((string) $row['department']) ?></td>
                                            <td><?= Html::encode((string) $row['remark']) ?></td>
                                            <td><span class="status-badge <?= Html::encode($badgeClass((string) $row['status'])) ?>"><?= Html::encode((string) $row['status']) ?></span></td>
                                            <td><?= Html::encode((string) $row['entryDate']) ?></td>
                                            <td>
                                                <form class="grid-action-form" method="post" action="<?= Html::encode((string) $row['deleteUrl']) ?>" onsubmit="return window.confirm('Padam permohonan WFH ini?');">
                                                    <?= $csrf->hiddenInput() ?>
                                                    <input type="hidden" name="redirect" value="/kehadiran/pantau-permohonan-wfh-admin">
                                                    <button class="grid-action grid-action-danger" type="submit"><i data-lucide="trash-2"></i><span>Padam</span></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php $renderPagination('/kehadiran/pantau-permohonan-wfh-admin', ['q' => $wfhFilters['q'], 'status' => $wfhFilters['status']], $pageData); ?>
                    </section>
                </div>
            <?php endif; ?>

            <?php if ($page['slug'] === 'wfh-jadual-admin' && $wfhSchedule !== null): ?>
                <?php $toolbar('/kehadiran/wfh-jadual-admin', 'calendar-check', 'attendance-icon-success', 'Jadual JAFPIB', (string) $wfhSchedule['monthName'] . ' ' . (string) $year, $monthYearFields(true) . $departmentField()); ?>
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

            <?php if ($page['slug'] === 'statistik-wfh' && $wfhStatistics !== null): ?>
                <?php $allRows = $wfhStatistics['rows'];
                $pageData = $paginateRows($allRows);
                $rows = $pageData['rows'];
                $numberOffset = (int) $pageData['offset'];
                $statFields = '<label>Date<input type="date" name="date" value="' . Html::encode($statFilters['date']) . '"></label><label>Department<input type="text" name="department" value="' . Html::encode($statFilters['department']) . '" placeholder="Department name"></label>' . $monthYearFields(true); ?>
                <?php $toolbar('/kehadiran/statistik-wfh', 'bar-chart-3', 'attendance-icon-info', 'Statistik WFH', 'Total WFH staff ' . (string) ($wfhStatistics['totalStaff'] ?? 0), $statFields); ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel attendance-compact-panel">
                        <div class="responsive-table">
                            <table class="data-table attendance-compact-table">
                                <thead><tr><th>Bil</th><th>Tarikh</th><th>Bulan</th><th>Tahun</th><th>JFPIB</th><th>Nama Jabatan</th><th>Bilangan</th></tr></thead>
                                <tbody>
                                    <?php if ($rows === []): ?><tr><td colspan="7" class="staff-empty">Tiada statistik WFH untuk filter ini.</td></tr><?php endif; ?>
                                    <?php foreach ($rows as $index => $row): ?>
                                        <tr>
                                            <td><span class="attendance-row-number"><?= Html::encode((string) ($numberOffset + $index + 1)) ?></span></td>
                                            <td><?= Html::encode((string) $row['date']) ?></td>
                                            <td><?= Html::encode((string) $row['month']) ?></td>
                                            <td><?= Html::encode((string) $row['year']) ?></td>
                                            <td><?= Html::encode((string) $row['departmentShort']) ?></td>
                                            <td><?= Html::encode((string) $row['department']) ?></td>
                                            <td><span class="status-badge status-badge-success"><?= Html::encode((string) $row['total']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php $renderPagination('/kehadiran/statistik-wfh', ['date' => $statFilters['date'], 'department' => $statFilters['department'], 'tahun' => $year, 'bulan' => $month], $pageData); ?>
                    </section>
                </div>
            <?php endif; ?>

            <?php if ($page['slug'] === 'admin-post-list' && $adminPosts !== null): ?>
                <?php $allRows = $adminPosts['rows'];
                $pageData = $paginateRows($allRows);
                $rows = $pageData['rows'];
                $numberOffset = (int) $pageData['offset']; ?>
                <?php $toolbar('/kehadiran/admin-post-list', 'briefcase', 'attendance-icon-info', 'Admin Post (Akad)', count($allRows) . ' records', '<label>Search<input type="text" name="q" value="' . Html::encode((string) ($staffFilters['q'] ?? '')) . '" placeholder="Name, position, department"></label>'); ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel attendance-compact-panel">
                        <div class="responsive-table">
                            <table class="data-table attendance-compact-table">
                                <thead><tr><th>Bil</th><th>Nama</th><th>IC</th><th>Jawatan Pentadbiran</th><th>Deskripsi</th><th>JFPIB</th><th>Mula</th><th>Tamat</th></tr></thead>
                                <tbody>
                                    <?php if ($rows === []): ?><tr><td colspan="8" class="staff-empty">Tiada admin post untuk dipaparkan.</td></tr><?php endif; ?>
                                    <?php foreach ($rows as $index => $row): ?>
                                        <tr>
                                            <td><span class="attendance-row-number"><?= Html::encode((string) ($numberOffset + $index + 1)) ?></span></td>
                                            <td><?= Html::encode((string) $row['staffName']) ?></td>
                                            <td><?= Html::encode((string) $row['icno']) ?></td>
                                            <td><?= Html::encode((string) $row['position']) ?></td>
                                            <td><?= Html::encode((string) $row['description']) ?></td>
                                            <td><?= Html::encode((string) $row['department']) ?></td>
                                            <td><?= Html::encode((string) $row['startDate']) ?></td>
                                            <td><?= Html::encode((string) $row['endDate']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php $renderPagination('/kehadiran/admin-post-list', ['q' => $staffFilters['q'] ?? ''], $pageData); ?>
                    </section>
                </div>
            <?php endif; ?>

            <?php if ($page['slug'] === 'absent-list-bpsm' && $thtcOverview !== null): ?>
                <?php $allRows = $thtcOverview['rows'];
                $pageData = $paginateRows($allRows);
                $rows = $pageData['rows'];
                $numberOffset = (int) $pageData['offset'];
                $statusFields = '<label>Search<input type="text" name="q" value="' . Html::encode((string) ($thtcFilters['q'] ?? '')) . '" placeholder="Name or IC"></label>' . $departmentField() . '<label>Status<select name="status"><option value="">All</option>';
                foreach ([1 => 'ENTRY', 2 => 'VERIFIED', 3 => 'APPROVED', 4 => 'REJECTED', 5 => 'RETURNED'] as $option => $label) {
                    $statusFields .= '<option value="' . Html::encode((string) $option) . '"' . ((string) ($thtcFilters['status'] ?? '') === (string) $option ? ' selected' : '') . '>' . Html::encode($label) . '</option>';
                }
                $statusFields .= '</select></label>'; ?>
                <?php $toolbar('/attendance/absent-list-bpsm', 'calendar-x', 'attendance-icon-danger', 'Tidak Hadir Tanpa Cuti (THTC)', count($allRows) . ' staff with open THTC records', $statusFields); ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel attendance-compact-panel">
                        <div class="responsive-table">
                            <table class="data-table attendance-compact-table">
                                <thead><tr><th>Bil</th><th>Nama</th><th>IC</th><th>JFPIB</th><th>Tarikh Terkini</th><th>Status</th><th>Bilangan</th><th>Perincian</th></tr></thead>
                                <tbody>
                                    <?php if ($rows === []): ?><tr><td colspan="8" class="staff-empty">Tiada rekod untuk dipaparkan.</td></tr><?php endif; ?>
                                    <?php foreach ($rows as $index => $row): ?>
                                        <tr>
                                            <td><span class="attendance-row-number"><?= Html::encode((string) ($numberOffset + $index + 1)) ?></span></td>
                                            <td><?= Html::encode((string) $row['staffName']) ?></td>
                                            <td><?= Html::encode((string) $row['icno']) ?></td>
                                            <td><?= Html::encode((string) $row['department']) ?></td>
                                            <td><?= Html::encode((string) $row['latestDate']) ?></td>
                                            <td><span class="status-badge <?= Html::encode($badgeClass((string) $row['status'])) ?>"><?= Html::encode((string) $row['status']) ?></span></td>
                                            <td><?= Html::encode((string) $row['totalRecords']) ?></td>
                                            <td><a class="grid-action" href="<?= Html::encode((string) $row['detailUrl']) ?>" target="_blank" rel="noopener"><i data-lucide="eye"></i><span>Yii2</span></a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php $renderPagination('/attendance/absent-list-bpsm', ['q' => $thtcFilters['q'] ?? '', 'dept_id' => $departmentId, 'status' => $thtcFilters['status'] ?? ''], $pageData); ?>
                    </section>
                </div>
            <?php endif; ?>
        </section>
    </main>
<?php endif; ?>
