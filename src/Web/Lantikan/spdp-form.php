<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\View\WebView;
use Yiisoft\Yii\View\Renderer\Csrf;

/**
 * @var WebView $this
 * @var Csrf $csrf
 * @var array<string, mixed> $form
 * @var array<string, mixed>|null $result
 */

$this->setTitle((string) $form['title']);
$values = is_array($form['values'] ?? null) ? $form['values'] : [];
$options = is_array($form['options'] ?? null) ? $form['options'] : [];
$errors = is_array($result['errors'] ?? null) ? $result['errors'] : [];
$formError = isset($errors['_form']) ? (string) $errors['_form'] : '';
$fieldError = static fn (string $field): string => isset($errors[$field])
    ? '<p class="field-error">' . Html::encode((string) $errors[$field]) . '</p>'
    : '';
$value = static fn (string $field): string => (string) ($values[$field] ?? '');
$selected = static fn (string $field, string $option): string => (string) ($values[$field] ?? '') === $option ? ' selected' : '';
$checked = static fn (string $field): string => (string) ($values[$field] ?? '') === '1' ? ' checked' : '';
$requiredMark = static fn (bool $isRequired = true): string => $isRequired ? ' *' : '';

$textInput = static function (string $name, string $label, bool $isRequired = true, string $type = 'text') use ($value, $fieldError, $requiredMark): void {
    ?>
    <label class="grid-field">
        <span><?= Html::encode($label) ?><?= $requiredMark($isRequired) ?></span>
        <input<?= $name === 'CONm' ? ' data-lantikan-name-suggestion-source' : '' ?> type="<?= Html::encode($type) ?>" name="<?= Html::encode($name) ?>" value="<?= Html::encode($value($name)) ?>">
        <?= $fieldError($name) ?>
    </label>
    <?php
};

$selectInput = static function (string $name, string $label, string $optionKey, bool $isRequired = true) use ($options, $selected, $fieldError, $requiredMark): void {
    ?>
    <label class="grid-field">
        <span><?= Html::encode($label) ?><?= $requiredMark($isRequired) ?></span>
        <select name="<?= Html::encode($name) ?>" data-searchable-select>
            <option value="">Pilih <?= Html::encode($label) ?></option>
            <?php foreach (($options[$optionKey] ?? []) as $option): ?>
                <option value="<?= Html::encode((string) $option['id']) ?>"<?= $selected($name, (string) $option['id']) ?>>
                    <?= Html::encode((string) $option['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?= $fieldError($name) ?>
    </label>
    <?php
};

$checkboxInput = static function (string $name, string $label, string $hint, bool $isRequired = true) use ($checked, $fieldError, $requiredMark): void {
    ?>
    <label class="grid-field">
        <span><?= Html::encode($label) ?><?= $requiredMark($isRequired) ?></span>
        <span class="checkbox-field">
            <input type="checkbox" name="<?= Html::encode($name) ?>" value="1"<?= $checked($name) ?>>
            <em><?= Html::encode($hint) ?></em>
        </span>
        <?= $fieldError($name) ?>
    </label>
    <?php
};
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
                <?php if ($result !== null): ?>
                    <div class="profile-empty">
                        <?php if ($formError !== ''): ?>
                            <?= Html::encode($formError) ?>
                        <?php else: ?>
                            <?= ($result['success'] ?? false) ? 'Dry-run berjaya. Tiada rekod disimpan.' : 'Sila semak ralat borang.' ?>
                        <?php endif; ?>
                    </div>

                    <?php $proposed = is_array($result['proposed'] ?? null) ? $result['proposed'] : []; ?>
                    <?php if ($proposed !== []): ?>
                        <ul>
                            <?php foreach ($proposed as $line): ?>
                                <li><?= Html::encode((string) $line) ?></li>
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
                                <?php foreach ($summary as $key => $rowValue): ?>
                                    <tr><td><?= Html::encode((string) $key) ?></td><td><?= Html::encode((string) $rowValue) ?></td></tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="responsive-table">
                            <table class="data-table">
                                <thead><tr><th>Jadual Transaksi SPDP</th><th>Status</th></tr></thead>
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
                        <div class="grid-field grid-field-wide record-form-section-heading">
                            <strong>Maklumat Asas Kakitangan SPDP</strong>
                        </div>

                        <?php $textInput('ICNO', 'No. Kad Pengenalan'); ?>

                        <label class="grid-field">
                            <span>UMSPER</span>
                            <input type="text" value="Auto Generate" disabled>
                            <?= $fieldError('COOldID') ?>
                        </label>

                        <?php $selectInput('TitleCd', 'Gelaran', 'titles'); ?>
                        <?php $textInput('CONm', 'Nama'); ?>
                        <?php $selectInput('ReligionCd', 'Agama', 'religions'); ?>
                        <?php $selectInput('RaceCd', 'Bangsa', 'races'); ?>
                        <?php $selectInput('EthnicCd', 'Etnik', 'ethnicities'); ?>
                        <?php $selectInput('BloodTypeCd', 'Jenis Darah', 'bloodTypes'); ?>
                        <?php $selectInput('GenderCd', 'Jantina', 'genders'); ?>
                        <?php $selectInput('MrtlStatusCd', 'Taraf Perkahwinan', 'maritalStatuses'); ?>
                        <?php $textInput('COBirthCertNo', 'No. Sijil Lahir'); ?>
                        <?php $textInput('COBirthDt', 'Tarikh Lahir', true, 'date'); ?>
                        <?php $selectInput('COBirthCountryCd', 'Negara Lahir', 'countries'); ?>
                        <?php $selectInput('COBirthPlaceCd', 'Tempat Lahir', 'states'); ?>
                        <?php $selectInput('NegaraAsalCd', 'Negara Asal Staf', 'countries'); ?>
                        <?php $selectInput('NegeriAsalCd', 'Negeri Asal Staf', 'states'); ?>
                        <?php $selectInput('NegeriAsalIbu', 'Negeri Asal Ibu', 'states'); ?>
                        <?php $selectInput('NegeriAsalBapa', 'Negeri Asal Bapa', 'states'); ?>
                        <?php $selectInput('NatCd', 'Warganegara', 'countries'); ?>
                        <?php $selectInput('NatStatusCd', 'Status Warganegara', 'nationalityStatuses'); ?>
                        <?php $checkboxInput('COBumiStatus', 'Bumiputera', 'Tandakan jika Bumiputera'); ?>
                        <?php $selectInput('isSabahan', 'Status Sabah', 'sabahStatuses', false); ?>

                        <div class="grid-field">
                            <span>Emel Rasmi *</span>
                            <div class="grid-field-action-row">
                                <input data-lantikan-email-suggestion-target type="email" name="COEmail" value="<?= Html::encode($value('COEmail')) ?>">
                                <button class="button-link button-link-secondary grid-field-action-button" type="submit" name="suggest_email" value="1">
                                    <i data-lucide="mail"></i>
                                    <span>Jana Email</span>
                                </button>
                            </div>
                            <?= $fieldError('COEmail') ?>
                        </div>

                        <?php $textInput('COEmail2', 'Emel Peribadi', true, 'email'); ?>
                        <?php $textInput('COHPhoneNo', 'No. Telefon Bimbit'); ?>
                        <?php $textInput('COHPhoneNo2', 'No. Telefon Bimbit 2', false); ?>
                        <?php $checkboxInput('COHPhoneStatus', 'Status Telefon Bimbit', 'Tandakan jika aktif', false); ?>
                        <?php $selectInput('HighestEduLevelCd', 'Pendidikan Tertinggi', 'educationLevels'); ?>
                        <?php $textInput('ConfermentDt', 'Tarikh Dianugerahkan Pendidikan Tertinggi', true, 'date'); ?>
                        <?php $selectInput('ArmyPoliceCd', 'Status Uniform', 'uniformStatuses'); ?>

                        <div class="grid-field grid-field-wide record-form-section-heading">
                            <strong>Maklumat Rekod Perkhidmatan SPDP</strong>
                        </div>

                        <?php $selectInput('statLantikan', 'Status Lantikan', 'appointmentStatusesSpdp'); ?>
                        <?php $textInput('startDateLantik', 'Tarikh Mula Lantikan', true, 'date'); ?>
                        <?php $textInput('endDateLantik', 'Tarikh Tamat Lantikan', true, 'date'); ?>
                        <?php $selectInput('gredJawatan', 'Gred Jawatan', 'jobGradesSpdp'); ?>
                        <?php $selectInput('gredJawatan_hakiki', 'Gred Jawatan Hakiki', 'jobGradesSpdp'); ?>
                        <?php $selectInput('statSandangan', 'Status Sandangan', 'sandanganStatuses'); ?>
                        <?php $selectInput('ApmtTypeCd', 'Jenis Lantikan', 'appointmentTypes'); ?>
                        <?php $textInput('noWaran', 'No. Waran', false); ?>
                        <?php $selectInput('DeptId', 'Jabatan', 'departments'); ?>
                        <?php $selectInput('campus_id', 'Kampus', 'campuses'); ?>
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
