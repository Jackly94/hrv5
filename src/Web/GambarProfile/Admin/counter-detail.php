<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var array<string, mixed>|null $detail
 * @var string $notice
 * @var string $message
 */

$title = $detail === null ? 'Rekod Muat Naik Gambar Kakitangan' : (string) $detail['title'];
$statusClass = static function (string $status): string {
    return match ($status) {
        'Diluluskan' => 'status-badge-success',
        'Ditolak' => 'status-badge-danger',
        default => 'status-badge-warning',
    };
};
$this->setTitle($title . ' - ' . $applicationParams->name);
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p><?= $detail === null ? 'Rekod muat naik gambar profil' : Html::encode((string) $detail['name']) ?></p>
            </div>
            <div class="profile-actions">
                <a class="button-link button-link-secondary" href="<?= Html::encode((string) ($detail['backUrl'] ?? '/gambar-profile/admin-senarai-penukaran')) ?>">
                    <i data-lucide="arrow-left"></i>
                    <span>Kembali</span>
                </a>
            </div>
        </div>

        <?php if ($detail === null): ?>
            <div class="profile-empty">Rekod muat naik tidak dijumpai.</div>
        <?php else: ?>
            <div class="page-card-section page-section-body staff-overview-stack">
                <?php if ($message !== ''): ?>
                    <div class="record-form-alert">
                        <div class="alert <?= $notice === 'success' ? 'alert-success' : 'alert-danger' ?>"><?= Html::encode($message) ?></div>
                    </div>
                <?php endif; ?>

                <section class="content-inner-card">
                    <div class="profile-card-header">
                        <h2>Perbandingan Gambar</h2>
                    </div>
                    <div class="profile-photo-comparison">
                        <a class="profile-photo-panel" href="<?= Html::encode((string) $detail['currentPhoto']) ?>" target="_blank" rel="noopener">
                            <span class="profile-photo-panel-title">Gambar BKUMS</span>
                            <img class="profile-photo-preview" src="<?= Html::encode((string) $detail['currentPhoto']) ?>" alt="Gambar profil BKUMS">
                        </a>
                        <?php if (($detail['uploadedPhoto'] ?? '') !== ''): ?>
                            <a class="profile-photo-panel" href="<?= Html::encode((string) $detail['uploadedPhoto']) ?>" target="_blank" rel="noopener">
                                <span class="profile-photo-panel-title">Gambar Dimuat Naik</span>
                                <img class="profile-photo-preview" src="<?= Html::encode((string) $detail['uploadedPhoto']) ?>" alt="Gambar profil dimuat naik">
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php if (($detail['downloadUrl'] ?? '') !== ''): ?>
                        <div class="record-form-actions">
                            <a class="grid-action" href="<?= Html::encode((string) $detail['downloadUrl']) ?>" target="_blank" rel="noopener">
                                <i data-lucide="download"></i>
                                <span>Muat Turun Gambar</span>
                            </a>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="content-inner-card">
                    <div class="profile-card-header">
                        <h2>Maklumat Muat Naik</h2>
                    </div>
                    <dl class="detail-list">
                        <?php foreach ($detail['fields'] as $field): ?>
                            <div>
                                <dt><?= Html::encode((string) $field['label']) ?></dt>
                                <dd>
                                    <?php if (($field['kind'] ?? '') === 'status'): ?>
                                        <span class="status-badge <?= Html::encode($statusClass((string) $field['value'])) ?>"><?= Html::encode((string) $field['value']) ?></span>
                                    <?php else: ?>
                                        <?= Html::encode((string) $field['value']) ?>
                                    <?php endif; ?>
                                </dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                </section>
            </div>
        <?php endif; ?>
    </section>
</main>
