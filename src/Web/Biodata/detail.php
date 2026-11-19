<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var array<string, mixed>|null $details
 * @var bool $isOwnDetail
 */

$this->setTitle('Lihat Biodata - ' . $applicationParams->name);
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1>Lihat Biodata</h1>
                <p><?= $details === null ? 'Maklumat biodata' : Html::encode($details['name']) ?></p>
            </div>
            <div class="profile-actions">
                <?php if ($isOwnDetail): ?>
                    <a class="button-link button-link-secondary" href="/biodata/userview">
                        <i data-lucide="arrow-left"></i>
                        <span>Kembali</span>
                    </a>
                    <a class="button-link" href="/biodata/kemaskini">
                        <i data-lucide="pencil"></i>
                        <span>Kemaskini</span>
                    </a>
                <?php elseif ($details !== null): ?>
                    <a class="button-link button-link-secondary" href="/biodata/adminview?id=<?= Html::encode($details['icno']) ?>">
                        <i data-lucide="arrow-left"></i>
                        <span>Kembali</span>
                    </a>
                    <a class="button-link" href="/biodata/kemaskinikakitangan?id=<?= Html::encode($details['icno']) ?>">
                        <i data-lucide="pencil"></i>
                        <span>Kemaskini</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($details === null): ?>
            <div class="profile-empty">Rekod biodata tidak dijumpai.</div>
        <?php else: ?>
            <?php /** @var list<array{label: string, value: string}> $fields */
            $fields = $details['fields']; ?>
            <div class="page-card-section page-section-body">
                <section class="content-inner-card">
                    <div class="profile-card-header">
                        <h2>Maklumat Biodata</h2>
                    </div>
                    <dl class="detail-list">
                        <?php foreach ($fields as $field): ?>
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
