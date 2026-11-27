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
 * @var array<string, mixed> $attendance
 * @var string $identityId
 * @var array{title: string} $page
 * @var array<string, mixed> $day
 * @var int|null $departmentId
 * @var string $message
 * @var string $messageType
 */

$this->setTitle($page['title'] . ' - STARS - ' . $applicationParams->name);
$query = array_filter([
    'tahun' => substr((string) $day['date'], 0, 4),
    'bulan' => substr((string) $day['date'], 5, 2),
    'dept_id' => $departmentId,
], static fn (mixed $value): bool => $value !== null && $value !== '');
$backUrl = '/kehadiran/wfh-by-mth?' . http_build_query($query);
$actionUrl = '/kehadiran/wfh-edit-day?date=' . rawurlencode((string) $day['date']) . ($departmentId === null ? '' : '&dept_id=' . rawurlencode((string) $departmentId));
?>

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
            <a class="button-link button-link-secondary" href="<?= Html::encode($backUrl) ?>">
                <i data-lucide="arrow-left"></i>
                <span>Kembali</span>
            </a>
        </div>

        <?php require __DIR__ . '/_topMenu.php'; ?>

        <div class="page-section-body page-card-section">
            <section class="content-inner-card attendance-standard-toolbar">
                <div class="attendance-standard-title">
                    <span class="attendance-standard-icon attendance-icon-success" aria-hidden="true"><i data-lucide="home"></i></span>
                    <div>
                        <h2>Set WFH Harian</h2>
                        <p><?= Html::encode((string) $day['dateLabel']) ?></p>
                    </div>
                </div>
                <div class="attendance-standard-actions">
                    <a class="button-link button-link-secondary" href="<?= Html::encode($backUrl) ?>"><i data-lucide="arrow-left"></i><span>Kembali Senarai Bulanan</span></a>
                </div>
            </section>
        </div>

        <?php if ($message !== ''): ?>
            <div class="page-section-body page-card-section">
                <section class="content-inner-card staff-page-panel attendance-compact-panel">
                    <span class="status-badge <?= $messageType === 'success' ? 'status-badge-success' : 'status-badge-danger' ?>"><?= Html::encode($message) ?></span>
                </section>
            </div>
        <?php endif; ?>

        <div class="dashboard-grid dashboard-metrics page-section-body page-card-section" aria-label="WFH daily totals">
            <article class="metric-card"><span>Total Staff</span><strong><?= Html::encode((string) $day['totalStaff']) ?></strong></article>
            <article class="metric-card"><span>WFH</span><strong><?= Html::encode((string) $day['totalWfh']) ?> (<?= Html::encode((string) $day['percentWfh']) ?>%)</strong></article>
            <article class="metric-card"><span>WFO</span><strong><?= Html::encode((string) $day['totalWfo']) ?> (<?= Html::encode((string) $day['percentWfo']) ?>%)</strong></article>
        </div>

        <div class="page-section-body page-card-section">
            <section class="content-inner-card staff-page-panel attendance-compact-panel">
                <form class="attendance-standard-filter" method="post" action="<?= Html::encode($actionUrl) ?>">
                    <?= $csrf->hiddenInput() ?>
                    <input type="hidden" name="mode" value="add">
                    <div class="attendance-standard-fields attendance-standard-fields-wide">
                        <label>Nama Kakitangan
                            <select name="add_icno[]" multiple size="8">
                                <?php foreach ($day['availableStaff'] as $person): ?>
                                    <option value="<?= Html::encode((string) ($person['icno'] ?? '')) ?>">
                                        <?= Html::encode((string) ($person['name'] ?? '-')) ?> - <?= Html::encode((string) ($person['departmentShort'] ?? $person['department'] ?? '-')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                    <div class="attendance-standard-actions">
                        <button class="button-link" type="submit"><i data-lucide="plus"></i><span>Masukkan ke WFH</span></button>
                    </div>
                </form>
            </section>
        </div>

        <div class="page-section-body page-card-section">
            <section class="content-inner-card staff-page-panel attendance-compact-panel">
                <form method="post" action="<?= Html::encode($actionUrl) ?>" onsubmit="return window.confirm('Tukar rekod terpilih ke WFO?');">
                    <?= $csrf->hiddenInput() ?>
                    <input type="hidden" name="mode" value="remove">
                    <div class="responsive-table">
                        <table class="data-table attendance-compact-table">
                            <thead><tr><th>Pilih</th><th>Bil</th><th>Nama</th><th>Gred</th><th>Jabatan</th><th>Tempoh</th><th>Catatan</th></tr></thead>
                            <tbody>
                                <?php if ($day['wfhRows'] === []): ?><tr><td colspan="7" class="staff-empty">Tiada rekod WFH untuk tarikh ini.</td></tr><?php endif; ?>
                                <?php foreach ($day['wfhRows'] as $index => $row): ?>
                                    <tr>
                                        <td><input type="checkbox" name="remove_id[]" value="<?= Html::encode((string) $row['id']) ?>"></td>
                                        <td><span class="attendance-row-number"><?= Html::encode((string) ($index + 1)) ?></span></td>
                                        <td><?= Html::encode((string) $row['staffName']) ?><br><small><?= Html::encode((string) $row['icno']) ?></small></td>
                                        <td><?= Html::encode((string) $row['grade']) ?></td>
                                        <td><?= Html::encode((string) $row['department']) ?></td>
                                        <td><?= Html::encode((string) $row['dateRange']) ?></td>
                                        <td><?= Html::encode((string) $row['remark']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="attendance-standard-actions">
                        <button class="button-link button-link-secondary" type="submit"><i data-lucide="send"></i><span>Tukar ke WFO</span></button>
                    </div>
                </form>
            </section>
        </div>
    </section>
</main>
