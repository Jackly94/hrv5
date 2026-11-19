<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var array<string, string>|null $address
 * @var array{name: string, rows: list<array<string, string>>}|null $staff
 */

$this->setTitle('Lihat Alamat - ' . $applicationParams->name);
$fields = $address === null ? [] : [
    ['label' => 'Jenis Alamat', 'value' => $address['type']],
    ['label' => 'Negara', 'value' => $address['country']],
    ['label' => 'Negeri', 'value' => $address['state']],
    ['label' => 'Daerah', 'value' => $address['city']],
    ['label' => 'Alamat', 'value' => $address['addr1']],
    ['label' => 'Alamat 2', 'value' => $address['addr2']],
    ['label' => 'Alamat 3', 'value' => $address['addr3']],
    ['label' => 'Poskod', 'value' => $address['postcode']],
    ['label' => 'No. Telefon', 'value' => $address['phone']],
];
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1>Lihat Alamat</h1>
                <p><?= $staff === null ? 'Maklumat alamat kakitangan' : Html::encode($staff['name']) ?></p>
            </div>
            <?php if ($address !== null): ?>
                <div class="profile-actions">
                    <a class="button-link button-link-secondary" href="/alamat/view">
                        <i data-lucide="arrow-left"></i>
                        <span>Kembali</span>
                    </a>
                    <a class="button-link" href="/alamat/update?id=<?= Html::encode(rawurlencode($address['id'])) ?>">
                        <i data-lucide="pencil"></i>
                        <span>Kemaskini</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($address === null): ?>
            <div class="profile-empty">Rekod alamat tidak dijumpai.</div>
        <?php else: ?>
            <div class="page-card-section page-section-body">
                <section class="content-inner-card">
                    <div class="profile-card-header">
                        <h2>Maklumat Alamat</h2>
                    </div>
                    <dl class="detail-list">
                        <?php foreach ($fields as $field): ?>
                            <div>
                                <dt><?= Html::encode($field['label']) ?></dt>
                                <dd><?= Html::encode($field['value']) ?></dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                </section>
            </div>
        <?php endif; ?>
    </section>
</main>
