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
 * @var array<string, mixed>|null $detail
 * @var array<string, string> $errors
 * @var string $remarks
 */

$title = $detail === null ? 'Maklumat Akaun Bank' : (string) $detail['title'];
$this->setTitle($title . ' - ' . $applicationParams->name);
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p>Pengesahan Data Staf</p>
            </div>
            <?php if ($detail !== null): ?>
                <div class="profile-actions">
                    <a class="button-link button-link-secondary" href="<?= Html::encode((string) $detail['backUrl']) ?>">
                        <i data-lucide="arrow-left"></i>
                        <span>Kembali</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($detail === null): ?>
            <div class="profile-empty">Rekod permohonan tidak dijumpai.</div>
        <?php else: ?>
            <div class="page-card-section page-section-body staff-overview-stack">
                <section class="content-inner-card">
                    <div class="profile-card-header">
                        <h2>Maklumat Akaun Bank</h2>
                    </div>
                    <dl class="detail-list">
                        <?php foreach ($detail['fields'] as $field): ?>
                            <div>
                                <dt><?= Html::encode((string) $field['label']) ?></dt>
                                <dd>
                                    <?php if (($field['kind'] ?? '') === 'file' && ($field['url'] ?? '') !== ''): ?>
                                        <a class="grid-action" href="<?= Html::encode((string) $field['url']) ?>" target="_blank" rel="noopener">
                                            <i data-lucide="file-text"></i>
                                            <span><?= Html::encode((string) $field['value']) ?></span>
                                        </a>
                                    <?php else: ?>
                                        <?= Html::encode((string) $field['value']) ?>
                                    <?php endif; ?>
                                </dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                </section>

                <?php if (($detail['write'] ?? false) === true): ?>
                    <section class="content-inner-card">
                        <div class="profile-card-header grid-card-header">
                            <h2>Catatan Tindakan</h2>
                        </div>
                        <?php if (isset($errors['general'])): ?>
                            <div class="record-form-alert">
                                <div class="alert alert-danger"><?= Html::encode($errors['general']) ?></div>
                            </div>
                        <?php endif; ?>
                        <form class="record-form" action="<?= Html::encode((string) $detail['action']) ?>" method="post" novalidate>
                            <?= $csrf->hiddenInput() ?>
                            <div class="record-form-grid">
                                <label class="grid-field grid-field-wide">
                                    <span>Catatan <b>*</b></span>
                                    <textarea name="remarks" rows="5" placeholder="Tulis catatan"><?= Html::encode($remarks) ?></textarea>
                                    <?php if (isset($errors['remarks'])): ?>
                                        <p class="field-error"><?= Html::encode($errors['remarks']) ?></p>
                                    <?php endif; ?>
                                </label>
                            </div>
                            <div class="record-form-actions">
                                <a class="button-link button-link-secondary" href="<?= Html::encode((string) $detail['backUrl']) ?>">
                                    <i data-lucide="arrow-left"></i>
                                    <span>Kembali</span>
                                </a>
                                <button class="grid-action grid-action-danger" type="submit" name="reject" value="reject">
                                    <i data-lucide="x"></i>
                                    <span>Tolak</span>
                                </button>
                                <?php if (($detail['mode'] ?? '') === 'approval'): ?>
                                    <button class="button-link-success" type="submit" name="approve" value="approve">
                                        <i data-lucide="check"></i>
                                        <span>Terima</span>
                                    </button>
                                <?php else: ?>
                                    <button class="button-link-success" type="submit" name="accept" value="accept">
                                        <i data-lucide="check"></i>
                                        <span>Sahkan</span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </section>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
