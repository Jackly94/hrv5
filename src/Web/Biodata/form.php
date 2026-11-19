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

$this->setTitle('Kemaskini Biodata - ' . $applicationParams->name);
$fieldError = static fn (string $field): string => isset($errors[$field])
    ? '<p class="field-error">' . Html::encode($errors[$field]) . '</p>'
    : '';
$selected = static fn (array $values, string $field, string $value): string => ((string) ($values[$field] ?? '')) === $value ? ' selected' : '';
$checked = static fn (array $values, string $field): string => ((string) ($values[$field] ?? '')) === '1' ? ' checked' : '';
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1>Kemaskini Biodata</h1>
                <p><?= Html::encode((string) $form['name']) ?></p>
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
                <div class="profile-card-header grid-card-header">
                    <h2>Maklumat Biodata</h2>
                </div>

                <?php if (isset($errors['general'])): ?>
                    <div class="record-form-alert">
                        <div class="alert alert-danger"><?= Html::encode($errors['general']) ?></div>
                    </div>
                <?php endif; ?>

                <form class="record-form" action="<?= Html::encode((string) $form['action']) ?>" method="post" novalidate>
                    <?= $csrf->hiddenInput() ?>

                    <div class="record-form-grid">
                        <?php foreach ($form['fields'] as $field): ?>
                            <?php
                            $name = (string) $field['name'];
                            $type = (string) ($field['type'] ?? 'text');
                            $label = (string) $field['label'];
                            $required = (bool) ($field['required'] ?? false);
                            $value = (string) (($form['values'][$name] ?? ''));
                            $options = $form['options'][(string) ($field['options'] ?? '')] ?? [];
                            $wide = in_array($type, ['static'], true) && in_array($name, ['CONm', 'COEmail'], true);
                            ?>
                            <label class="grid-field<?= $wide ? ' grid-field-wide' : '' ?>">
                                <span><?= Html::encode($label) ?><?= $required ? ' <b>*</b>' : '' ?></span>
                                <?php if ($type === 'select'): ?>
                                    <select name="<?= Html::encode($name) ?>" data-searchable-select>
                                        <option value="">Pilih <?= Html::encode($label) ?></option>
                                        <?php foreach ($options as $option): ?>
                                            <option value="<?= Html::encode($option['id']) ?>"<?= $selected($form['values'], $name, (string) $option['id']) ?>><?= Html::encode($option['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php elseif ($type === 'checkbox'): ?>
                                    <span class="checkbox-field">
                                        <input type="checkbox" name="<?= Html::encode($name) ?>" value="1"<?= $checked($form['values'], $name) ?>>
                                        <em>Tandakan jika ya / aktif</em>
                                    </span>
                                <?php elseif ($type === 'static'): ?>
                                    <input type="text" value="<?= Html::encode($value) ?>" disabled>
                                <?php else: ?>
                                    <input type="<?= Html::encode($type) ?>" name="<?= Html::encode($name) ?>" value="<?= Html::encode($value) ?>">
                                <?php endif; ?>
                                <?= $fieldError($name) ?>
                            </label>
                        <?php endforeach; ?>
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
