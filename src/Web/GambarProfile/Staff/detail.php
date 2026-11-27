<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var array<string, mixed>|null $detail
 */

$title = $detail === null ? 'Lihat Permohonan Gambar Profil' : (string) $detail['title'];
$this->setTitle($title . ' - ' . $applicationParams->name);
$statusClass = static function (string $status): string {
    return match ($status) {
        'Diluluskan' => 'status-badge-success',
        'Ditolak' => 'status-badge-danger',
        default => 'status-badge-warning',
    };
};
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p><?= $detail === null ? 'Permohonan kemaskini gambar profil' : Html::encode((string) $detail['name']) ?></p>
            </div>
            <div class="profile-actions">
                <a class="button-link button-link-secondary" href="/gambar-profile/semakan">
                    <i data-lucide="arrow-left"></i>
                    <span>Kembali</span>
                </a>
            </div>
        </div>

        <?php if ($detail === null): ?>
            <div class="profile-empty">Rekod permohonan tidak dijumpai.</div>
        <?php else: ?>
            <div class="page-card-section page-section-body staff-overview-stack">
                <section class="content-inner-card">
                    <div class="profile-card-header">
                        <h2>Perbandingan Gambar</h2>
                    </div>
                    <div class="profile-photo-comparison">
                        <a class="profile-photo-panel" href="<?= Html::encode((string) $detail['currentPhoto']) ?>" target="_blank" rel="noopener">
                            <span class="profile-photo-panel-title">Gambar Semasa</span>
                            <img class="profile-photo-preview" src="<?= Html::encode((string) $detail['currentPhoto']) ?>" alt="Gambar profil semasa">
                        </a>
                        <?php if (($detail['requestedPhoto'] ?? '') !== ''): ?>
                            <a class="profile-photo-panel" href="<?= Html::encode((string) $detail['requestedPhoto']) ?>" target="_blank" rel="noopener">
                                <span class="profile-photo-panel-title">Gambar Dipohon</span>
                                <img class="profile-photo-preview" src="<?= Html::encode((string) $detail['requestedPhoto']) ?>" alt="Gambar profil dipohon">
                            </a>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="content-inner-card">
                    <div class="profile-card-header">
                        <h2>Maklumat Permohonan</h2>
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
