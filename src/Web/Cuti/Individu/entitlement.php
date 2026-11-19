<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var string $title
 * @var list<array<string, mixed>> $rows
 * @var array{before_adjustment: int, after_adjustment: int} $gcrTotals
 * @var list<array<string, mixed>> $menuSections
 * @var string $legacyUrl
 */

$this->setTitle($title . ' - ' . $applicationParams->name);
?>

<main class="cuti-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p>Kelayakan Cuti</p>
            </div>
            <a class="button-link button-link-secondary" href="/cuti/individu/index"><i data-lucide="arrow-left"></i><span>Kembali</span></a>
        </div>

        <div class="page-section-body page-card-section">
            <?php require dirname(__DIR__) . '/Shared/menu.php'; ?>
        </div>

        <div class="page-section-body">
            <section class="dashboard-panel">
                <div class="dashboard-panel-header">
                    <h2>Leave Entitlement</h2>
                    <a class="grid-action" href="<?= Html::encode($legacyUrl) ?>" target="_blank" rel="noopener"><i data-lucide="external-link"></i><span>Yii2</span></a>
                </div>
                <div class="cuti-inline-note">
                    <i data-lucide="info"></i>
                    <span>Lantikan Sementara, Lantikan Kontrak dan Lantikan Kontrak Jabatan tidak dikira sebagai lantikan pertama anda.</span>
                </div>
                <div class="responsive-table">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Start</th>
                                <th>End</th>
                                <th>BCTL</th>
                                <th>Entitlement</th>
                                <th>Total</th>
                                <th>CBTH</th>
                                <th>GCR</th>
                                <th>Discarded</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><?= Html::encode((string) ($row['layak_mula'] ?? '-')) ?></td>
                                    <td><?= Html::encode((string) ($row['layak_tamat'] ?? '-')) ?></td>
                                    <td><?= Html::encode((string) ($row['layak_bawa_lepas'] ?? '0')) ?></td>
                                    <td><?= Html::encode((string) ($row['layak_cuti'] ?? '0')) ?></td>
                                    <td><?= Html::encode((string) ($row['total_layak'] ?? '0')) ?></td>
                                    <td><?= Html::encode((string) ($row['layak_bawa_depan'] ?? '0')) ?></td>
                                    <td><?= Html::encode((string) ($row['layak_gcr'] ?? '0')) ?></td>
                                    <td><?= Html::encode((string) ($row['layak_hapus'] ?? '0')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td colspan="6"><strong>Total GCR Before Adjustment</strong></td>
                                <td><strong><?= Html::encode((string) $gcrTotals['before_adjustment']) ?></strong></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="6"><strong>Total GCR After Adjustment</strong></td>
                                <td><strong><?= Html::encode((string) $gcrTotals['after_adjustment']) ?></strong></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </section>
</main>
