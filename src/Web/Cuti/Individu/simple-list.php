<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var string $title
 * @var string $kind
 * @var list<array<string, mixed>> $rows
 * @var list<array<string, mixed>> $menuSections
 * @var string $legacyUrl
 * @var list<array<string, mixed>>|null $entitlements
 * @var array<string, mixed>|null $openWindow
 */

$this->setTitle($title . ' - ' . $applicationParams->name);
?>

<main class="cuti-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p>Cuti</p>
            </div>
            <div class="profile-actions">
                <a class="button-link button-link-secondary" href="/cuti/individu/index">
                    <i data-lucide="arrow-left"></i>
                    <span>Kembali</span>
                </a>
            </div>
        </div>

        <div class="page-section-body page-card-section">
            <?php require dirname(__DIR__) . '/Shared/menu.php'; ?>
        </div>

        <div class="page-section-body">
            <section class="dashboard-panel">
                <div class="dashboard-panel-header">
                    <h2><?= Html::encode($title) ?></h2>
                    <a class="grid-action" href="<?= Html::encode($legacyUrl) ?>" target="_blank" rel="noopener"><i data-lucide="external-link"></i><span>Yii2</span></a>
                </div>

                <?php if ($kind === 'gcr' && isset($openWindow)): ?>
                    <div class="cuti-inline-note">
                        <i data-lucide="info"></i>
                        <span>
                            <?php if ($openWindow === null): ?>
                                Permohonan GCR/CBTH semasa tidak dibuka.
                            <?php else: ?>
                                Dibuka untuk tahun <?= Html::encode((string) ($openWindow['year'] ?? '-')) ?>:
                                <?= Html::encode((string) ($openWindow['full_date'] ?? '-')) ?>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if ($rows === []): ?>
                    <div class="profile-empty">Tiada rekod ditemui.</div>
                <?php else: ?>
                    <div class="responsive-table">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <?php if ($kind === 'entitlement'): ?>
                                        <th>Tempoh</th>
                                        <th>Kelayakan</th>
                                        <th>Digunakan</th>
                                        <th>GCR</th>
                                        <th>Baki</th>
                                    <?php elseif ($kind === 'gcr'): ?>
                                        <th>Tahun</th>
                                        <th>Mohon</th>
                                        <th>Baki</th>
                                        <th>GCR</th>
                                        <th>CBTH</th>
                                        <th>Status</th>
                                    <?php else: ?>
                                        <th>Type</th>
                                        <th>Date</th>
                                        <th>Period</th>
                                        <th>Remark</th>
                                        <th>Status</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <?php if ($kind === 'entitlement'): ?>
                                            <td><?= Html::encode((string) ($row['layak_mula'] ?? '-')) ?> - <?= Html::encode((string) ($row['layak_tamat'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['total_layak'] ?? '0')) ?></td>
                                            <td><?= Html::encode((string) ($row['used_cuti'] ?? '0')) ?></td>
                                            <td><?= Html::encode((string) ($row['layak_gcr'] ?? '0')) ?></td>
                                            <td><strong><?= Html::encode((string) ($row['balance'] ?? '0')) ?></strong></td>
                                        <?php elseif ($kind === 'gcr'): ?>
                                            <td><?= Html::encode((string) ($row['gcr_year'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['mohon_dt'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['leave_balance'] ?? '0')) ?></td>
                                            <td><?= Html::encode((string) ($row['gcr_applied'] ?? '0')) ?></td>
                                            <td><?= Html::encode((string) ($row['cbth_applied'] ?? '0')) ?></td>
                                            <td><span class="status-badge"><?= Html::encode((string) ($row['status'] ?? '-')) ?></span></td>
                                        <?php else: ?>
                                            <td><?= Html::encode((string) ($row['jenis_cuti_label'] ?? $row['staff_name'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['date_label'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['tempoh_label'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['remark'] ?? '-')) ?></td>
                                            <td><span class="status-badge"><?= Html::encode((string) ($row['status_label'] ?? '-')) ?></span></td>
                                        <?php endif; ?>
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
