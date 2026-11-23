<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var array{name: string}|null $staff
 * @var array<string, string>|null $disability
 * @var string $backUrl
 * @var string $updateUrl
 */

$this->setTitle('Lihat Kecacatan - ' . $applicationParams->name);
$backUrl = $backUrl ?? '/biodata/kecacatan/view';
$updateUrl = $updateUrl ?? ($disability === null ? '' : '/biodata/kecacatan/update?id=' . rawurlencode($disability['id']));
$fields = $disability === null ? [] : [
    ['label' => 'No. Fail Kebajikan', 'value' => $disability['socialWelfareNo']],
    ['label' => 'No. Laporan Doktor', 'value' => $disability['doctorReportNo']],
    ['label' => 'Jenis Kecacatan', 'value' => $disability['type']],
    ['label' => 'Punca Kecacatan', 'value' => $disability['cause']],
    ['label' => 'Tarikh Kecacatan', 'value' => $disability['disabilityDate']],
    ['label' => 'Tarikh Sembuh', 'value' => $disability['healDate']],
    ['label' => 'Fail', 'value' => $disability['file']],
];
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1>Lihat Kecacatan</h1>
                <p><?= $staff === null ? 'Maklumat kecacatan kakitangan' : Html::encode($staff['name']) ?></p>
            </div>
            <?php if ($disability !== null): ?>
                <div class="profile-actions">
                    <a class="button-link button-link-secondary" href="<?= Html::encode($backUrl) ?>">
                        <i data-lucide="arrow-left"></i>
                        <span>Kembali</span>
                    </a>
                    <a class="button-link" href="<?= Html::encode($updateUrl) ?>">
                        <i data-lucide="pencil"></i>
                        <span>Kemaskini</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($disability === null): ?>
            <div class="profile-empty">Rekod kecacatan tidak dijumpai.</div>
        <?php else: ?>
            <div class="page-card-section page-section-body">
                <section class="content-inner-card">
                    <div class="profile-card-header">
                        <h2>Maklumat Kecacatan</h2>
                    </div>
                    <dl class="detail-list">
                        <?php foreach ($fields as $field): ?>
                            <div>
                                <dt><?= Html::encode($field['label']) ?></dt>
                                <dd>
                                    <?php if ($field['label'] === 'Fail' && ($disability['fileUrl'] ?? '') !== ''): ?>
                                        <a class="grid-action" href="<?= Html::encode($disability['fileUrl']) ?>" target="_blank" rel="noopener">
                                            <i data-lucide="file-text"></i>
                                            <span>Lihat Fail</span>
                                        </a>
                                    <?php else: ?>
                                        <?= Html::encode($field['value']) ?>
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
