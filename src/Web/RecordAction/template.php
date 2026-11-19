<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var array{title: string, module: string, mode: string, backUrl: string} $page
 * @var string $id
 */

$this->setTitle($page['title'] . ' - ' . $applicationParams->name);
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($page['title']) ?></h1>
                <p>Rekod Peribadi</p>
            </div>
            <div class="profile-actions">
                <a class="button-link button-link-secondary" href="<?= Html::encode($page['backUrl']) ?>">
                    <i data-lucide="arrow-left"></i>
                    <span>Kembali</span>
                </a>
            </div>
        </div>

        <div class="page-card-section page-section-body">
            <section class="content-inner-card">
                <div class="profile-card-header">
                    <h2><?= Html::encode($page['module']) ?></h2>
                </div>
                <dl class="detail-list">
                    <div>
                        <dt>Status</dt>
                        <dd>Halaman tindakan ini sudah berada dalam Yii3 dan tidak lagi membuka Yii2.</dd>
                    </div>
                    <?php if ($id !== ''): ?>
                        <div>
                            <dt>ID Rekod</dt>
                            <dd><?= Html::encode($id) ?></dd>
                        </div>
                    <?php endif; ?>
                    <div>
                        <dt>Tindakan</dt>
                        <dd><?= Html::encode($page['mode']) ?></dd>
                    </div>
                </dl>
            </section>
        </div>
    </section>
</main>
