<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var string $title
 * @var list<array<string, mixed>> $leaveTypes
 * @var list<array<string, mixed>> $menuSections
 */

$this->setTitle($title . ' - ' . $applicationParams->name);
?>

<main class="cuti-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p>Permohonan baharu masih menggunakan Yii2 untuk borang penuh.</p>
            </div>
            <a class="button-link button-link-secondary" href="/cuti/individu/index">
                <i data-lucide="arrow-left"></i>
                <span>Kembali</span>
            </a>
        </div>

        <div class="page-section-body page-card-section">
            <?php require dirname(__DIR__) . '/Shared/menu.php'; ?>
        </div>

        <div class="page-section-body">
            <section class="dashboard-panel">
                <div class="dashboard-panel-header">
                    <h2>Jenis Cuti</h2>
                    <span><?= count($leaveTypes) ?> pilihan</span>
                </div>
                <div class="cuti-type-grid">
                    <?php foreach ($leaveTypes as $type): ?>
                        <?php $url = (string) ($type['url'] ?? $type['legacyRoute'] ?? '/cuti/individu/index'); ?>
                        <a href="<?= Html::encode($url) ?>"<?= str_starts_with($url, 'http') ? ' target="_blank" rel="noopener"' : '' ?>>
                            <i data-lucide="plane"></i>
                            <span><?= Html::encode((string) $type['name']) ?></span>
                            <em><?= !empty($type['local']) ? 'Yii3 form' : 'Yii2 form' ?></em>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </section>
</main>
