<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var array<string, mixed> $incomplete
 */

$this->setTitle('Lantikan Belum Selesai');
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div><h1>Lantikan Belum Selesai</h1><p>Semakan read-only</p></div>
            <div class="profile-actions"><a class="button-link button-link-secondary" href="/lantikan/index"><i data-lucide="arrow-left"></i><span>Kembali</span></a></div>
        </div>
        <div class="page-card-section page-section-body">
            <div class="responsive-table">
                <table class="data-table">
                    <thead><tr><th>ICNO</th><th>UMSPER</th><th>Nama</th><th>Tindakan</th></tr></thead>
                    <tbody>
                    <?php foreach ($incomplete['rows'] as $row): ?>
                        <tr>
                            <td><?= Html::encode((string) ($row['ICNO'] ?? '-')) ?></td>
                            <td><?= Html::encode((string) ($row['COOldID'] ?? $row['Staff_Id'] ?? '-')) ?></td>
                            <td><?= Html::encode((string) ($row['CONm'] ?? '-')) ?></td>
                            <td><a class="grid-action" href="/lantikan/view-lantikan?id=<?= Html::encode((string) ($row['ICNO'] ?? '')) ?>"><i data-lucide="eye"></i><span>Semak</span></a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>
