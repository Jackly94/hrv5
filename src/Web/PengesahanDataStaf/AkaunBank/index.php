<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var array<string, mixed> $dashboard
 */

$title = (string) ($dashboard['title'] ?? 'Pengesahan Akaun Bank');
$this->setTitle($title . ' - ' . $applicationParams->name);
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p>Aliran pengesahan dan kelulusan permohonan penukaran akaun bank gaji</p>
            </div>
            <div class="profile-actions">
                <a class="button-link button-link-secondary" href="/pengesahan-data-staf/index">
                    <i data-lucide="arrow-left"></i>
                    <span>Kembali</span>
                </a>
            </div>
        </div>

        <div class="page-card-section page-section-body">
            <section class="content-inner-card">
                <div class="profile-card-header grid-card-header">
                    <h2>Akaun Bank Gaji</h2>
                </div>

                <div class="table-scroll grid-table-wrap">
                    <table class="data-table staff-overview-table">
                        <thead>
                        <tr>
                            <th>Proses</th>
                            <th>Keterangan</th>
                            <th>Menunggu</th>
                            <th>Tindakan</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach (($dashboard['cards'] ?? []) as $card): ?>
                            <tr>
                                <td class="grid-name"><?= Html::encode((string) $card['title']) ?></td>
                                <td><?= Html::encode((string) $card['subtitle']) ?></td>
                                <td><?= Html::encode((string) $card['count']) ?></td>
                                <td>
                                    <a class="grid-action" href="<?= Html::encode((string) $card['url']) ?>">
                                        <i data-lucide="<?= Html::encode((string) $card['icon']) ?>"></i>
                                        <span>Buka</span>
                                    </a>
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
