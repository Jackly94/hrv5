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

$title = $mode === 'update' ? 'Kemaskini Kecacatan' : 'Tambah Kecacatan';
$this->setTitle($title . ' - ' . $applicationParams->name);

$fieldError = static fn (string $field): string => isset($errors[$field])
    ? '<p class="field-error">' . Html::encode($errors[$field]) . '</p>'
    : '';

$selected = static fn (string $field, string $value): string => ($values[$field] ?? '') === $value ? ' selected' : '';
$backUrl = $backUrl ?? '/biodata/kecacatan/view';
$formAction = $formAction ?? ($mode === 'update' ? '/biodata/kecacatan/update?id=' . rawurlencode($values['id'] ?? '') : '/biodata/kecacatan/tambahkecacatan');
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p><?= $staff === null ? 'Maklumat kecacatan kakitangan' : Html::encode($staff['name']) ?></p>
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
                        <h2>Maklumat Kecacatan</h2>
                    </div>

                    <?php if (isset($errors['general'])): ?>
                        <div class="record-form-alert">
                            <div class="alert alert-danger"><?= Html::encode($errors['general']) ?></div>
                        </div>
                    <?php endif; ?>

                    <form class="record-form" action="<?= Html::encode($formAction) ?>" method="post" enctype="multipart/form-data" novalidate>
                        <?= $csrf->hiddenInput() ?>

                        <div class="record-form-grid">
                            <label class="grid-field">
                                <span>No. Fail Kebajikan <b>*</b></span>
                                <input type="text" name="socialWelfareNo" maxlength="20" value="<?= Html::encode($values['socialWelfareNo']) ?>">
                                <?= $fieldError('socialWelfareNo') ?>
                            </label>

                            <label class="grid-field">
                                <span>No. Laporan Doktor <b>*</b></span>
                                <input type="text" name="doctorReportNo" maxlength="20" value="<?= Html::encode($values['doctorReportNo']) ?>">
                                <?= $fieldError('doctorReportNo') ?>
                            </label>

                            <label class="grid-field">
                                <span>Jenis Kecacatan <b>*</b></span>
                                <select name="disabilityTypeCd" data-searchable-select>
                                    <option value="">Pilih Jenis Kecacatan</option>
                                    <?php foreach ($options['types'] ?? [] as $option): ?>
                                        <option value="<?= Html::encode($option['id']) ?>"<?= $selected('disabilityTypeCd', $option['id']) ?>><?= Html::encode($option['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?= $fieldError('disabilityTypeCd') ?>
                            </label>

                            <label class="grid-field">
                                <span>Punca Kecacatan <b>*</b></span>
                                <select name="disabilityCauseCd" data-searchable-select>
                                    <option value="">Pilih Punca Kecacatan</option>
                                    <?php foreach ($options['causes'] ?? [] as $option): ?>
                                        <option value="<?= Html::encode($option['id']) ?>"<?= $selected('disabilityCauseCd', $option['id']) ?>><?= Html::encode($option['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?= $fieldError('disabilityCauseCd') ?>
                            </label>

                            <label class="grid-field">
                                <span>Tarikh Kecacatan</span>
                                <input type="date" name="disabilityDate" value="<?= Html::encode($values['disabilityDate']) ?>">
                                <?= $fieldError('disabilityDate') ?>
                            </label>

                            <label class="grid-field">
                                <span>Tarikh Sembuh</span>
                                <input type="date" name="healDate" value="<?= Html::encode($values['healDate']) ?>">
                                <?= $fieldError('healDate') ?>
                            </label>

                            <label class="grid-field grid-field-wide">
                                <span>Upload Kad Kebajikan <b>*</b></span>
                                <input type="file" name="file" accept="application/pdf,.pdf">
                                <em class="form-help">
                                    <?php if ($mode === 'update' && ($values['filename'] ?? '') !== '' && ($values['filename'] ?? '') !== 'deleted'): ?>
                                        Fail sedia ada dikekalkan jika tiada fail baharu dimuat naik.
                                    <?php else: ?>
                                        PDF sahaja. Saiz maksimum 6.0 MB.
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
