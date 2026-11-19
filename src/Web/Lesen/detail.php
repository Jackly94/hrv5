<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var array{name: string}|null $staff
 * @var array<string, string>|null $license
 */

$this->setTitle('Lihat Lesen - ' . $applicationParams->name);
$fields = $license === null ? [] : [
    ['label' => 'No. Lesen', 'value' => $license['licNo']],
    ['label' => 'Nama Pemilik Lesen', 'value' => $license['name']],
    ['label' => 'Jenis Lesen', 'value' => $license['type']],
    ['label' => 'Kelas Lesen', 'value' => $license['class']],
    ['label' => 'Tarikh Dikeluarkan', 'value' => $license['issuedDate']],
    ['label' => 'Tarikh Luput', 'value' => $license['expiryDate']],
    ['label' => 'Yuran Pembaharuan', 'value' => $license['fee']],
    ['label' => 'Fail', 'value' => $license['file']],
];
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1>Lihat Lesen</h1>
                <p><?= $staff === null ? 'Maklumat lesen kakitangan' : Html::encode($staff['name']) ?></p>
            </div>
            <?php if ($license !== null): ?>
                <div class="profile-actions">
                    <a class="button-link button-link-secondary" href="/lesen/view">
                        <i data-lucide="arrow-left"></i>
                        <span>Kembali</span>
                    </a>
                    <a class="button-link" href="/lesen/update?id=<?= Html::encode(rawurlencode($license['id'])) ?>">
                        <i data-lucide="pencil"></i>
                        <span>Kemaskini</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($license === null): ?>
            <div class="profile-empty">Rekod lesen tidak dijumpai.</div>
        <?php else: ?>
            <div class="page-card-section page-section-body">
                <section class="content-inner-card">
                    <div class="profile-card-header">
                        <h2>Maklumat Lesen</h2>
                    </div>
                    <dl class="detail-list">
                        <?php foreach ($fields as $field): ?>
                            <div>
                                <dt><?= Html::encode($field['label']) ?></dt>
                                <dd>
                                    <?php if ($field['label'] === 'Fail' && ($license['fileUrl'] ?? '') !== ''): ?>
                                        <a class="grid-action" href="<?= Html::encode($license['fileUrl']) ?>" target="_blank" rel="noopener">
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
