<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var array<string, mixed>|null $dashboard
 * @var string $identityId
 */

$this->setTitle('Cuti - ' . $applicationParams->name);
?>

<?php if ($dashboard === null): ?>
    <main class="cuti-page">
        <section class="profile-card page-main-card">
            <div class="profile-heading page-card-heading">
                <div>
                    <h1>Cuti unavailable</h1>
                    <p>The current legacy session <?= Html::encode($identityId) ?> is not a staff session.</p>
                </div>
            </div>
        </section>
    </main>
<?php else: ?>
    <?php
    /** @var array<string, mixed>|null $currentEntitlement */
    $currentEntitlement = $dashboard['currentEntitlement'];
    /** @var list<array<string, mixed>> $records */
    $records = $dashboard['records'];
    /** @var list<array<string, mixed>> $substituteRecords */
    $substituteRecords = $dashboard['substituteRecords'];
    /** @var list<array<string, string>> $pendingTasks */
    $pendingTasks = $dashboard['pendingTasks'];
    /** @var list<array<string, mixed>> $menuSections */
    $menuSections = $dashboard['menuSections'];
    ?>
    <main class="cuti-page">
        <section class="profile-card page-main-card">
            <div class="profile-heading page-card-heading">
                <div>
                    <h1>Cuti</h1>
                    <p><?= Html::encode((string) $dashboard['staffName']) ?></p>
                </div>
                <div class="profile-actions">
                    <a class="button-link button-link-secondary" href="/menu">
                        <i data-lucide="arrow-left"></i>
                        <span>Menu</span>
                    </a>
                </div>
            </div>

            <div class="page-section-body page-card-section">
                <?php require dirname(__DIR__) . '/Shared/menu.php'; ?>
            </div>

            <div class="dashboard-grid dashboard-metrics page-section-body page-card-section">
                <article class="metric-card">
                    <span>Baki Cuti</span>
                    <strong><?= $currentEntitlement === null ? 'Tiada' : Html::encode((string) $currentEntitlement['balance']) ?></strong>
                    <small><?= Html::encode((string) $dashboard['year']) ?> hari/days</small>
                </article>
                <article class="metric-card">
                    <span>Kelayakan</span>
                    <strong><?= $currentEntitlement === null ? '-' : Html::encode((string) $currentEntitlement['total_layak']) ?></strong>
                    <small>Digunakan <?= $currentEntitlement === null ? '-' : Html::encode((string) $currentEntitlement['used_cuti']) ?></small>
                </article>
                <article class="metric-card">
                    <span>Permohonan Tahun Ini</span>
                    <strong><?= count($records) ?></strong>
                    <small><?= Html::encode((string) $dashboard['year']) ?></small>
                </article>
                <article class="metric-card">
                    <span>Substitute</span>
                    <strong><?= count($substituteRecords) ?></strong>
                    <small>Pengganti kepada</small>
                </article>
            </div>

            <?php if ($pendingTasks !== []): ?>
                <div class="page-section-body">
                    <section class="dashboard-panel">
                        <div class="dashboard-panel-header">
                            <h2>Task to do</h2>
                        </div>
                        <div class="cuti-task-strip">
                            <?php foreach ($pendingTasks as $task): ?>
                                <a href="<?= Html::encode((string) $task['url']) ?>">
                                    <i data-lucide="<?= Html::encode((string) $task['icon']) ?>"></i>
                                    <span><?= Html::encode((string) $task['name']) ?></span>
                                    <b><?= Html::encode((string) $task['count']) ?></b>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </div>
            <?php endif; ?>

            <div class="cuti-columns page-section-body">
                <section class="dashboard-panel">
                    <div class="dashboard-panel-header">
                        <h2>Maklumat Cuti</h2>
                        <a class="grid-action" href="/cuti/individu/list"><i data-lucide="list"></i><span>Semua</span></a>
                    </div>
                    <?php if ($records === []): ?>
                        <div class="profile-empty">Tiada rekod cuti untuk tahun ini.</div>
                    <?php else: ?>
                        <div class="responsive-table">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Date</th>
                                        <th>Period</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($records, 0, 8) as $row): ?>
                                        <tr>
                                            <td><?= Html::encode((string) $row['jenis_cuti_label']) ?></td>
                                            <td><?= Html::encode((string) $row['date_label']) ?></td>
                                            <td><?= Html::encode((string) $row['tempoh_label']) ?></td>
                                            <td><span class="status-badge"><?= Html::encode((string) $row['status_label']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="dashboard-panel">
                    <div class="dashboard-panel-header">
                        <h2>Pengganti Kepada</h2>
                        <a class="grid-action" href="/cuti/individu/substitute-list"><i data-lucide="list"></i><span>Semua</span></a>
                    </div>
                    <?php if ($substituteRecords === []): ?>
                        <div class="profile-empty">Tiada rekod pengganti.</div>
                    <?php else: ?>
                        <div class="responsive-table">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Staff</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($substituteRecords, 0, 8) as $row): ?>
                                        <tr>
                                            <td><?= Html::encode((string) $row['staff_name']) ?></td>
                                            <td><?= Html::encode((string) $row['date_label']) ?></td>
                                            <td><span class="status-badge"><?= Html::encode((string) $row['status_label']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </section>
    </main>
<?php endif; ?>
