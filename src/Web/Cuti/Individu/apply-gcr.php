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
 * @var array<string, mixed>|null $staff
 * @var array<string, mixed>|null $openWindow
 * @var bool $eligible
 * @var list<string> $blockReasons
 * @var array<string, mixed>|null $entitlement
 * @var array<string, int> $calculations
 * @var list<array{id: string, name: string}> $verifiers
 * @var array<string, mixed> $values
 * @var array<string, string> $errors
 * @var list<array<string, mixed>> $menuSections
 * @var string $legacyUrl
 */

$this->setTitle($title . ' - ' . $applicationParams->name);
$fieldError = static fn (string $field): string => isset($errors[$field])
    ? '<p class="field-error">' . Html::encode($errors[$field]) . '</p>'
    : '';
$selected = static fn (string $field, string $value): string => (string) ($values[$field] ?? '') === $value ? ' selected' : '';
$gcrValue = (string) ($values['gcrApplied'] !== '' ? $values['gcrApplied'] : $calculations['maxGcr']);
$cbthValue = (string) ($values['cbthApplied'] !== '' ? $values['cbthApplied'] : $calculations['cbthSuggested']);
?>

<main class="cuti-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p><?= Html::encode((string) ($openWindow['year'] ?? date('Y'))) ?></p>
            </div>
            <a class="button-link button-link-secondary" href="/cuti/individu/index"><i data-lucide="arrow-left"></i><span>Kembali</span></a>
        </div>

        <div class="page-section-body page-card-section">
            <?php require dirname(__DIR__) . '/Shared/menu.php'; ?>
        </div>

        <div class="page-section-body">
            <section class="content-inner-card">
                <div class="profile-card-header grid-card-header">
                    <h2>Permohonan GCR & CBTH</h2>
                    <a class="grid-action" href="<?= Html::encode($legacyUrl) ?>" target="_blank" rel="noopener"><i data-lucide="external-link"></i><span>Yii2</span></a>
                </div>

                <?php if (isset($errors['general'])): ?>
                    <div class="record-form-alert"><div class="alert alert-danger"><?= Html::encode($errors['general']) ?></div></div>
                <?php endif; ?>

                <?php if (!$eligible): ?>
                    <div class="record-form-alert">
                        <div class="alert alert-danger">
                            <?php foreach ($blockReasons as $reason): ?>
                                <div><?= Html::encode($reason) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <form class="record-form" method="post" action="/cuti/individu/apply-gcr" novalidate>
                    <?= $csrf->hiddenInput() ?>
                    <div class="record-form-grid">
                        <div class="grid-field">
                            <span>Tahun Permohonan</span>
                            <input type="text" value="<?= Html::encode((string) ($openWindow['year'] ?? '-')) ?>" disabled>
                        </div>
                        <div class="grid-field">
                            <span>Baki Cuti</span>
                            <input type="text" value="<?= Html::encode((string) $calculations['leaveBalance']) ?>" disabled>
                        </div>
                        <div class="grid-field">
                            <span>Layak GCR Maksimum</span>
                            <input type="text" value="<?= Html::encode((string) $calculations['maxGcr']) ?>" disabled>
                        </div>
                        <div class="grid-field">
                            <span>Layak CBTH Maksimum</span>
                            <input type="text" value="<?= Html::encode((string) $calculations['maxCbth']) ?>" disabled>
                        </div>
                        <label class="grid-field">
                            <span>Semakan Oleh <b>*</b></span>
                            <select name="semakanBy" data-searchable-select<?= $eligible ? '' : ' disabled' ?>>
                                <option value="">Pilih penyelia cuti</option>
                                <?php foreach ($verifiers as $option): ?>
                                    <option value="<?= Html::encode($option['id']) ?>"<?= $selected('semakanBy', $option['id']) ?>><?= Html::encode($option['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= $fieldError('semakanBy') ?>
                        </label>
                        <label class="grid-field">
                            <span>GCR <b>*</b></span>
                            <input type="number" name="gcrApplied" min="0" max="<?= Html::encode((string) $calculations['maxGcr']) ?>" value="<?= Html::encode($gcrValue) ?>"<?= $eligible ? '' : ' disabled' ?> data-gcr-input data-max-total="<?= Html::encode((string) $calculations['leaveBalance']) ?>" data-max-cbth="<?= Html::encode((string) $calculations['maxCbth']) ?>">
                            <?= $fieldError('gcrApplied') ?>
                        </label>
                        <label class="grid-field">
                            <span>CBTH <b>*</b></span>
                            <input type="number" name="cbthApplied" min="0" value="<?= Html::encode($cbthValue) ?>" readonly data-cbth-output>
                            <?= $fieldError('cbthApplied') ?>
                        </label>
                        <div class="grid-field">
                            <span>Cuti Yang Akan Dilupuskan</span>
                            <input type="text" value="<?= Html::encode((string) $calculations['lupus']) ?>" disabled data-lupus-output>
                        </div>
                        <label class="grid-field grid-field-wide">
                            <span>Pengesahan <b>*</b></span>
                            <span class="checkbox-field">
                                <input type="checkbox" name="agreement" value="1"<?= (string) ($values['agreement'] ?? '') === '1' ? ' checked' : '' ?><?= $eligible ? '' : ' disabled' ?>>
                                <em>Sila pastikan permohonan GCR atau CBTH adalah muktamad kerana permohonan hanya boleh dibuat sekali sahaja.</em>
                            </span>
                            <?= $fieldError('agreement') ?>
                        </label>
                    </div>
                    <div class="record-form-actions">
                        <a class="button-link button-link-secondary" href="/cuti/individu/index"><i data-lucide="arrow-left"></i><span>Kembali</span></a>
                        <button class="button-link-success" type="submit"<?= $eligible ? '' : ' disabled' ?>><i data-lucide="send"></i><span>Hantar</span></button>
                    </div>
                </form>
            </section>
        </div>
    </section>
</main>
