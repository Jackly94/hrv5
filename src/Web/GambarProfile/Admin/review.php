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
 * @var array<string, mixed>|null $review
 * @var string $notice
 * @var string $message
 */

$title = $review === null ? 'Semakan Gambar Profil' : (string) $review['title'];
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
                <p><?= $review === null ? 'Permohonan kemaskini gambar profil' : Html::encode((string) $review['name']) ?></p>
            </div>
            <div class="profile-actions">
                <a class="button-link button-link-secondary" href="<?= Html::encode((string) ($review['backUrl'] ?? '/gambar-profile/senarai')) ?>">
                    <i data-lucide="arrow-left"></i>
                    <span>Kembali</span>
                </a>
            </div>
        </div>

        <?php if ($review === null): ?>
            <div class="profile-empty">Rekod permohonan tidak dijumpai.</div>
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
                        <a class="profile-photo-panel" href="<?= Html::encode((string) $review['currentPhoto']) ?>" target="_blank" rel="noopener">
                            <span class="profile-photo-panel-title">Gambar Semasa</span>
                            <img class="profile-photo-preview" src="<?= Html::encode((string) $review['currentPhoto']) ?>" alt="Gambar profil semasa">
                        </a>
                        <?php if (($review['requestedPhoto'] ?? '') !== ''): ?>
                            <a class="profile-photo-panel" href="<?= Html::encode((string) $review['requestedPhoto']) ?>" target="_blank" rel="noopener">
                                <span class="profile-photo-panel-title">Gambar Dipohon</span>
                                <img class="profile-photo-preview" src="<?= Html::encode((string) $review['requestedPhoto']) ?>" alt="Gambar profil dipohon">
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php if (($review['downloadUrl'] ?? '') !== ''): ?>
                        <div class="record-form-actions">
                            <a class="grid-action" href="<?= Html::encode((string) $review['downloadUrl']) ?>" target="_blank" rel="noopener">
                                <i data-lucide="download"></i>
                                <span>Muat Turun Gambar Dipohon</span>
                            </a>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="content-inner-card">
                    <div class="profile-card-header">
                        <h2>Maklumat Permohonan</h2>
                    </div>
                    <dl class="detail-list">
                        <?php foreach ($review['fields'] as $field): ?>
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

                <?php if (($review['isPending'] ?? false) === true): ?>
                    <section class="content-inner-card">
                        <div class="profile-card-header">
                            <h2>Tindakan BPSM</h2>
                        </div>
                        <form method="post" action="<?= Html::encode((string) $review['action']) ?>">
                            <?= $csrf->hiddenInput() ?>
                            <div class="record-form-grid">
                                <label class="grid-field">
                                    <span>Status</span>
                                    <select name="app_status" required>
                                        <option value="">Sila Pilih</option>
                                        <option value="DILULUSKAN">DILULUSKAN</option>
                                        <option value="TIDAK DILULUSKAN">TIDAK DILULUSKAN</option>
                                    </select>
                                </label>
                                <label class="grid-field grid-field-wide">
                                    <span>Catatan</span>
                                    <textarea name="catatan" rows="5"></textarea>
                                </label>
                            </div>
                            <div class="record-form-actions">
                                <a class="button-link button-link-secondary" href="<?= Html::encode((string) $review['backUrl']) ?>">
                                    <i data-lucide="arrow-left"></i>
                                    <span>Kembali</span>
                                </a>
                                <button class="button-link-success" type="submit">
                                    <i data-lucide="send"></i>
                                    <span>Hantar</span>
                                </button>
                            </div>
                        </form>
                    </section>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
