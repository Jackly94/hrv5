<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use App\Shared\LegacyUrl;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var list<array<string, mixed>> $rows
 * @var bool $authorized
 * @var list<array<string, mixed>> $menuSections
 * @var array<string, mixed> $query
 */

$this->setTitle('Senarai Permohonan HSO - Cuti - ' . $applicationParams->name);
$value = static fn (string $key): string => isset($query[$key]) && is_scalar($query[$key]) ? (string) $query[$key] : '';
$rowNumber = 1;
?>

<main class="cuti-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div><h1>Senarai Permohonan HSO</h1><p>Cuti</p></div>
            <a class="button-link button-link-secondary" href="/cuti/individu/index"><i data-lucide="arrow-left"></i><span>Kembali</span></a>
        </div>
        <div class="page-section-body page-card-section"><?php require dirname(__DIR__) . '/Shared/menu.php'; ?></div>
        <div class="page-section-body page-card-section">
            <section class="content-inner-card attendance-standard-toolbar">
                <div class="attendance-standard-title">
                    <span class="attendance-standard-icon attendance-icon-info" aria-hidden="true"><i data-lucide="list"></i></span>
                    <div><h2>HSO</h2><p><?= Html::encode($authorized ? 'Permohonan HSO jabatan ketua/PP semasa.' : 'Access is not available for this account.') ?></p></div>
                </div>
                <?php if ($authorized): ?>
                    <form class="attendance-standard-filter" method="get" action="/cuti/pegawai/report">
                        <div class="attendance-standard-fields">
                            <label>Year <input type="text" name="year" value="<?= Html::encode($value('year') ?: date('Y')) ?>"></label>
                            <label>Status <input type="text" name="status" value="<?= Html::encode($value('status')) ?>"></label>
                        </div>
                        <div class="attendance-standard-actions"><button class="button-link" type="submit"><i data-lucide="search"></i><span>Search</span></button></div>
                    </form>
                <?php else: ?><div class="attendance-standard-count"><span>Records</span><strong>0</strong></div><?php endif; ?>
            </section>
        </div>
        <div class="page-section-body page-card-section">
            <section class="content-inner-card staff-page-panel attendance-compact-panel">
                <?php if (!$authorized): ?><div class="profile-empty">Anda tidak mempunyai akses untuk paparan ini.</div>
                <?php elseif ($rows === []): ?><div class="profile-empty">Tiada rekod ditemui.</div>
                <?php else: ?>
                    <div class="responsive-table"><table class="data-table attendance-compact-table">
                        <thead><tr><th>Bil</th><th>Nama</th><th>Jabatan</th><th>Jenis Cuti</th><th>Tarikh</th><th>Tempoh</th><th>Status</th><th>Tindakan</th></tr></thead>
                        <tbody><?php foreach ($rows as $row): ?><tr>
                            <td><?= Html::encode((string) $rowNumber++) ?></td>
                            <td><?= Html::encode((string) ($row['staff_name'] ?? '-')) ?><br><small><?= Html::encode((string) ($row['staff_no'] ?? '-')) ?></small></td>
                            <td><?= Html::encode((string) ($row['department_label'] ?? '-')) ?></td>
                            <td><?= Html::encode((string) ($row['jenis_cuti_label'] ?? '-')) ?></td>
                            <td><?= Html::encode((string) ($row['date_label'] ?? '-')) ?></td>
                            <td><?= Html::encode((string) ($row['tempoh'] ?? '-')) ?></td>
                            <td><span class="status-badge"><?= Html::encode((string) ($row['status_label'] ?? '-')) ?></span></td>
                            <td><a class="grid-action" href="<?= Html::encode(LegacyUrl::to('cuti/pegawai/report') . '?id=58&year=' . rawurlencode($value('year') ?: date('Y'))) ?>" target="_blank" rel="noopener"><i data-lucide="external-link"></i><span>Yii2</span></a></td>
                        </tr><?php endforeach; ?></tbody>
                    </table></div>
                <?php endif; ?>
                <div class="grid-count-footer"><span class="grid-count">Papar <?= Html::encode((string) count($rows)) ?> rekod</span></div>
            </section>
        </div>
    </section>
</main>
