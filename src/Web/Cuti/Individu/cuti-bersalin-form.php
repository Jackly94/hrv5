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
 * @var string $title
 * @var array<string, string> $leaveType
 * @var array<string, string> $staff
 * @var array<string, mixed> $values
 * @var array<string, string> $errors
 * @var array<string, mixed> $options
 * @var array<string, string> $assignees
 * @var list<array<string, mixed>> $menuSections
 * @var int $maternityBalance
 * @var string $legacyUrl
 */

$this->setTitle($title . ' - ' . $applicationParams->name);

$fieldError = static fn (string $field): string => isset($errors[$field])
    ? '<p class="field-error">' . Html::encode($errors[$field]) . '</p>'
    : '';
$selected = static fn (string $field, string $value): string => (string) ($values[$field] ?? '') === $value ? ' selected' : '';
$formAction = '/cuti/individu/cb?id=' . rawurlencode((string) ($values['typeId'] ?? $leaveType['id']));
?>

<main class="cuti-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p><?= Html::encode($staff['name']) ?> &middot; <?= Html::encode($staff['department']) ?></p>
            </div>
            <div class="profile-actions">
                <a class="button-link button-link-secondary" href="/cuti/individu/pilih">
                    <i data-lucide="arrow-left"></i>
                    <span>Kembali</span>
                </a>
            </div>
        </div>

        <div class="page-section-body page-card-section">
            <?php require dirname(__DIR__) . '/Shared/menu.php'; ?>
        </div>

        <div class="page-section-body">
            <section class="content-inner-card">
                <div class="profile-card-header grid-card-header">
                    <h2><?= Html::encode($leaveType['code'] !== '' ? $leaveType['code'] : 'Cuti') ?></h2>
                    <span><?= Html::encode($leaveType['name']) ?></span>
                </div>

                <?php if (isset($errors['general'])): ?>
                    <div class="record-form-alert">
                        <div class="alert alert-danger"><?= Html::encode($errors['general']) ?></div>
                    </div>
                <?php endif; ?>

                <form class="record-form" action="<?= Html::encode($formAction) ?>" method="post" enctype="multipart/form-data" novalidate>
                    <?= $csrf->hiddenInput() ?>
                    <input type="hidden" name="typeId" value="<?= Html::encode((string) ($values['typeId'] ?? $leaveType['id'])) ?>">

                    <div class="record-form-grid">
                        <label class="grid-field">
                            <span>Tarikh Mula <b>*</b></span>
                            <input type="date" name="startDate" value="<?= Html::encode((string) $values['startDate']) ?>">
                            <?= $fieldError('startDate') ?>
                        </label>

                        <label class="grid-field">
                            <span>Tempoh <b>*</b></span>
                            <select name="duration" data-searchable-select>
                                <option value="">Pilih tempoh</option>
                                <?php foreach ($options['durations'] ?? [] as $option): ?>
                                    <option value="<?= Html::encode($option['id']) ?>"<?= $selected('duration', $option['id']) ?>><?= Html::encode($option['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <em class="form-help">Baki Cuti Bersalin: <?= Html::encode((string) $maternityBalance) ?> hari.</em>
                            <?= $fieldError('duration') ?>
                        </label>

                        <label class="grid-field">
                            <span>Cuti Bersalin Untuk Kali <b>*</b></span>
                            <select name="cbCutiBilanganId" data-searchable-select>
                                <option value="">Pilih maklumat</option>
                                <?php foreach ($options['maternityNumbers'] ?? [] as $option): ?>
                                    <option value="<?= Html::encode($option['id']) ?>"<?= $selected('cbCutiBilanganId', $option['id']) ?>><?= Html::encode($option['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= $fieldError('cbCutiBilanganId') ?>
                        </label>

                        <label class="grid-field">
                            <span>Bagi Kelahiran Anak <b>*</b></span>
                            <select name="cbAnakBilanganId" data-searchable-select>
                                <option value="">Pilih maklumat</option>
                                <?php foreach ($options['maternityNumbers'] ?? [] as $option): ?>
                                    <option value="<?= Html::encode($option['id']) ?>"<?= $selected('cbAnakBilanganId', $option['id']) ?>><?= Html::encode($option['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= $fieldError('cbAnakBilanganId') ?>
                        </label>

                        <label class="grid-field">
                            <span>Catatan <b>*</b></span>
                            <textarea name="remark" rows="4"><?= Html::encode((string) $values['remark']) ?></textarea>
                            <?= $fieldError('remark') ?>
                        </label>

                        <label class="grid-field">
                            <span>Dokumen Sokongan <b>*</b></span>
                            <input type="file" name="file" accept="application/pdf,image/jpeg,image/png,.pdf,.jpg,.jpeg,.png">
                            <em class="form-help">PDF, JPG, JPEG atau PNG. Saiz maksimum 6 MB.</em>
                            <?= $fieldError('file') ?>
                        </label>

                        <div class="grid-field">
                            <span>Pegawai Penyemak</span>
                            <input type="text" value="<?= Html::encode($assignees['semakanName']) ?>" disabled>
                        </div>

                        <div class="grid-field">
                            <span>Pegawai Peraku</span>
                            <input type="text" value="<?= Html::encode($assignees['perakuName']) ?>" disabled>
                        </div>

                        <div class="grid-field">
                            <span>Pegawai Pelulus</span>
                            <input type="text" value="<?= Html::encode($assignees['lulusName']) ?>" disabled>
                        </div>
                    </div>

                    <div class="record-form-actions">
                        <a class="button-link button-link-secondary" href="/cuti/individu/pilih">
                            <i data-lucide="arrow-left"></i>
                            <span>Kembali</span>
                        </a>
                        <a class="button-link button-link-secondary" href="<?= Html::encode($legacyUrl) ?>" target="_blank" rel="noopener">
                            <i data-lucide="external-link"></i>
                            <span>Yii2</span>
                        </a>
                        <button class="button-link-success" type="submit">
                            <i data-lucide="send"></i>
                            <span>Hantar</span>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </section>
</main>
