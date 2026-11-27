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
 * @var array<string, mixed> $form
 * @var array<string, string> $errors
 */

$title = (string) ($form['pageTitle'] ?? 'Muat Naik Gambar Kakitangan');
$sectionTitle = (string) ($form['sectionTitle'] ?? 'Muat Naik Gambar Profil');
$submitLabel = (string) ($form['submitLabel'] ?? 'Hantar');
$guide = is_array($form['guide'] ?? null) ? $form['guide'] : null;
$this->setTitle($title . ' - ' . $applicationParams->name);
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p><?= Html::encode((string) $form['name']) ?></p>
            </div>
            <div class="profile-actions">
                <a class="button-link button-link-secondary" href="<?= Html::encode((string) $form['backUrl']) ?>">
                    <i data-lucide="arrow-left"></i>
                    <span>Kembali</span>
                </a>
            </div>
        </div>

        <div class="profile-hero page-card-section">
            <img class="profile-photo" src="<?= Html::encode((string) $form['currentPhoto']) ?>" alt="Gambar profil semasa">
            <div class="profile-identity">
                <h2><?= Html::encode((string) $form['title']) ?></h2>
                <p>Gambar JPEG sahaja, saiz maksimum 2MB.</p>
            </div>
        </div>

        <div class="page-card-section page-section-body staff-overview-stack">
            <section class="content-inner-card">
                <div class="profile-card-header">
                    <h2>Maklumat Kakitangan</h2>
                </div>
                <dl class="detail-list">
                    <?php foreach (($form['staffFields'] ?? []) as $field): ?>
                        <div>
                            <dt><?= Html::encode((string) $field['label']) ?></dt>
                            <dd><?= Html::encode((string) $field['value']) ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            </section>

            <section class="content-inner-card">
                <div class="profile-card-header grid-card-header">
                    <h2><?= Html::encode($sectionTitle) ?></h2>
                    <?php if ($guide !== null): ?>
                        <button class="grid-action" type="button" data-profile-guide-open>
                            <i data-lucide="file-text"></i>
                            <span><?= Html::encode((string) ($guide['label'] ?? 'Panduan')) ?></span>
                        </button>
                    <?php endif; ?>
                </div>

                <?php if (isset($errors['general'])): ?>
                    <div class="record-form-alert">
                        <div class="alert alert-danger"><?= Html::encode($errors['general']) ?></div>
                    </div>
                <?php endif; ?>

                <form class="record-form" action="<?= Html::encode((string) $form['action']) ?>" method="post" enctype="multipart/form-data" novalidate>
                    <?= $csrf->hiddenInput() ?>
                    <div class="record-form-grid">
                        <?php foreach ($form['fields'] as $field): ?>
                            <?php
                            $name = (string) $field['name'];
                            $label = (string) $field['label'];
                            $required = (bool) ($field['required'] ?? false);
                            ?>
                            <label class="grid-field grid-field-wide">
                                <span><?= Html::encode($label) ?><?= $required ? ' <b>*</b>' : '' ?></span>
                                <input type="file" name="<?= Html::encode($name) ?>" accept="<?= Html::encode((string) ($field['accept'] ?? 'image/jpeg,.jpeg,.jpg')) ?>" data-profile-upload-input>
                                <em class="form-help"><?= Html::encode((string) ($field['help'] ?? 'JPEG sahaja.')) ?></em>
                                <div class="profile-upload-preview" data-profile-upload-preview hidden>
                                    <span>Semak Gambar</span>
                                    <img src="" alt="Pratonton gambar dipilih" data-profile-upload-preview-image>
                                </div>
                                <?php if (isset($errors[$name])): ?>
                                    <p class="field-error"><?= Html::encode($errors[$name]) ?></p>
                                <?php endif; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="record-form-actions">
                        <a class="button-link button-link-secondary" href="<?= Html::encode((string) $form['backUrl']) ?>">
                            <i data-lucide="arrow-left"></i>
                            <span>Kembali</span>
                        </a>
                        <button class="button-link-success" type="submit">
                            <i data-lucide="send"></i>
                            <span><?= Html::encode($submitLabel) ?></span>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </section>

    <?php if ($guide !== null): ?>
        <div class="profile-guide-modal" data-profile-guide-modal hidden>
            <div class="profile-guide-dialog" role="dialog" aria-modal="true" aria-labelledby="profile-guide-title">
                <div class="profile-guide-header">
                    <div class="profile-guide-copy">
                        <h2 id="profile-guide-title"><?= Html::encode((string) $guide['title']) ?></h2>
                        <p><?= Html::encode((string) $guide['description']) ?></p>
                    </div>
                    <button class="profile-guide-close" type="button" data-profile-guide-close aria-label="Tutup panduan">
                        <i data-lucide="x"></i>
                    </button>
                </div>
                <div class="profile-guide-body">
                    <iframe
                        class="profile-guide-frame"
                        src="<?= Html::encode((string) $guide['url']) ?>"
                        title="<?= Html::encode((string) $guide['title']) ?>"
                    ></iframe>
                    <p class="profile-guide-fallback">
                        <a href="<?= Html::encode((string) $guide['url']) ?>" target="_blank" rel="noopener">Buka panduan dalam tab baharu</a>
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>
