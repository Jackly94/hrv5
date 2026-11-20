<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var array<string, mixed> $result
 */

$staff = is_array($result['staff'] ?? null) ? $result['staff'] : [];
$checks = is_array($result['checks'] ?? null) ? $result['checks'] : [];
$postCommit = is_array($result['postCommit'] ?? null) ? $result['postCommit'] : [];

$this->setTitle('Semakan Lantik Staf Baru');
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1>Semakan Lantik Staf Baru</h1>
                <p><?= Html::encode((string) ($staff['CONm'] ?? '-')) ?></p>
            </div>
            <div class="profile-actions">
                <a class="button-link button-link-secondary" href="/lantikan/index"><i data-lucide="arrow-left"></i><span>Kembali</span></a>
                <a class="button-link button-link-secondary" href="/lantikan/lantik-staf-baru"><i data-lucide="user-plus"></i><span>Staf Baru</span></a>
            </div>
        </div>

        <div class="page-card-section page-section-body">
            <div class="content-inner-card">
                <div class="dashboard-panel-header">
                    <h2>Rekod Teras</h2>
                    <span><?= Html::encode((string) ($staff['COOldID'] ?? '-')) ?></span>
                </div>
                <div class="responsive-table">
                    <table class="data-table">
                        <thead><tr><th>Jadual</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($checks as $check): ?>
                            <tr>
                                <td><?= Html::encode((string) ($check['table'] ?? '-')) ?></td>
                                <td><?= ($check['exists'] ?? false) ? 'Wujud' : 'Belum wujud / tidak berkaitan' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="content-inner-card">
                <div class="dashboard-panel-header">
                    <h2>Post-Commit</h2>
                    <span>Semakan</span>
                </div>
                <div class="responsive-table">
                    <table class="data-table">
                        <thead><tr><th>Item</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($postCommit as $item): ?>
                            <tr>
                                <td><?= Html::encode((string) ($item['label'] ?? '-')) ?></td>
                                <td><?= Html::encode((string) ($item['status'] ?? '-')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="record-form-actions">
                    <a class="button-link button-link-secondary" href="<?= Html::encode((string) ($result['allocationUrl'] ?? '#')) ?>" target="_blank" rel="noopener">
                        <i data-lucide="external-link"></i>
                        <span>Allocation Profile Yii2</span>
                    </a>
                    <a class="button-link button-link-secondary" href="/lantikan/view-lantikan?id=<?= Html::encode((string) ($staff['ICNO'] ?? '')) ?>">
                        <i data-lucide="list-checks"></i>
                        <span>Semak Jadual</span>
                    </a>
                </div>
            </div>
        </div>
    </section>
</main>
