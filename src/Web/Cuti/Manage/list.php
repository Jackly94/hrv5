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
 */

$this->setTitle('Manage(Surat) - Cuti - ' . $applicationParams->name);
$rowNumber = 1;
?>

<main class="cuti-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div><h1>Manage(Surat)</h1><p>Cuti</p></div>
            <a class="button-link button-link-secondary" href="/cuti/individu/index"><i data-lucide="arrow-left"></i><span>Kembali</span></a>
        </div>
        <div class="page-section-body page-card-section"><?php require dirname(__DIR__) . '/Shared/menu.php'; ?></div>
        <div class="page-section-body page-card-section">
            <section class="content-inner-card attendance-standard-toolbar">
                <div class="attendance-standard-title">
                    <span class="attendance-standard-icon attendance-icon-success" aria-hidden="true"><i data-lucide="file-cog"></i></span>
                    <div><h2>Sector HR</h2><p><?= Html::encode($authorized ? 'Senarai pengurusan surat cuti.' : 'Access is not available for this account.') ?></p></div>
                </div>
                <div class="attendance-standard-count"><span>Records</span><strong><?= Html::encode((string) count($rows)) ?></strong></div>
            </section>
        </div>
        <div class="page-section-body page-card-section">
            <section class="content-inner-card staff-page-panel attendance-compact-panel">
                <?php if (!$authorized): ?><div class="profile-empty">Anda tidak mempunyai akses untuk paparan ini.</div>
                <?php elseif ($rows === []): ?><div class="profile-empty">Tiada rekod ditemui.</div>
                <?php else: ?>
                    <div class="responsive-table"><table class="data-table attendance-compact-table">
                        <thead><tr><th>Bil</th><th>Nama</th><th>Staff No</th><th>User</th><th>Level</th><th>Title</th><th>Tarikh AP</th><th>Status</th><th>Tindakan</th></tr></thead>
                        <tbody><?php foreach ($rows as $row): ?><tr>
                            <td><?= Html::encode((string) $rowNumber++) ?></td>
                            <td><?= Html::encode((string) ($row['staff_name'] ?? '-')) ?></td>
                            <td><?= Html::encode((string) ($row['staff_no'] ?? '-')) ?></td>
                            <td><?= Html::encode((string) ($row['user'] ?? '-')) ?></td>
                            <td><?= Html::encode((string) ($row['level_label'] ?? '-')) ?></td>
                            <td><?= Html::encode((string) ($row['title'] ?? '-')) ?></td>
                            <td><?= Html::encode((string) ($row['tarikh_AP'] ?? '-')) ?></td>
                            <td><span class="status-badge"><?= Html::encode((string) ($row['status_label'] ?? '-')) ?></span></td>
                            <td><a class="grid-action" href="<?= Html::encode(LegacyUrl::to('manage/edit') . '?id=' . rawurlencode((string) ($row['id'] ?? ''))) ?>" target="_blank" rel="noopener"><i data-lucide="pencil"></i><span>Yii2</span></a></td>
                        </tr><?php endforeach; ?></tbody>
                    </table></div>
                <?php endif; ?>
                <div class="grid-count-footer"><span class="grid-count">Papar <?= Html::encode((string) count($rows)) ?> rekod</span></div>
            </section>
        </div>
    </section>
</main>
