<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;
use Yiisoft\Yii\View\Renderer\Csrf;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var Csrf $csrf
 * @var string $title
 * @var list<array<string, mixed>> $rows
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
                <p>Menunggu Persetujuan Pengganti</p>
            </div>
            <div class="profile-actions">
                <a class="button-link button-link-secondary" href="/cuti/individu/index"><i data-lucide="arrow-left"></i><span>Kembali</span></a>
            </div>
        </div>

        <div class="page-section-body page-card-section">
            <?php require dirname(__DIR__) . '/Shared/menu.php'; ?>
        </div>

        <div class="page-section-body">
            <section class="dashboard-panel">
                <div class="dashboard-panel-header">
                    <h2>Pending for Substitute Approval</h2>
                    <a class="grid-action" href="<?= Html::encode($legacyUrl) ?>" target="_blank" rel="noopener"><i data-lucide="external-link"></i><span>Yii2</span></a>
                </div>

                <?php if ($rows === []): ?>
                    <div class="profile-empty">Tiada rekod menunggu persetujuan.</div>
                <?php else: ?>
                    <div class="responsive-table">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Tarikh Bercuti</th>
                                    <th>Jenis Cuti</th>
                                    <th>Catatan</th>
                                    <th>Tindakan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td><?= Html::encode((string) ($row['staff_name'] ?? '-')) ?></td>
                                        <td><?= Html::encode((string) ($row['date_label'] ?? '-')) ?></td>
                                        <td><?= Html::encode((string) ($row['jenis_cuti_label'] ?? '-')) ?></td>
                                        <td><?= Html::encode((string) ($row['remark'] ?? '-')) ?></td>
                                        <td>
                                            <form method="post" action="/cuti/individu/agree?id=<?= Html::encode((string) $row['id']) ?>" data-confirm-submit="Adakah anda pasti?">
                                                <?= $csrf->hiddenInput() ?>
                                                <input type="hidden" name="id" value="<?= Html::encode((string) $row['id']) ?>">
                                                <button class="button-link-success" type="submit"><i data-lucide="check-circle-2"></i><span>Agree</span></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </section>
</main>
