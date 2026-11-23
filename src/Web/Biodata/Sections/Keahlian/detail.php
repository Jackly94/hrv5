<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var array{icno: string, name: string, staffNo: string, rows: list<array<string, string>>}|null $staff
 * @var array<string, string>|null $membership
 * @var string $backUrl
 * @var string $updateUrl
 */

$this->setTitle('Lihat Keahlian - ' . $applicationParams->name);
$fields = $membership === null ? [] : [
    ['label' => 'Nama Organisasi', 'value' => $membership['body']],
    ['label' => 'Peringkat', 'value' => $membership['level']],
    ['label' => 'No. Keahlian', 'value' => $membership['membershipNo']],
    ['label' => 'Taraf Keahlian', 'value' => $membership['membershipType']],
    ['label' => 'Jawatan', 'value' => $membership['designation']],
    ['label' => 'Tarikh Mula Menyertai', 'value' => $membership['startDate']],
    ['label' => 'Tarikh Tamat Menyertai', 'value' => $membership['endDate']],
    ['label' => 'Yuran Dikenakan', 'value' => $membership['fee']],
    ['label' => 'URL', 'value' => $membership['url']],
    ['label' => 'Status', 'value' => $membership['status']],
    ['label' => 'Berkaitan Kelayakan Perubatan', 'value' => $membership['isMedicalBody']],
    ['label' => 'Fail', 'value' => $membership['file']],
];
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1>Lihat Keahlian</h1>
                <p><?= $staff === null ? 'Kelab, persatuan, institusi dan kesatuan' : Html::encode($staff['name']) ?></p>
            </div>
            <?php if ($membership !== null): ?>
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

        <?php if ($staff === null || $membership === null): ?>
            <div class="profile-empty">Rekod keahlian tidak dijumpai.</div>
        <?php else: ?>
            <div class="page-card-section page-section-body">
                <section class="content-inner-card">
                    <div class="profile-card-header">
                        <h2>Maklumat Keahlian</h2>
                    </div>
                    <dl class="detail-list">
                        <?php foreach ($fields as $field): ?>
                            <div>
                                <dt><?= Html::encode($field['label']) ?></dt>
                                <dd>
                                    <?php if ($field['label'] === 'Fail' && ($membership['fileUrl'] ?? '') !== ''): ?>
                                        <a class="grid-action" href="<?= Html::encode($membership['fileUrl']) ?>" target="_blank" rel="noopener">
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
