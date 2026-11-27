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
 * @var string $message
 */

$title = (string) $form['title'];
$values = is_array($form['values'] ?? null) ? $form['values'] : [];
$staffOptions = is_array($form['staffOptions'] ?? null) ? $form['staffOptions'] : [];
$accessOptions = is_array($form['accessOptions'] ?? null) ? $form['accessOptions'] : [];
$statusOptions = is_array($form['statusOptions'] ?? null) ? $form['statusOptions'] : [];
$this->setTitle($title . ' - ' . $applicationParams->name);
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p>Pengurusan akses admin gambar profil</p>
            </div>
            <div class="profile-actions">
                <a class="button-link button-link-secondary" href="<?= Html::encode((string) $form['backUrl']) ?>">
                    <i data-lucide="arrow-left"></i>
                    <span>Kembali</span>
                </a>
            </div>
        </div>

        <div class="page-card-section page-section-body">
            <section class="content-inner-card">
                <div class="profile-card-header">
                    <h2><?= Html::encode((string) $form['sectionTitle']) ?></h2>
                </div>

                <?php if ($message !== '' || isset($errors['general'])): ?>
                    <div class="record-form-alert">
                        <div class="alert alert-danger"><?= Html::encode($errors['general'] ?? $message) ?></div>
                    </div>
                <?php endif; ?>

                <form class="record-form" action="<?= Html::encode((string) $form['action']) ?>" method="post" novalidate>
                    <?= $csrf->hiddenInput() ?>
                    <div class="record-form-grid">
                        <label class="grid-field grid-field-wide">
                            <span>Nama Admin <b>*</b></span>
                            <select name="icno" required>
                                <option value="">Sila Pilih</option>
                                <?php foreach ($staffOptions as $option): ?>
                                    <?php
                                    $value = (string) ($option['value'] ?? '');
                                    $label = (string) ($option['label'] ?? $value);
                                    ?>
                                    <option value="<?= Html::encode($value) ?>"<?= $value === (string) ($values['icno'] ?? '') ? ' selected' : '' ?>>
                                        <?= Html::encode($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['icno'])): ?>
                                <p class="field-error"><?= Html::encode($errors['icno']) ?></p>
                            <?php endif; ?>
                        </label>

                        <label class="grid-field">
                            <span>Jenis Akses <b>*</b></span>
                            <select name="access_type" required>
                                <?php foreach ($accessOptions as $value => $label): ?>
                                    <option value="<?= Html::encode((string) $value) ?>"<?= (string) $value === (string) ($values['access_type'] ?? '') ? ' selected' : '' ?>>
                                        <?= Html::encode((string) $label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['access_type'])): ?>
                                <p class="field-error"><?= Html::encode($errors['access_type']) ?></p>
                            <?php endif; ?>
                        </label>

                        <label class="grid-field">
                            <span>Status <b>*</b></span>
                            <select name="is_active" required>
                                <?php foreach ($statusOptions as $value => $label): ?>
                                    <option value="<?= Html::encode((string) $value) ?>"<?= (string) $value === (string) ($values['is_active'] ?? '') ? ' selected' : '' ?>>
                                        <?= Html::encode((string) $label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['is_active'])): ?>
                                <p class="field-error"><?= Html::encode($errors['is_active']) ?></p>
                            <?php endif; ?>
                        </label>
                    </div>

                    <div class="record-form-actions">
                        <a class="button-link button-link-secondary" href="<?= Html::encode((string) $form['backUrl']) ?>">
                            <i data-lucide="arrow-left"></i>
                            <span>Kembali</span>
                        </a>
                        <button class="button-link-success" type="submit">
                            <i data-lucide="save"></i>
                            <span>Simpan</span>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </section>
</main>
