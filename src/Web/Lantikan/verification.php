<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var array<string, mixed> $verification
 */

$this->setTitle('Semakan Lantikan');
$staff = $verification['staff'];
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div><h1>Semakan Lantikan</h1><p><?= Html::encode((string) ($staff['CONm'] ?? '-')) ?></p></div>
            <div class="profile-actions"><a class="button-link button-link-secondary" href="/lantikan/lantikan-belum-selesai"><i data-lucide="arrow-left"></i><span>Kembali</span></a></div>
        </div>
        <div class="page-card-section page-section-body">
            <div class="responsive-table">
                <table class="data-table">
                    <thead><tr><th>Jadual</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($verification['checks'] as $check): ?>
                        <tr>
                            <td><?= Html::encode((string) $check['table']) ?></td>
                            <td><span class="status-badge <?= $check['exists'] ? 'status-badge-success' : 'status-badge-danger' ?>"><?= $check['exists'] ? 'Ada' : 'Tiada' ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>
