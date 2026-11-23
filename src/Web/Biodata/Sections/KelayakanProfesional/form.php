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
 * @var array{name: string}|null $staff
 * @var 'create'|'update' $mode
 * @var array<string, list<array{id: string, name: string}>> $options
 * @var array<string, string> $values
 * @var array<string, string> $errors
 * @var string $backUrl
 * @var string $formAction
 */

$title = $mode === 'update' ? 'Kemaskini Kelayakan Profesional' : 'Tambah Kelayakan Profesional';
$this->setTitle($title . ' - ' . $applicationParams->name);

$fieldError = static fn (string $field): string => isset($errors[$field])
    ? '<p class="field-error">' . Html::encode($errors[$field]) . '</p>'
    : '';

$selected = static fn (string $field, string $value): string => ($values[$field] ?? '') === $value ? ' selected' : '';
$formAction = $formAction ?? ($mode === 'update'
    ? '/biodata/kelayakan-profesional/update-cc?id=' . rawurlencode($values['id'] ?? '')
    : '/biodata/kelayakan-profesional/add-cl-cert');
$backUrl = $backUrl ?? '/biodata/kelayakan-profesional/view';
$showOtherType = ($values['type'] ?? '') === '999';
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p><?= $staff === null ? 'Rekod sijil profesional kakitangan' : Html::encode($staff['name']) ?></p>
            </div>
            <div class="profile-actions">
                <a class="button-link button-link-secondary" href="<?= Html::encode($backUrl) ?>">
                    <i data-lucide="arrow-left"></i>
                    <span>Kembali</span>
                </a>
            </div>
        </div>

        <?php if ($staff === null): ?>
            <div class="profile-empty">Rekod staf tidak dijumpai.</div>
        <?php else: ?>
            <div class="page-card-section page-section-body">
                <section class="content-inner-card">
                    <div class="profile-card-header grid-card-header">
                        <h2>Maklumat Sijil Profesional</h2>
                    </div>

                    <?php if (isset($errors['general'])): ?>
                        <div class="record-form-alert">
                            <div class="alert alert-danger"><?= Html::encode($errors['general']) ?></div>
                        </div>
                    <?php endif; ?>

                    <form class="record-form" action="<?= Html::encode($formAction) ?>" method="post" enctype="multipart/form-data" novalidate>
                        <?= $csrf->hiddenInput() ?>

                        <div class="record-form-grid">
                            <label class="grid-field grid-field-wide">
                                <span>Tajuk Sijil <b>*</b></span>
                                <input type="text" name="title" maxlength="255" value="<?= Html::encode($values['title']) ?>">
                                <?= $fieldError('title') ?>
                            </label>

                            <label class="grid-field">
                                <span>Jenis Sijil <b>*</b></span>
                                <select name="type" data-searchable-select data-prof-cert-type data-other-target="[data-prof-cert-other-row]">
                                    <option value="">Pilih Jenis Sijil</option>
                                    <?php foreach ($options['types'] ?? [] as $option): ?>
                                        <option value="<?= Html::encode($option['id']) ?>"<?= $selected('type', $option['id']) ?>><?= Html::encode($option['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?= $fieldError('type') ?>
                            </label>

                            <label class="grid-field">
                                <span>Bidang <b>*</b></span>
                                <select name="field" data-searchable-select>
                                    <option value="">Pilih Bidang</option>
                                    <?php foreach ($options['fields'] ?? [] as $option): ?>
                                        <option value="<?= Html::encode($option['id']) ?>"<?= $selected('field', $option['id']) ?>><?= Html::encode($option['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?= $fieldError('field') ?>
                            </label>

                            <label class="grid-field grid-field-wide" data-prof-cert-other-row<?= $showOtherType ? '' : ' hidden' ?>>
                                <span>Jenis Lain-lain <b>*</b></span>
                                <textarea name="otherType" rows="3"><?= Html::encode($values['otherType']) ?></textarea>
                                <?= $fieldError('otherType') ?>
                            </label>

                            <label class="grid-field">
                                <span>No. Sijil</span>
                                <input type="text" name="certificateNo" maxlength="255" value="<?= Html::encode($values['certificateNo']) ?>">
                                <?= $fieldError('certificateNo') ?>
                            </label>

                            <label class="grid-field">
                                <span>Tarikh Dianugerahkan <b>*</b></span>
                                <input type="date" name="dateAwarded" value="<?= Html::encode($values['dateAwarded']) ?>">
                                <?= $fieldError('dateAwarded') ?>
                            </label>

                            <label class="grid-field">
                                <span>Tarikh Mula <b>*</b></span>
                                <input type="date" name="startDate" value="<?= Html::encode($values['startDate']) ?>">
                                <?= $fieldError('startDate') ?>
                            </label>

                            <label class="grid-field">
                                <span>Tarikh Tamat <b>*</b></span>
                                <input type="date" name="endDate" value="<?= Html::encode($values['endDate']) ?>">
                                <?= $fieldError('endDate') ?>
                            </label>

                            <label class="grid-field grid-field-wide">
                                <span>Dianugerahkan Oleh <b>*</b></span>
                                <textarea name="awardedBy" rows="3"><?= Html::encode($values['awardedBy']) ?></textarea>
                                <?= $fieldError('awardedBy') ?>
                            </label>

                            <label class="grid-field grid-field-wide">
                                <span>Upload Dokumen <b>*</b></span>
                                <input type="file" name="file" accept="application/pdf,image/jpeg,image/png,.pdf,.jpg,.jpeg,.png">
                                <em class="form-help">
                                    <?php if ($mode === 'update' && ($values['proof'] ?? '') !== '' && ($values['proof'] ?? '') !== 'deleted'): ?>
                                        Fail sedia ada dikekalkan jika tiada fail baharu dimuat naik.
                                    <?php else: ?>
                                        PDF, JPG, JPEG atau PNG. Saiz maksimum 6.0 MB.
                                    <?php endif; ?>
                                </em>
                                <?= $fieldError('file') ?>
                            </label>
                        </div>

                        <div class="record-form-actions">
                            <a class="button-link button-link-secondary" href="<?= Html::encode($backUrl) ?>">
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
