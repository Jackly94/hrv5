<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var string $title
 * @var list<array<string, mixed>> $rows
 * @var array<string, mixed>|null $openWindow
 * @var list<array<string, mixed>> $menuSections
 * @var string $legacyUrl
 */

$this->setTitle($title . ' - ' . $applicationParams->name);
?>

<main class="cuti-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p>Senarai Permohonan GCR & CBTH</p>
            </div>
            <a class="button-link button-link-secondary" href="/cuti/individu/index"><i data-lucide="arrow-left"></i><span>Kembali</span></a>
        </div>

        <div class="page-section-body page-card-section">
            <?php require dirname(__DIR__) . '/Shared/menu.php'; ?>
        </div>

        <div class="page-section-body">
            <section class="dashboard-panel">
                <div class="dashboard-panel-header">
                    <h2>GCR & CBTH Application List</h2>
                    <a class="grid-action" href="<?= Html::encode($legacyUrl) ?>" target="_blank" rel="noopener"><i data-lucide="external-link"></i><span>Yii2</span></a>
                </div>

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

                <?php if ($rows === []): ?>
                    <div class="profile-empty">Tiada rekod ditemui.</div>
                <?php else: ?>
                    <div class="responsive-table">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Tarikh Mohon</th>
                                    <th>GCR Dimohon</th>
                                    <th>CBTH Dimohon</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td><?= Html::encode((string) ($row['mohon_dt'] ?? '-')) ?></td>
                                        <td><?= Html::encode((string) ($row['gcr_applied'] ?? '0')) ?></td>
                                        <td><?= Html::encode((string) ($row['cbth_applied'] ?? '0')) ?></td>
                                        <td><span class="status-badge"><?= Html::encode((string) ($row['status'] ?? '-')) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <div class="cuti-inline-note">
                    <i data-lucide="badge-info"></i>
                    <span>ENTRY: Permohonan Baru. CHECKED: Telah disemak penyelia cuti. VERIFIED: Telah diperaku Ketua Jabatan/Dekan. APPROVED: Telah diluluskan.</span>
                </div>
            </section>
        </div>
    </section>
</main>
