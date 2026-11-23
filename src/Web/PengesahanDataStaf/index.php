<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var list<array<string, string|bool>> $sections
 */

$title = 'Pengesahan Data Staf';
$this->setTitle($title . ' - ' . $applicationParams->name);
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p>Pengurusan pengesahan dan kelulusan perubahan data kakitangan</p>
            </div>
            <div class="profile-actions">
                <a class="button-link button-link-secondary" href="/menu">
                    <i data-lucide="arrow-left"></i>
                    <span>Kembali</span>
                </a>
            </div>
        </div>

        <div class="page-card-section page-section-body">
            <section class="content-inner-card">
                <div class="profile-card-header grid-card-header">
                    <h2>Senarai Bahagian</h2>
                </div>

                <div class="table-scroll grid-table-wrap">
                    <table class="data-table staff-overview-table">
                        <thead>
                        <tr>
                            <th>Bahagian</th>
                            <th>Keterangan</th>
                            <th>Status</th>
                            <th>Tindakan</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($sections as $section): ?>
                            <?php $enabled = (bool) ($section['enabled'] ?? false); ?>
                            <tr>
                                <td class="grid-name"><?= Html::encode((string) $section['title']) ?></td>
                                <td><?= Html::encode((string) $section['subtitle']) ?></td>
                                <td><?= $enabled ? 'Aktif' : 'Belum Migrasi' ?></td>
                                <td>
                                    <?php if ($enabled): ?>
                                        <a class="grid-action" href="<?= Html::encode((string) $section['url']) ?>">
                                            <i data-lucide="<?= Html::encode((string) $section['icon']) ?>"></i>
                                            <span>Buka</span>
                                        </a>
                                    <?php else: ?>
                                        <span class="grid-count">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </section>
</main>
