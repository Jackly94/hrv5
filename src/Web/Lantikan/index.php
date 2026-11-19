<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var array<string, mixed>|null $dashboard
 */

$this->setTitle('Perlantikan Kakitangan - ' . $applicationParams->name);
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1>Perlantikan Kakitangan</h1>
            </div>
            <div class="profile-actions">
                <a class="button-link button-link-secondary" href="/menu">
                    <i data-lucide="arrow-left"></i>
                    <span>Kembali</span>
                </a>
            </div>
        </div>

        <?php if ($dashboard === null): ?>
            <div class="profile-empty">Menu perlantikan tidak tersedia untuk sesi semasa.</div>
        <?php else: ?>
            <?php /** @var list<array<string, mixed>> $tiles */
            $tiles = $dashboard['tiles']; ?>
            <div class="page-card-section page-section-body">
                <div class="dashboard-grid dashboard-metrics">
                    <?php foreach ($tiles as $tile): ?>
                        <article class="metric-card">
                            <span><?= Html::encode((string) $tile['label']) ?></span>
                            <strong><i data-lucide="<?= Html::encode((string) $tile['icon']) ?>"></i></strong>
                            <small><?= Html::encode((string) $tile['description']) ?></small>
                            <?php if ($tile['enabled']): ?>
                                <a class="button-link<?= $tile['legacy'] ? ' button-link-secondary' : '' ?>" href="<?= Html::encode((string) $tile['url']) ?>"<?= $tile['legacy'] ? ' target="_blank" rel="noopener"' : '' ?>>
                                    <i data-lucide="<?= $tile['legacy'] ? 'external-link' : 'arrow-right' ?>"></i>
                                    <span><?= $tile['legacy'] ? 'Yii2' : 'Buka' ?></span>
                                </a>
                            <?php else: ?>
                                <span class="status-badge status-badge-warning">Belum aktif</span>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="page-card-section page-section-body">
                <div class="content-inner-card">
                    <div class="dashboard-panel-header">
                        <h2>Status Migrasi</h2>
                        <span><?= $dashboard['adEnabled'] ? 'AD API enabled' : 'AD API disabled' ?></span>
                    </div>
                    <p>Yii3 menyediakan paparan dan carian read-only. Aliran lantikan yang menulis rekod masih menggunakan handoff Yii2 sehingga transaksi dan kesan sampingan AD/STARS/Cuti disahkan.</p>
                </div>
            </div>
        <?php endif; ?>
    </section>
</main>
