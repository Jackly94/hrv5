<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var array<string, mixed>|null $detail
 */

$title = $detail === null ? 'Lihat Rekod' : 'Lihat ' . $detail['title'];
$this->setTitle($title . ' - ' . $applicationParams->name);
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p><?= $detail === null ? 'Maklumat dan rekod kakitangan' : Html::encode($detail['name']) ?></p>
            </div>
            <?php if ($detail !== null): ?>
                <div class="profile-actions">
                    <a class="button-link button-link-secondary" href="<?= Html::encode($detail['backUrl']) ?>">
                        <i data-lucide="arrow-left"></i>
                        <span>Kembali</span>
                    </a>
                    <a class="button-link" href="<?= Html::encode($detail['updateUrl']) ?>">
                        <i data-lucide="pencil"></i>
                        <span>Kemaskini</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($detail === null): ?>
            <div class="profile-empty">Rekod tidak dijumpai.</div>
        <?php else: ?>
            <div class="page-card-section page-section-body">
                <section class="content-inner-card">
                    <div class="profile-card-header">
                        <h2><?= Html::encode($detail['title']) ?></h2>
                    </div>
                    <dl class="detail-list">
                        <?php foreach ($detail['fields'] as $field): ?>
                            <div>
                                <dt><?= Html::encode($field['label']) ?></dt>
                                <dd><?= Html::encode($field['value']) ?></dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                </section>
            </div>
        <?php endif; ?>
    </section>
</main>
