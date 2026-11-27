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

$title = $detail === null ? 'Maklumat Admin' : (string) $detail['title'];
$statusClass = static fn (string $status): string => $status === 'Aktif' ? 'status-badge-success' : 'status-badge-danger';
$this->setTitle($title . ' - ' . $applicationParams->name);
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p><?= $detail === null ? 'Pengurusan akses admin gambar profil' : Html::encode((string) $detail['name']) ?></p>
            </div>
            <div class="profile-actions">
                <a class="button-link button-link-secondary" href="<?= Html::encode((string) ($detail['backUrl'] ?? '/gambar-profile/senarai-admin')) ?>">
                    <i data-lucide="arrow-left"></i>
                    <span>Kembali</span>
                </a>
            </div>
        </div>

        <?php if ($detail === null): ?>
            <div class="profile-empty">Rekod admin tidak dijumpai.</div>
        <?php else: ?>
            <div class="page-card-section page-section-body">
                <section class="content-inner-card">
                    <div class="profile-card-header">
                        <h2>Maklumat Admin</h2>
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
                    <div class="record-form-actions">
                        <a class="button-link button-link-secondary" href="<?= Html::encode((string) $detail['backUrl']) ?>">
                            <i data-lucide="arrow-left"></i>
                            <span>Kembali</span>
                        </a>
                        <a class="button-link" href="<?= Html::encode((string) $detail['editUrl']) ?>">
                            <i data-lucide="square-pen"></i>
                            <span>Kemaskini</span>
                        </a>
                    </div>
                </section>
            </div>
        <?php endif; ?>
    </section>
</main>
