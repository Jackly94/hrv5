<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var array<string, mixed> $search
 */

$this->setTitle('Lantikan Semula Staf UMS');
$filters = $search['filters'];
$options = $search['options'];
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1>Lantikan Semula Staf UMS</h1>
                <p>Carian read-only</p>
            </div>
            <div class="profile-actions">
                <a class="button-link button-link-secondary" href="/lantikan/index"><i data-lucide="arrow-left"></i><span>Kembali</span></a>
            </div>
        </div>

        <div class="page-card-section page-section-body">
            <form class="content-inner-card" method="get">
                <div class="record-form-grid">
                    <label class="grid-field">
                        <span>Jenis Carian</span>
                        <select name="mode">
                            <?php foreach (['icno' => 'ICNO', 'name' => 'Nama', 'staffNo' => 'UMSPER'] as $id => $label): ?>
                                <option value="<?= Html::encode($id) ?>"<?= (string) ($filters['mode'] ?? '') === $id ? ' selected' : '' ?>><?= Html::encode($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="grid-field"><span>Carian</span><input name="q" value="<?= Html::encode((string) ($filters['q'] ?? '')) ?>"></label>
                    <label class="grid-field">
                        <span>Jabatan</span>
                        <select name="department"><option value="">Semua</option><?php foreach ($options['departments'] as $option): ?><option value="<?= Html::encode((string) $option['id']) ?>"<?= (string) ($filters['department'] ?? '') === (string) $option['id'] ? ' selected' : '' ?>><?= Html::encode((string) $option['name']) ?></option><?php endforeach; ?></select>
                    </label>
                    <label class="grid-field">
                        <span>Status Lantikan</span>
                        <select name="appointmentStatus"><option value="">Semua</option><?php foreach ($options['appointmentStatuses'] as $option): ?><option value="<?= Html::encode((string) $option['id']) ?>"<?= (string) ($filters['appointmentStatus'] ?? '') === (string) $option['id'] ? ' selected' : '' ?>><?= Html::encode((string) $option['name']) ?></option><?php endforeach; ?></select>
                    </label>
                </div>
                <button class="button-link" type="submit"><i data-lucide="search"></i><span>Cari</span></button>
            </form>
        </div>

        <div class="page-card-section page-section-body">
            <div class="responsive-table">
                <table class="data-table">
                    <thead><tr><th>ICNO</th><th>UMSPER</th><th>Nama</th><th>Jabatan</th><th>Status</th><th>Tindakan</th></tr></thead>
                    <tbody>
                    <?php foreach ($search['rows'] as $row): ?>
                        <tr>
                            <td><?= Html::encode((string) $row['ICNO']) ?></td>
                            <td><?= Html::encode((string) ($row['COOldID'] ?? '-')) ?></td>
                            <td><?= Html::encode(ucwords(strtolower((string) $row['CONm']))) ?></td>
                            <td><?= Html::encode((string) ($row['department_name'] ?? '-')) ?></td>
                            <td><?= Html::encode((string) ($row['ApmtStatusNm'] ?? '-')) ?></td>
                            <td><a class="grid-action" href="<?= Html::encode((string) $row['legacyUrl']) ?>" target="_blank" rel="noopener"><i data-lucide="external-link"></i><span>Yii2</span></a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>
