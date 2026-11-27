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
 * @var array{title: string} $page
 * @var list<int> $years
 * @var int $year
 * @var int $month
 * @var string $staffIcno
 * @var array<string, mixed>|null $report
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
    'APPROVED', 'CEMERLANG', 'GREEN' => 'status-badge-success',
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
                <a class="button-link button-link-secondary" href="/kehadiran/senarai_kakitangan">
                    <i data-lucide="arrow-left"></i>
                    <span>Kembali</span>
                </a>
            </div>

            <?php require __DIR__ . '/_topMenu.php'; ?>

            <?php if ($report === null): ?>
                <div class="page-section-body page-card-section">
                    <section class="content-inner-card attendance-standard-toolbar">
                        <div class="attendance-standard-title">
                            <span class="attendance-standard-icon attendance-icon-danger" aria-hidden="true"><i data-lucide="shield-alert"></i></span>
                            <div>
                                <h2>Access denied</h2>
                                <p>This staff record is not inside your attendance supervisor scope.</p>
                            </div>
                        </div>
                    </section>
                </div>
            <?php else: ?>
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
                        <form class="attendance-standard-filter" method="get" action="/kehadiran/supervisor-staff-attendance-report">
                            <input type="hidden" name="icno" value="<?= Html::encode($staffIcno) ?>">
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
                    <article class="metric-card"><span>Working day(Completed)</span><strong><?= Html::encode((string) $summary['completedDays']) ?></strong></article>
                    <article class="metric-card"><span>Working Hours</span><strong><?= Html::encode((string) $summary['workingHours']) ?></strong></article>
                    <article class="metric-card"><span>Late In</span><strong><?= Html::encode((string) $summary['lateIn']) ?></strong></article>
                    <article class="metric-card"><span>Early Out</span><strong><?= Html::encode((string) $summary['earlyOut']) ?></strong></article>
                    <article class="metric-card"><span>Incomplete</span><strong><?= Html::encode((string) $summary['incomplete']) ?></strong></article>
                    <article class="metric-card"><span>Absent</span><strong><?= Html::encode((string) $summary['absent']) ?></strong></article>
                    <article class="metric-card"><span>External</span><strong><?= Html::encode((string) $summary['external']) ?></strong></article>
                </div>

                <div class="page-section-body page-card-section">
                    <section class="content-inner-card staff-page-panel">
                        <div class="responsive-table">
                            <table class="data-table staff-attendance-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Day</th>
                                        <th>WBB</th>
                                        <th>In</th>
                                        <th>Out</th>
                                        <th>Non-compliance status</th>
                                        <th>Leave/Outstation</th>
                                        <th>Working Hours</th>
                                        <th>Remark?</th>
                                        <th>Location</th>
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
