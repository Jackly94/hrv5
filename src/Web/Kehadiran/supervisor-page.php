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
 * @var array<string, mixed> $supervisorScope
 * @var array<string, mixed>|null $staffList
 * @var array<string, mixed>|null $staffWbb
 * @var array<string, mixed>|null $monthlyReport
 * @var array<string, mixed>|null $yearlyReport
 * @var array<string, mixed>|null $cardColorReport
 * @var array<string, mixed>|null $cardPerformanceReport
 * @var array<string, mixed>|null $pendingStaff
 * @var array<string, mixed>|null $shieldStatus
 * @var array<string, mixed>|null $shiftStaff
 * @var array<string, mixed>|null $shiftSetup
 * @var array<string, mixed>|null $manageShiftSupervisor
 * @var array<string, mixed>|null $wfhMonth
 * @var array<string, mixed>|null $wfhSchedule
 * @var list<array<string, mixed>>|null $wfhApplications
 * @var array<string, mixed>|null $thtcList
 */

$this->setTitle($page['title'] . ' - STARS - ' . $applicationParams->name);
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
    $departments = is_array($supervisorScope['departments'] ?? null) ? $supervisorScope['departments'] : [];
    $badgeClass = static fn (string $status): string => match (strtoupper($status)) {
        'APPROVED', 'VERIFIED', 'GREEN', 'WFH' => 'status-badge-success',
        'REJECTED', 'RED', 'NO' => 'status-badge-danger',
        'ENTRY', 'RETURNED', 'YELLOW' => 'status-badge-warning',
        default => '',
    };
    $staffName = static fn (array $staff): string => (string) ($staff['name'] ?? $staff['staffName'] ?? 'Tiada Rekod');
    $staffMeta = static fn (array $staff): string => trim((string) ($staff['grade'] ?? '-') . ' / ' . (string) ($staff['departmentShort'] ?? $staff['department'] ?? '-'), ' /');
    $numberOffset = 0;
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
    $cleanQuery = static fn (array $query): array => array_filter(
        $query,
        static fn (mixed $value): bool => $value !== null && $value !== '',
    );
    $renderPagination = static function (string $path, array $query, array $pageData) use ($cleanQuery): void {
        $total = (int) $pageData['total'];
        $page = (int) $pageData['page'];
        $pageCount = (int) $pageData['pageCount'];
        $visibleCount = count($pageData['rows']);
        $firstVisible = $visibleCount === 0 ? 0 : (int) $pageData['offset'] + 1;
        $lastVisible = (int) $pageData['offset'] + $visibleCount;
        $pageLink = static fn (int $targetPage): string => $path . '?' . http_build_query(
            array_merge($cleanQuery($query), ['page' => (string) $targetPage]),
        );
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
                    <a class="<?= $page === 1 ? 'is-disabled' : '' ?>" href="<?= Html::encode($pageLink(max(1, $page - 1))) ?>" aria-label="Halaman sebelumnya">
                        <i data-lucide="chevron-left"></i>
                        <span>Sebelum</span>
                    </a>
                    <?php for ($pageNumber = $startPage; $pageNumber <= $endPage; $pageNumber++): ?>
                        <a class="<?= $pageNumber === $page ? 'is-active' : '' ?>" href="<?= Html::encode($pageLink($pageNumber)) ?>"><?= Html::encode((string) $pageNumber) ?></a>
                    <?php endfor; ?>
                    <a class="<?= $page === $pageCount ? 'is-disabled' : '' ?>" href="<?= Html::encode($pageLink(min($pageCount, $page + 1))) ?>" aria-label="Halaman seterusnya">
                        <span>Seterus</span>
                        <i data-lucide="chevron-right"></i>
                    </a>
                </nav>
            <?php endif; ?>
            <span class="grid-count">Papar <?= Html::encode((string) $firstVisible) ?>-<?= Html::encode((string) $lastVisible) ?> daripada <?= Html::encode((string) $total) ?> rekod</span>
        </div>
        <?php
    };
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
    $departmentField = static function () use ($departments, $departmentId): string {
        $html = '<label>Department<select name="dept_id" data-searchable-select><option value="">All</option>';
        foreach ($departments as $department) {
            $id = (int) ($department['id'] ?? 0);
            $label = (string) ($department['shortName'] ?? $department['name'] ?? $id);
            $html .= '<option value="' . Html::encode((string) $id) . '"' . ($departmentId === $id ? ' selected' : '') . '>' . Html::encode($label) . '</option>';
        }

        return $html . '</select></label>';
    };
    $toolbar = static function (string $action, string $icon, string $iconClass, string $title, string $subtitle, string $fields, ?string $countLabel = null, ?int $count = null, string $toolbarClass = '', string $fieldsClass = 'attendance-standard-fields-wide'): void {
        ?>
        <div class="page-section-body page-card-section">
            <section class="content-inner-card attendance-standard-toolbar <?= Html::encode($toolbarClass) ?>">
                <div class="attendance-standard-title">
                    <span class="attendance-standard-icon <?= Html::encode($iconClass) ?>" aria-hidden="true"><i data-lucide="<?= Html::encode($icon) ?>"></i></span>
                    <div>
                        <h2><?= Html::encode($title) ?></h2>
                        <?php if ($subtitle !== ''): ?>
                            <p><?= Html::encode($subtitle) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($fields !== ''): ?>
                    <form class="attendance-standard-filter" method="get" action="<?= Html::encode($action) ?>">
                        <div class="attendance-standard-fields <?= Html::encode($fieldsClass) ?>"><?= $fields ?></div>
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
    $staffSearchFields = '<label>Search<input type="text" name="q" value="' . Html::encode((string) ($staffFilters['q'] ?? '')) . '" placeholder="Name, staff no, IC"></label>' . $departmentField();
    $staffGradeFields = $staffSearchFields . '<label>Grade<input type="text" name="grade" value="' . Html::encode((string) ($staffFilters['grade'] ?? '')) . '" placeholder="Grade or position"></label>';
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

            <?php if (!(bool) ($supervisorScope['authorized'] ?? false)): ?>
                <?php $toolbar('', 'shield-alert', 'attendance-icon-danger', 'Attendance Supervisor', 'Access setup is not available for this account.', '', 'Records', 0); ?>
            <?php endif; ?>

            <?php if ($page['slug'] === 'senarai_kakitangan' && $staffList !== null): ?>
                <?php /** @var list<array<string, mixed>> $allRows */
                $allRows = $staffList['rows'];
                $pageData = $paginateRows($allRows);
                $rows = $pageData['rows'];
                $numberOffset = (int) $pageData['offset']; ?>
                <?php $toolbar('/kehadiran/senarai_kakitangan', 'address-book', '', 'Staff lists', '', $staffGradeFields, null, null, '', 'attendance-standard-fields-staff-list-standard'); ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel attendance-compact-panel">
                        <div class="responsive-table">
                            <table class="data-table attendance-compact-table">
                                <thead><tr><th>Bil</th><th>Nama Kakitangan</th><th>Staff No</th><th>Jawatan</th><th>JFPIB</th><th>Campus</th><th>Kehadiran</th><th>WBB</th><th>Peraku/Lulus</th></tr></thead>
                                <tbody>
                                    <?php if ($rows === []): ?><tr><td colspan="9" class="staff-empty">Tiada kakitangan untuk dipaparkan.</td></tr><?php endif; ?>
                                    <?php foreach ($rows as $index => $person): ?>
                                        <tr>
                                            <td><span class="attendance-row-number"><?= Html::encode((string) ($numberOffset + $index + 1)) ?></span></td>
                                            <td><?= Html::encode($staffName($person)) ?><br><small><?= Html::encode((string) ($person['icno'] ?? '-')) ?></small></td>
                                            <td><?= Html::encode((string) ($person['staffNo'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($person['position'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($person['departmentShort'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($person['campus'] ?? '-')) ?></td>
                                            <td><a class="grid-action" href="<?= Html::encode('/kehadiran/supervisor-staff-attendance-report?icno=' . rawurlencode((string) ($person['icno'] ?? ''))) ?>"><i data-lucide="bar-chart-3"></i><span>View</span></a></td>
                                            <td><a class="grid-action" href="<?= Html::encode('/kehadiran/staff-wbb?q=' . rawurlencode((string) ($person['staffNo'] ?? $person['icno'] ?? ''))) ?>"><i data-lucide="clock"></i><span>View</span></a></td>
                                            <td><a class="grid-action" href="<?= Html::encode('/kehadiran/staff-wbb?q=' . rawurlencode((string) ($person['staffNo'] ?? $person['icno'] ?? ''))) ?>"><i data-lucide="users"></i><span>View</span></a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php $renderPagination('/kehadiran/senarai_kakitangan', ['q' => $staffFilters['q'] ?? '', 'dept_id' => $departmentId, 'grade' => $staffFilters['grade'] ?? ''], $pageData); ?>
                    </section>
                </div>
            <?php endif; ?>

            <?php if ($page['slug'] === 'staff-wbb' && $staffWbb !== null): ?>
                <?php /** @var list<array<string, mixed>> $allRows */
                $allRows = $staffWbb['rows'];
                $pageData = $paginateRows($allRows);
                $rows = $pageData['rows'];
                $numberOffset = (int) $pageData['offset']; ?>
                <?php $toolbar('/kehadiran/staff-wbb', 'bar-chart-3', 'attendance-icon-info', 'Staff Working hours', '', $staffSearchFields); ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel attendance-compact-panel">
                        <div class="responsive-table">
                            <table class="data-table attendance-compact-table">
                                <thead><tr><th>Bil</th><th>Nama Kakitangan</th><th>Gred / JFPIB</th><th>Peraku</th><th>Pelulus</th><th>Current WP</th><th>WBB</th><th>Peraku/Lulus</th></tr></thead>
                                <tbody>
                                    <?php if ($rows === []): ?><tr><td colspan="8" class="staff-empty">Tiada rekod WBB untuk dipaparkan.</td></tr><?php endif; ?>
                                    <?php foreach ($rows as $index => $row): ?>
                                        <?php /** @var array<string, mixed> $person */
                                        $person = $row['staff']; ?>
                                        <tr>
                                            <td><span class="attendance-row-number"><?= Html::encode((string) ($numberOffset + $index + 1)) ?></span></td>
                                            <td><?= Html::encode($staffName($person)) ?></td>
                                            <td><?= Html::encode($staffMeta($person)) ?></td>
                                            <td><?= Html::encode((string) $row['peraku']) ?></td>
                                            <td><?= Html::encode((string) $row['pelulus']) ?></td>
                                            <td><span class="status-badge"><?= Html::encode((string) $row['wbb']) ?></span></td>
                                            <td><a class="grid-action" href="<?= Html::encode((string) $row['wbbUrl']) ?>"><i data-lucide="clock"></i><span>WBB</span></a></td>
                                            <td><a class="grid-action" href="<?= Html::encode((string) $row['pegawaiUrl']) ?>"><i data-lucide="users"></i><span>Set</span></a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php $renderPagination('/kehadiran/staff-wbb', ['q' => $staffFilters['q'] ?? '', 'dept_id' => $departmentId], $pageData); ?>
                    </section>
                </div>
            <?php endif; ?>

            <?php if ($page['slug'] === 'sup-mth-rpt' && $monthlyReport !== null): ?>
                <?php /** @var list<array<string, mixed>> $allRows */
                $allRows = $monthlyReport['rows'];
                $pageData = $paginateRows($allRows);
                $rows = $pageData['rows'];
                $numberOffset = (int) $pageData['offset'];
                /** @var array<string, int> $totals */
                $totals = $monthlyReport['totals']; ?>
                <?php $toolbar('/kehadiran/sup-mth-rpt', 'flag', 'attendance-icon-info', 'Monthly Reports', (string) $monthlyReport['monthName'] . ' ' . (string) $year, $monthYearFields(true) . $departmentField()); ?>
                <div class="dashboard-grid dashboard-metrics page-section-body page-card-section" aria-label="Supervisor monthly totals">
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
                        <?php $renderPagination('/kehadiran/sup-mth-rpt', ['tahun' => $year, 'bulan' => $month, 'dept_id' => $departmentId], $pageData); ?>
                    </section>
                </div>
            <?php endif; ?>

            <?php if ($page['slug'] === 'sup-year-rpt' && $yearlyReport !== null): ?>
                <?php /** @var list<array<string, mixed>> $allRows */
                $allRows = $yearlyReport['rows'];
                $pageData = $paginateRows($allRows);
                $rows = $pageData['rows'];
                $numberOffset = (int) $pageData['offset']; ?>
                <?php $toolbar('/kehadiran/sup-year-rpt', 'globe-2', 'attendance-icon-info', 'Yearly Reports', (string) $year, $monthYearFields(false) . $departmentField()); ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel attendance-compact-panel">
                        <div class="responsive-table">
                            <table class="data-table attendance-compact-table">
                                <thead><tr><th>Bil</th><th>Nama Kakitangan</th><th>Gred / JFPIB</th><th>Working Days</th><th>Late In</th><th>Early Out</th><th>Incomplete</th><th>Absent</th><th>External</th></tr></thead>
                                <tbody>
                                    <?php if ($rows === []): ?><tr><td colspan="9" class="staff-empty">Tiada rekod tahunan.</td></tr><?php endif; ?>
                                    <?php foreach ($rows as $index => $row): ?>
                                        <?php $person = $row['staff']; ?>
                                        <tr>
                                            <td><span class="attendance-row-number"><?= Html::encode((string) ($numberOffset + $index + 1)) ?></span></td>
                                            <td><?= Html::encode($staffName($person)) ?></td>
                                            <td><?= Html::encode($staffMeta($person)) ?></td>
                                            <td><?= Html::encode((string) $row['workingDays']) ?></td>
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
                        <?php $renderPagination('/kehadiran/sup-year-rpt', ['tahun' => $year, 'dept_id' => $departmentId], $pageData); ?>
                    </section>
                </div>
            <?php endif; ?>

            <?php if ($page['slug'] === 'senarai-warna-kad' && $cardColorReport !== null): ?>
                <?php /** @var list<array<string, mixed>> $allRows */
                $allRows = $cardColorReport['rows'];
                $pageData = $paginateRows($allRows);
                $rows = $pageData['rows'];
                $numberOffset = (int) $pageData['offset']; ?>
                <?php $toolbar('/kehadiran/senarai-warna-kad', 'palette', 'attendance-icon-warning', 'Card Colour Report', (string) $cardColorReport['monthName'] . ' ' . (string) $year, $monthYearFields(true) . $departmentField()); ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel attendance-compact-panel">
                        <div class="responsive-table">
                            <table class="data-table attendance-compact-table">
                                <thead><tr><th>Bil</th><th>Nama Kakitangan</th><th>Gred / JFPIB</th><th>Ketidakpatuhan</th><th>Diterima</th><th>Tidak Diterima</th><th>Warna Kad</th></tr></thead>
                                <tbody>
                                    <?php if ($rows === []): ?><tr><td colspan="7" class="staff-empty">Tiada rekod kad warna.</td></tr><?php endif; ?>
                                    <?php foreach ($rows as $index => $row): ?>
                                        <?php $person = $row['staff']; ?>
                                        <tr>
                                            <td><span class="attendance-row-number"><?= Html::encode((string) ($numberOffset + $index + 1)) ?></span></td>
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
                        <?php $renderPagination('/kehadiran/senarai-warna-kad', ['tahun' => $year, 'bulan' => $month, 'dept_id' => $departmentId], $pageData); ?>
                    </section>
                </div>
            <?php endif; ?>

            <?php if ($page['slug'] === 'sup-year-performance-rpt' && $cardPerformanceReport !== null): ?>
                <?php /** @var list<array<string, mixed>> $allRows */
                $allRows = $cardPerformanceReport['rows'];
                $pageData = $paginateRows($allRows);
                $rows = $pageData['rows'];
                $numberOffset = (int) $pageData['offset']; ?>
                <?php $toolbar('/kehadiran/sup-year-performance-rpt', 'activity', 'attendance-icon-warning', 'Card Colour Performance', (string) $year, $monthYearFields(false) . $departmentField()); ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel attendance-compact-panel">
                        <div class="responsive-table">
                            <table class="data-table attendance-compact-table">
                                <thead><tr><th>Bil</th><th>Nama Kakitangan</th><th>Gred / JFPIB</th><th>Green</th><th>Yellow</th><th>Red</th><th>Prestasi</th></tr></thead>
                                <tbody>
                                    <?php if ($rows === []): ?><tr><td colspan="7" class="staff-empty">Tiada rekod prestasi.</td></tr><?php endif; ?>
                                    <?php foreach ($rows as $index => $row): ?>
                                        <?php $person = $row['staff']; ?>
                                        <tr>
                                            <td><span class="attendance-row-number"><?= Html::encode((string) ($numberOffset + $index + 1)) ?></span></td>
                                            <td><?= Html::encode($staffName($person)) ?></td>
                                            <td><?= Html::encode($staffMeta($person)) ?></td>
                                            <td><span class="status-badge status-badge-success"><?= Html::encode((string) $row['green']) ?></span></td>
                                            <td><span class="status-badge status-badge-warning"><?= Html::encode((string) $row['yellow']) ?></span></td>
                                            <td><span class="status-badge status-badge-danger"><?= Html::encode((string) $row['red']) ?></span></td>
                                            <td><?= Html::encode((string) $row['performance']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php $renderPagination('/kehadiran/sup-year-performance-rpt', ['tahun' => $year, 'dept_id' => $departmentId], $pageData); ?>
                    </section>
                </div>
            <?php endif; ?>

            <?php if ($page['slug'] === 'pantau-staf' && $pendingStaff !== null): ?>
                <?php /** @var list<array<string, mixed>> $allRows */
                $allRows = $pendingStaff['rows'];
                $pageData = $paginateRows($allRows);
                $rows = $pageData['rows'];
                $numberOffset = (int) $pageData['offset']; ?>
                <?php $toolbar('/kehadiran/pantau-staf', 'list-checks', 'attendance-icon-danger', 'Pantau Staf', 'Pending staff reason queue', $staffSearchFields); ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel attendance-compact-panel">
                        <div class="responsive-table">
                            <table class="data-table attendance-compact-table">
                                <thead><tr><th>Bil</th><th>Nama</th><th>Gred / JFPIB</th><th>Tarikh</th><th>Masa Masuk</th><th>Masa Keluar</th><th>Status</th><th>Alasan</th><th>Tindakan</th></tr></thead>
                                <tbody>
                                    <?php if ($rows === []): ?><tr><td colspan="9" class="staff-empty">Belum ada tindakan lagi.</td></tr><?php endif; ?>
                                    <?php foreach ($rows as $index => $row): ?>
                                        <tr>
                                            <td><span class="attendance-row-number"><?= Html::encode((string) ($numberOffset + $index + 1)) ?></span></td>
                                            <td><?= Html::encode((string) $row['staffName']) ?></td>
                                            <td><?= Html::encode((string) $row['grade'] . ' / ' . (string) $row['department']) ?></td>
                                            <td><?= Html::encode((string) $row['date']) ?></td>
                                            <td><?= Html::encode((string) $row['timeIn']) ?></td>
                                            <td><?= Html::encode((string) $row['timeOut']) ?></td>
                                            <td><?= $statusBadges($row['statusLabels']) ?></td>
                                            <td><?= Html::encode((string) $row['remark']) ?></td>
                                            <td><a class="grid-action attendance-primary-action" href="<?= Html::encode((string) $row['actionUrl']) ?>" target="_blank" rel="noopener"><i data-lucide="pencil"></i><span>Yii2</span></a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php $renderPagination('/kehadiran/pantau-staf', ['q' => $staffFilters['q'] ?? '', 'dept_id' => $departmentId], $pageData); ?>
                    </section>
                </div>
            <?php endif; ?>

            <?php if ($page['slug'] === 'shield-status-list' && $shieldStatus !== null): ?>
                <?php /** @var list<array<string, mixed>> $allRows */
                $allRows = $shieldStatus['rows'];
                $pageData = $paginateRows($allRows);
                $rows = $pageData['rows'];
                $numberOffset = (int) $pageData['offset']; ?>
                <?php $toolbar('/kehadiran/shield-status-list', 'shield', 'attendance-icon-success', 'Shields Status', 'Scoped staff list with live Yii2 handoff', $staffSearchFields); ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel attendance-compact-panel">
                        <div class="responsive-table">
                            <table class="data-table attendance-compact-table">
                                <thead><tr><th>Bil</th><th>Nama Kakitangan</th><th>Gred / JFPIB</th><th>Shield Color</th><th>Answered Date</th><th>Expiry</th><th>Live Status</th></tr></thead>
                                <tbody>
                                    <?php if ($rows === []): ?><tr><td colspan="7" class="staff-empty">Tiada kakitangan untuk dipaparkan.</td></tr><?php endif; ?>
                                    <?php foreach ($rows as $index => $row): ?>
                                        <?php $person = $row['staff']; ?>
                                        <tr>
                                            <td><span class="attendance-row-number"><?= Html::encode((string) ($numberOffset + $index + 1)) ?></span></td>
                                            <td><?= Html::encode($staffName($person)) ?></td>
                                            <td><?= Html::encode($staffMeta($person)) ?></td>
                                            <td><span class="status-badge <?= Html::encode($badgeClass((string) $row['color'])) ?>"><?= Html::encode((string) $row['color']) ?></span></td>
                                            <td><?= Html::encode((string) $row['takenAt']) ?></td>
                                            <td><?= Html::encode((string) $row['expiresAt']) ?></td>
                                            <td><a class="grid-action" href="<?= Html::encode((string) $row['liveUrl']) ?>" target="_blank" rel="noopener"><i data-lucide="external-link"></i><span>Yii2</span></a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php $renderPagination('/kehadiran/shield-status-list', ['q' => $staffFilters['q'] ?? '', 'dept_id' => $departmentId], $pageData); ?>
                    </section>
                </div>
            <?php endif; ?>

            <?php if ($page['slug'] === 'staff-shift-list' && $shiftStaff !== null): ?>
                <?php /** @var list<array<string, mixed>> $allRows */
                $allRows = $shiftStaff['rows'];
                $pageData = $paginateRows($allRows);
                $rows = $pageData['rows'];
                $numberOffset = (int) $pageData['offset']; ?>
                <?php $toolbar('/kehadiran/staff-shift-list', 'users', 'attendance-icon-info', 'Staff Shift List', count($allRows) . ' shift staff', '<label>Search<input type="text" name="q" value="' . Html::encode((string) ($staffFilters['q'] ?? '')) . '" placeholder="Name, staff no, IC"></label>'); ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel attendance-compact-panel">
                        <div class="responsive-table">
                            <table class="data-table attendance-compact-table">
                                <thead><tr><th>Bil</th><th>Nama Kakitangan</th><th>Staff No</th><th>IC</th><th>Gred / JFPIB</th><th>Added</th><th>Remove</th></tr></thead>
                                <tbody>
                                    <?php if ($rows === []): ?><tr><td colspan="7" class="staff-empty">Tiada staff shift.</td></tr><?php endif; ?>
                                    <?php foreach ($rows as $index => $row): ?>
                                        <tr>
                                            <td><span class="attendance-row-number"><?= Html::encode((string) ($numberOffset + $index + 1)) ?></span></td>
                                            <td><?= Html::encode((string) $row['staffName']) ?></td>
                                            <td><?= Html::encode((string) $row['staffNo']) ?></td>
                                            <td><?= Html::encode((string) $row['icno']) ?></td>
                                            <td><?= Html::encode((string) $row['grade'] . ' / ' . (string) $row['department']) ?></td>
                                            <td><?= Html::encode((string) $row['createdAt']) ?></td>
                                            <td><a class="grid-action grid-action-danger" href="<?= Html::encode((string) $row['removeUrl']) ?>" target="_blank" rel="noopener"><i data-lucide="trash-2"></i><span>Yii2</span></a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php $renderPagination('/kehadiran/staff-shift-list', ['q' => $staffFilters['q'] ?? ''], $pageData); ?>
                    </section>
                </div>
            <?php endif; ?>

            <?php if ($page['slug'] === 'shift-setup' && $shiftSetup !== null): ?>
                <?php $toolbar('/kehadiran/shift-setup', 'wrench', 'attendance-icon-info', 'Shift Setup', (string) $shiftSetup['monthName'] . ' ' . (string) $year, $monthYearFields(true)); ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel attendance-compact-panel">
                        <div class="responsive-table">
                            <table class="data-table attendance-compact-table attendance-calendar-table">
                                <thead><tr><th>Nama</th><?php foreach ($shiftSetup['days'] as $day): ?><th><?= Html::encode((string) $day['day']) ?><br><small><?= Html::encode((string) $day['weekday']) ?></small></th><?php endforeach; ?></tr></thead>
                                <tbody>
                                    <?php if ($shiftSetup['rows'] === []): ?><tr><td colspan="<?= Html::encode((string) (count($shiftSetup['days']) + 1)) ?>" class="staff-empty">Tiada staff shift.</td></tr><?php endif; ?>
                                    <?php foreach ($shiftSetup['rows'] as $row): ?>
                                        <?php $person = $row['staff']; ?>
                                        <tr>
                                            <td class="grid-name"><?= Html::encode((string) $person['staffName']) ?><br><small><?= Html::encode((string) $person['department']) ?></small></td>
                                            <?php foreach ($row['cells'] as $cell): ?>
                                                <td><a class="grid-action attendance-calendar-action" href="<?= Html::encode((string) $cell['url']) ?>" target="_blank" rel="noopener"><?= Html::encode((string) $cell['label']) ?></a></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            <?php endif; ?>

            <?php if ($page['slug'] === 'manage-shift-supervisor' && $manageShiftSupervisor !== null): ?>
                <?php /** @var list<array<string, mixed>> $allRows */
                $allRows = $manageShiftSupervisor['rows'];
                $pageData = $paginateRows($allRows);
                $rows = $pageData['rows'];
                $numberOffset = (int) $pageData['offset']; ?>
                <?php $toolbar('/kehadiran-admin/manage-shift-supervisor', 'wrench', 'attendance-icon-info', 'Manage Supervisor/Staff', (bool) $manageShiftSupervisor['authorized'] ? count($allRows) . ' assignments' : 'Administrator access required', '<label>Search<input type="text" name="q" value="' . Html::encode((string) ($staffFilters['q'] ?? '')) . '" placeholder="Staff or supervisor"></label>'); ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel attendance-compact-panel">
                        <div class="responsive-table">
                            <table class="data-table attendance-compact-table">
                                <thead><tr><th>Bil</th><th>Supervisor</th><th>Supervisor IC</th><th>Staff</th><th>Staff IC</th><th>JFPIB</th><th>Added</th></tr></thead>
                                <tbody>
                                    <?php if ($rows === []): ?><tr><td colspan="7" class="staff-empty">Tiada rekod untuk dipaparkan.</td></tr><?php endif; ?>
                                    <?php foreach ($rows as $index => $row): ?>
                                        <tr>
                                            <td><span class="attendance-row-number"><?= Html::encode((string) ($numberOffset + $index + 1)) ?></span></td>
                                            <td><?= Html::encode((string) $row['supervisorName']) ?></td>
                                            <td><?= Html::encode((string) $row['supervisorIcno']) ?></td>
                                            <td><?= Html::encode((string) $row['staffName']) ?></td>
                                            <td><?= Html::encode((string) $row['staffIcno']) ?></td>
                                            <td><?= Html::encode((string) $row['department']) ?></td>
                                            <td><?= Html::encode((string) $row['createdAt']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php $renderPagination('/kehadiran-admin/manage-shift-supervisor', ['q' => $staffFilters['q'] ?? ''], $pageData); ?>
                    </section>
                </div>
            <?php endif; ?>

            <?php if ($page['slug'] === 'wfh-by-mth' && $wfhMonth !== null): ?>
                <?php $toolbar('/kehadiran/wfh-by-mth', 'home', 'attendance-icon-success', 'Senarai Bulanan WFH', (string) $wfhMonth['monthName'] . ' ' . (string) $year, $monthYearFields(true) . $departmentField()); ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel attendance-compact-panel">
                        <div class="responsive-table">
                            <table class="data-table attendance-compact-table">
                                <thead><tr><th>Bil</th><th>Tarikh</th><th>Hari</th><th>WFH</th><th>WFO</th><th>Total Staff</th><th>Tindakan</th></tr></thead>
                                <tbody>
                                    <?php foreach ($wfhMonth['rows'] as $index => $row): ?>
                                        <tr>
                                            <td><span class="attendance-row-number"><?= Html::encode((string) ($numberOffset + $index + 1)) ?></span></td>
                                            <td><?= Html::encode((string) $row['dateLabel']) ?></td>
                                            <td><?= Html::encode((string) $row['day']) ?></td>
                                            <td><span class="status-badge status-badge-success"><?= Html::encode((string) $row['wfh']) ?></span></td>
                                            <td><?= Html::encode((string) $row['wfo']) ?></td>
                                            <td><?= Html::encode((string) $row['totalStaff']) ?></td>
                                            <td><a class="grid-action attendance-primary-action" href="<?= Html::encode((string) $row['editUrl']) ?>"><i data-lucide="pencil"></i><span>Tindakan</span></a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            <?php endif; ?>

            <?php if ($page['slug'] === 'wfh-jadual' && $wfhSchedule !== null): ?>
                <?php if ((string) ($wfhSchedule['selectedStaffIcno'] ?? '') === ''): ?>
                    <?php /** @var list<array<string, mixed>> $allRows */
                    $allRows = $wfhSchedule['rows'];
                    $pageData = $paginateRows($allRows);
                    $rows = $pageData['rows'];
                    $numberOffset = (int) $pageData['offset'];
                    $wfhScheduleFields = $monthYearFields(true)
                        . '<label>Search<input type="text" name="q" value="' . Html::encode((string) ($staffFilters['q'] ?? '')) . '" placeholder="Name, staff no, IC"></label>'
                        . $departmentField(); ?>
                    <?php $toolbar('/kehadiran/wfh-jadual', 'calendar-check', 'attendance-icon-success', 'Jadual WFH', (string) $wfhSchedule['monthName'] . ' ' . (string) $year, $wfhScheduleFields); ?>
                    <div class="page-section-body page-card-section">
                        <section class="content-inner-card staff-page-panel attendance-compact-panel">
                            <div class="responsive-table">
                                <table class="data-table attendance-compact-table">
                                    <thead><tr><th>Bil</th><th>Nama Kakitangan</th><th>Gred</th><th>Jabatan</th><th>Tindakan</th></tr></thead>
                                    <tbody>
                                        <?php if ($rows === []): ?><tr><td colspan="5" class="staff-empty">Tiada kakitangan untuk dipaparkan.</td></tr><?php endif; ?>
                                        <?php foreach ($rows as $index => $row): ?>
                                            <?php $person = $row['staff'];
                                            $detailUrl = '/kehadiran/wfh-jadual?' . http_build_query($cleanQuery([
                                                'tahun' => $year,
                                                'bulan' => $month,
                                                'q' => $staffFilters['q'] ?? '',
                                                'dept_id' => $departmentId,
                                                'icno' => $person['icno'] ?? '',
                                            ])); ?>
                                            <tr>
                                                <td><span class="attendance-row-number"><?= Html::encode((string) ($numberOffset + $index + 1)) ?></span></td>
                                                <td><?= Html::encode($staffName($person)) ?><br><small><?= Html::encode((string) ($person['staffNo'] ?? $person['icno'] ?? '-')) ?></small></td>
                                                <td><?= Html::encode((string) ($person['grade'] ?? '-')) ?></td>
                                                <td><?= Html::encode((string) ($person['departmentShort'] ?? $person['department'] ?? '-')) ?></td>
                                                <td><a class="grid-action attendance-primary-action" href="<?= Html::encode($detailUrl) ?>"><i data-lucide="calendar-days"></i><span>Tindakan</span></a></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php $renderPagination('/kehadiran/wfh-jadual', ['tahun' => $year, 'bulan' => $month, 'q' => $staffFilters['q'] ?? '', 'dept_id' => $departmentId], $pageData); ?>
                        </section>
                    </div>
                <?php else: ?>
                    <?php $selectedStaff = is_array($wfhSchedule['selectedStaff'] ?? null) ? $wfhSchedule['selectedStaff'] : null;
                    $backUrl = '/kehadiran/wfh-jadual?' . http_build_query($cleanQuery([
                        'tahun' => $year,
                        'bulan' => $month,
                        'q' => $staffFilters['q'] ?? '',
                        'dept_id' => $departmentId,
                    ]));
                    $selectedFields = '<input type="hidden" name="icno" value="' . Html::encode((string) ($wfhSchedule['selectedStaffIcno'] ?? '')) . '">' . $monthYearFields(true); ?>
                    <?php $toolbar('/kehadiran/wfh-jadual', 'calendar-check', 'attendance-icon-success', 'Jadual WFH', (string) $wfhSchedule['monthName'] . ' ' . (string) $year, $selectedFields); ?>
                    <div class="page-section-body page-card-section">
                        <section class="content-inner-card staff-page-panel attendance-compact-panel">
                            <div class="attendance-standard-actions">
                                <a class="button-link button-link-secondary" href="<?= Html::encode($backUrl) ?>"><i data-lucide="arrow-left"></i><span>Kembali Senarai</span></a>
                            </div>
                            <?php if ($selectedStaff === null): ?>
                                <p class="staff-empty">Kakitangan tidak ditemui dalam skop penyelia.</p>
                            <?php else: ?>
                                <div class="responsive-table">
                                    <table class="data-table attendance-compact-table attendance-calendar-table">
                                        <thead><tr><th>Nama</th><?php foreach ($wfhSchedule['days'] as $day): ?><th><?= Html::encode((string) $day['day']) ?><br><small><?= Html::encode((string) $day['weekday']) ?></small></th><?php endforeach; ?></tr></thead>
                                        <tbody>
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
                            <?php endif; ?>
                        </section>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($page['slug'] === 'pantau-permohonan-wfh' && $wfhApplications !== null): ?>
                <?php $allRows = $wfhApplications;
                $pageData = $paginateRows($allRows);
                $wfhRows = $pageData['rows'];
                $numberOffset = (int) $pageData['offset'];
                $wfhFields = '<label>Search<input type="text" name="q" value="' . Html::encode($wfhFilters['q']) . '" placeholder="Name, remark, date"></label><label>Status<select name="status">';
                foreach (['' => 'All', 'ENTRY' => 'ENTRY', 'APPROVED' => 'APPROVED', 'REJECTED' => 'REJECTED', 'VERIFIED' => 'VERIFIED'] as $option => $label) {
                    $wfhFields .= '<option value="' . Html::encode($option) . '"' . ($option === $wfhFilters['status'] ? ' selected' : '') . '>' . Html::encode($label) . '</option>';
                }
                $wfhFields .= '</select></label>'; ?>
                <?php $toolbar('/kehadiran/pantau-permohonan-wfh', 'home', 'attendance-icon-success', 'Pantau Permohonan WFH', count($allRows) . ' records', $wfhFields); ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel attendance-compact-panel">
                        <div class="responsive-table">
                            <table class="data-table attendance-compact-table">
                                <thead><tr><th>Bil</th><th>Tarikh WFH</th><th>Nama</th><th>JFPIB</th><th>Catatan</th><th>Status</th><th>Tarikh Mohon</th><th>Tarikh Kelulusan</th><th>Buang</th></tr></thead>
                                <tbody>
                                    <?php if ($wfhRows === []): ?><tr><td colspan="9" class="staff-empty">Tiada rekod untuk dipaparkan.</td></tr><?php endif; ?>
                                    <?php foreach ($wfhRows as $index => $row): ?>
                                        <tr>
                                            <td><span class="attendance-row-number"><?= Html::encode((string) ($numberOffset + $index + 1)) ?></span></td>
                                            <td><?= Html::encode((string) $row['date']) ?></td>
                                            <td><?= Html::encode((string) $row['staffName']) ?></td>
                                            <td><?= Html::encode((string) $row['department']) ?></td>
                                            <td><?= Html::encode((string) $row['remark']) ?></td>
                                            <td><span class="status-badge <?= Html::encode($badgeClass((string) $row['status'])) ?>"><?= Html::encode((string) $row['status']) ?></span></td>
                                            <td><?= Html::encode((string) $row['entryDate']) ?></td>
                                            <td><?= Html::encode((string) $row['approvalDate']) ?></td>
                                            <td>
                                                <form class="grid-action-form" method="post" action="<?= Html::encode((string) $row['deleteUrl']) ?>" onsubmit="return window.confirm('Padam permohonan WFH ini?');">
                                                    <?= $csrf->hiddenInput() ?>
                                                    <input type="hidden" name="redirect" value="/kehadiran/pantau-permohonan-wfh">
                                                    <button class="grid-action grid-action-danger" type="submit"><i data-lucide="trash-2"></i><span>Padam</span></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php $renderPagination('/kehadiran/pantau-permohonan-wfh', ['q' => $wfhFilters['q'] ?? '', 'status' => $wfhFilters['status'] ?? ''], $pageData); ?>
                    </section>
                </div>
            <?php endif; ?>

            <?php if ($page['slug'] === 'absent-list-sv' && $thtcList !== null): ?>
                <?php /** @var list<array<string, mixed>> $allRows */
                $allRows = $thtcList['rows'];
                $pageData = $paginateRows($allRows);
                $rows = $pageData['rows'];
                $numberOffset = (int) $pageData['offset']; ?>
                <?php $toolbar('', 'calendar-x', 'attendance-icon-danger', 'Tidak Hadir Tanpa Cuti (THTC)', 'Supervisor verification queue', '', 'Open list', count($allRows)); ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel attendance-compact-panel">
                        <div class="responsive-table">
                            <table class="data-table attendance-compact-table">
                                <thead><tr><th>Bil</th><th>Tarikh Absent</th><th>Day</th><th>Nama</th><th>JFPIB</th><th>Remark Absent</th><th>Remark Permohonan THTC</th><th>Dokumen</th><th>Status</th><th>Tindakan</th></tr></thead>
                                <tbody>
                                    <?php if ($rows === []): ?><tr><td colspan="10" class="staff-empty">Tiada rekod untuk dipaparkan.</td></tr><?php endif; ?>
                                    <?php foreach ($rows as $index => $row): ?>
                                        <tr>
                                            <td><span class="attendance-row-number"><?= Html::encode((string) ($numberOffset + $index + 1)) ?></span></td>
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
                        <?php $renderPagination('/kehadiran/absent-list-sv', [], $pageData); ?>
                    </section>
                </div>
            <?php endif; ?>
        </section>
    </main>
<?php endif; ?>
