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
 * @var string $key
 * @var 'create'|'update' $mode
 * @var array<string, mixed>|null $form
 * @var array<string, string> $values
 * @var array<string, string> $errors
 */

$title = $form === null
    ? 'Rekod Tidak Dijumpai'
    : ($mode === 'update' ? 'Kemaskini ' : 'Tambah ') . $form['title'];
$this->setTitle($title . ' - ' . $applicationParams->name);

$fieldError = static fn (string $field): string => isset($errors[$field])
    ? '<p class="field-error">' . Html::encode($errors[$field]) . '</p>'
    : '';
$selected = static fn (string $field, string $value): string => ($values[$field] ?? '') === $value ? ' selected' : '';
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p><?= $form === null ? 'Maklumat dan rekod kakitangan' : Html::encode($form['name']) ?></p>
            </div>
            <div class="profile-actions">
                <a class="button-link button-link-secondary" href="<?= Html::encode($form['backUrl'] ?? '/biodata/userview') ?>">
                    <i data-lucide="arrow-left"></i>
                    <span>Kembali</span>
                </a>
            </div>
        </div>

        <?php if ($form === null): ?>
            <div class="profile-empty"><?= Html::encode($errors['general'] ?? 'Rekod tidak dijumpai.') ?></div>
        <?php else: ?>
            <div class="page-card-section page-section-body">
                <section class="content-inner-card">
                    <div class="profile-card-header grid-card-header">
                        <h2><?= Html::encode($form['title']) ?></h2>
                    </div>

                    <?php if (isset($errors['general'])): ?>
                        <div class="record-form-alert">
                            <div class="alert alert-danger"><?= Html::encode($errors['general']) ?></div>
                        </div>
                    <?php endif; ?>

                    <form class="record-form" action="<?= Html::encode($form['action']) ?>" method="post" novalidate>
                        <?= $csrf->hiddenInput() ?>

                        <div class="record-form-grid">
                            <?php foreach ($form['fields'] as $field): ?>
                                <?php
                                $name = (string) $field['name'];
                                $type = (string) $field['type'];
                                $isWide = in_array($type, ['textarea'], true);
                                ?>
                                <label class="grid-field<?= $isWide ? ' grid-field-wide' : '' ?>">
                                    <span>
                                        <?= Html::encode((string) $field['label']) ?>
                                        <?php if (($field['required'] ?? false) === true): ?><b>*</b><?php endif; ?>
                                    </span>

                                    <?php if ($type === 'select'): ?>
                                        <select name="<?= Html::encode($name) ?>" data-searchable-select>
                                            <option value="">Pilih <?= Html::encode((string) $field['label']) ?></option>
                                            <?php foreach (($field['optionsList'] ?? []) as $option): ?>
                                                <option value="<?= Html::encode($option['id']) ?>"<?= $selected($name, $option['id']) ?>><?= Html::encode($option['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php elseif ($type === 'textarea'): ?>
                                        <textarea name="<?= Html::encode($name) ?>" rows="4"><?= Html::encode($values[$name] ?? '') ?></textarea>
                                    <?php else: ?>
                                        <input
                                            type="<?= Html::encode($type === 'date' ? 'date' : 'text') ?>"
                                            name="<?= Html::encode($name) ?>"
                                            value="<?= Html::encode($values[$name] ?? '') ?>"
                                            <?= isset($field['maxlength']) ? ' maxlength="' . Html::encode((string) $field['maxlength']) . '"' : '' ?>
                                        >
                                    <?php endif; ?>
                                    <?= $fieldError($name) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="record-form-actions">
                            <a class="button-link button-link-secondary" href="<?= Html::encode($form['backUrl']) ?>">
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
        <?php endif; ?>
    </section>
</main>
