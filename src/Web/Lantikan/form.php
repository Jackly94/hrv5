<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\View\WebView;
use Yiisoft\Yii\View\Renderer\Csrf;

/**
 * @var WebView $this
 * @var Csrf $csrf
 * @var array<string, mixed> $form
 * @var array{success: bool, values: array<string, string>, errors: array<string, string>, proposed: list<string>, plan?: array<string, mixed>}|null $result
 */

$this->setTitle((string) $form['title']);
$values = $form['values'] ?? [];
$errors = $result['errors'] ?? [];
$fieldError = static fn (string $field): string => isset($errors[$field])
    ? '<p class="field-error">' . Html::encode($errors[$field]) . '</p>'
    : '';
$selected = static fn (string $field, string $value): string => (string) ($values[$field] ?? '') === $value ? ' selected' : '';
$checked = static fn (string $field): string => (string) ($values[$field] ?? '') === '1' ? ' checked' : '';
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode((string) $form['title']) ?></h1>
                <p>Mod dry-run Yii3</p>
            </div>
            <div class="profile-actions">
                <a class="button-link button-link-secondary" href="/lantikan/index">
                    <i data-lucide="arrow-left"></i>
                    <span>Kembali</span>
                </a>
                <a class="button-link button-link-secondary" href="<?= Html::encode((string) $form['legacyUrl']) ?>" target="_blank" rel="noopener">
                    <i data-lucide="external-link"></i>
                    <span>Yii2</span>
                </a>
            </div>
        </div>

        <div class="page-card-section page-section-body">
            <div class="content-inner-card">
                <div class="dashboard-panel-header">
                    <h2>Semakan Borang</h2>
                    <span><?= $form['adEnabled'] ? 'AD API enabled' : 'AD API disabled' ?></span>
                </div>

                <?php if ($result !== null): ?>
                    <div class="profile-empty">
                        <?= $result['success'] ? 'Dry-run berjaya. Tiada rekod disimpan.' : 'Sila semak ralat borang.' ?>
                    </div>
                    <?php if ($result['proposed'] !== []): ?>
                        <ul>
                            <?php foreach ($result['proposed'] as $line): ?>
                                <li><?= Html::encode($line) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <?php if (isset($result['plan']) && is_array($result['plan'])): ?>
                        <?php
                        $summary = is_array($result['plan']['summary'] ?? null) ? $result['plan']['summary'] : [];
                        $coreTables = is_array($result['plan']['coreTables'] ?? null) ? $result['plan']['coreTables'] : [];
                        $postCommit = is_array($result['plan']['postCommit'] ?? null) ? $result['plan']['postCommit'] : [];
                        $blockedSideEffects = is_array($result['plan']['blockedSideEffects'] ?? null) ? $result['plan']['blockedSideEffects'] : [];
                        ?>
                        <div class="responsive-table">
                            <table class="data-table">
                                <thead><tr><th>Item</th><th>Nilai</th></tr></thead>
                                <tbody>
                                <?php foreach ($summary as $key => $value): ?>
                                    <tr><td><?= Html::encode((string) $key) ?></td><td><?= Html::encode((string) $value) ?></td></tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="responsive-table">
                            <table class="data-table">
                                <thead><tr><th>Jadual Transaksi</th><th>Status</th></tr></thead>
                                <tbody>
                                <?php foreach ($coreTables as $table => $row): ?>
                                    <tr><td><?= Html::encode((string) $table) ?></td><td><?= $row === null ? 'Tidak berkaitan' : 'Akan disediakan' ?></td></tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($postCommit !== [] || $blockedSideEffects !== []): ?>
                            <ul>
                                <?php foreach ($postCommit as $line): ?>
                                    <li><?= Html::encode((string) $line) ?></li>
                                <?php endforeach; ?>
                                <?php foreach ($blockedSideEffects as $line): ?>
                                    <li><?= Html::encode((string) $line) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>

                <form class="record-form" method="post">
                    <?= $csrf->hiddenInput() ?>
                    <div class="record-form-grid">
                        <?php foreach ($form['fields'] as $field): ?>
                            <?php
                            $name = (string) $field['name'];
                            $type = (string) $field['type'];
                            $value = (string) ($values[$name] ?? '');
                            ?>
                            <label class="grid-field">
                                <span><?= Html::encode((string) $field['label']) ?><?= $field['required'] ? ' *' : '' ?></span>
                                <?php if ($type === 'select'): ?>
                                    <select name="<?= Html::encode($name) ?>" data-searchable-select>
                                        <option value="">Pilih <?= Html::encode((string) $field['label']) ?></option>
                                        <?php foreach (($form['options'][$field['options']] ?? []) as $option): ?>
                                            <option value="<?= Html::encode((string) $option['id']) ?>"<?= $selected($name, (string) $option['id']) ?>>
                                                <?= Html::encode((string) $option['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php elseif ($type === 'checkbox'): ?>
                                    <span class="checkbox-field">
                                        <input type="checkbox" name="<?= Html::encode($name) ?>" value="1"<?= $checked($name) ?>>
                                        <em>Tandakan jika ya / aktif</em>
                                    </span>
                                <?php elseif ($type === 'static'): ?>
                                    <input type="text" value="Auto Generate" disabled>
                                <?php else: ?>
                                    <input type="<?= Html::encode($type) ?>" name="<?= Html::encode($name) ?>" value="<?= Html::encode($value) ?>">
                                <?php endif; ?>
                                <?= $fieldError($name) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="record-form-actions">
                        <a class="button-link button-link-secondary" href="/lantikan/index">
                            <i data-lucide="arrow-left"></i>
                            <span>Kembali</span>
                        </a>
                        <a class="button-link button-link-secondary" href="<?= Html::encode((string) $form['legacyUrl']) ?>" target="_blank" rel="noopener">
                            <i data-lucide="external-link"></i>
                            <span>Yii2</span>
                        </a>
                        <button class="button-link button-link-secondary" type="submit" name="suggest_email" value="1">
                            <i data-lucide="mail"></i>
                            <span>Jana Email</span>
                        </button>
                        <button class="button-link-success" type="submit">
                            <i data-lucide="check"></i>
                            <span>Dry Run</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>
