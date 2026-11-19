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
 * @var array{icno: string, name: string, staffNo: string, rows: list<array<string, string>>}|null $staff
 * @var 'create'|'update' $mode
 * @var array<string, list<array{id: string, name: string}>> $options
 * @var array<string, string> $values
 * @var array<string, string> $errors
 */

$title = $mode === 'update' ? 'Kemaskini Keahlian' : 'Tambah Keahlian';
$this->setTitle($title . ' - ' . $applicationParams->name);

$fieldError = static fn (string $field): string => isset($errors[$field])
    ? '<p class="field-error">' . Html::encode($errors[$field]) . '</p>'
    : '';

$selected = static fn (string $field, string $value): string => ($values[$field] ?? '') === $value ? ' selected' : '';
$checked = static fn (string $field): string => ($values[$field] ?? '') === '1' ? ' checked' : '';
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p><?= $staff === null ? 'Kelab, persatuan, institusi dan kesatuan' : Html::encode($staff['name']) ?></p>
            </div>
            <div class="profile-actions">
                <a class="button-link button-link-secondary" href="/badan-profesional/view">
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
                        <h2>Maklumat Keahlian</h2>
                    </div>

                    <?php if (isset($errors['general'])): ?>
                        <div class="record-form-alert">
                            <div class="alert alert-danger"><?= Html::encode($errors['general']) ?></div>
                        </div>
                    <?php endif; ?>

                    <form class="record-form" method="post" enctype="multipart/form-data" novalidate>
                        <?= $csrf->hiddenInput() ?>

                        <div class="record-form-grid">
                            <label class="grid-field grid-field-wide">
                                <span>Nama Organisasi <b>*</b></span>
                                <select name="profBodyCd" data-searchable-select>
                                    <option value="">Pilih Organisasi</option>
                                    <?php foreach ($options['bodies'] ?? [] as $option): ?>
                                        <option value="<?= Html::encode($option['id']) ?>"<?= $selected('profBodyCd', $option['id']) ?>><?= Html::encode($option['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?= $fieldError('profBodyCd') ?>
                            </label>

                            <label class="grid-field">
                                <span>Taraf Keahlian <b>*</b></span>
                                <select name="membershipTypeCd" data-searchable-select>
                                    <option value="">Pilih Taraf Keahlian</option>
                                    <?php foreach ($options['membershipTypes'] ?? [] as $option): ?>
                                        <option value="<?= Html::encode($option['id']) ?>"<?= $selected('membershipTypeCd', $option['id']) ?>><?= Html::encode($option['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?= $fieldError('membershipTypeCd') ?>
                            </label>

                            <label class="grid-field">
                                <span>Jawatan <b>*</b></span>
                                <input type="text" name="designation" maxlength="40" value="<?= Html::encode($values['designation']) ?>">
                                <?= $fieldError('designation') ?>
                            </label>

                            <label class="grid-field">
                                <span>Peringkat <b>*</b></span>
                                <select name="level" data-searchable-select>
                                    <option value="">Pilih Peringkat</option>
                                    <?php foreach ($options['levels'] ?? [] as $option): ?>
                                        <option value="<?= Html::encode($option['id']) ?>"<?= $selected('level', $option['id']) ?>><?= Html::encode($option['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?= $fieldError('level') ?>
                            </label>

                            <label class="grid-field">
                                <span>No. Keahlian <b>*</b></span>
                                <input type="text" name="membershipNo" maxlength="50" value="<?= Html::encode($values['membershipNo']) ?>">
                                <?= $fieldError('membershipNo') ?>
                            </label>

                            <label class="grid-field">
                                <span>Tarikh Mula Menyertai <b>*</b></span>
                                <input type="date" name="joinDate" value="<?= Html::encode($values['joinDate']) ?>">
                                <?= $fieldError('joinDate') ?>
                            </label>

                            <label class="grid-field">
                                <span>Tarikh Tamat Menyertai</span>
                                <input type="date" name="resignDate" value="<?= Html::encode($values['resignDate']) ?>">
                                <?= $fieldError('resignDate') ?>
                            </label>

                            <label class="grid-field">
                                <span>Yuran Dikenakan <b>*</b></span>
                                <input type="text" name="fee" inputmode="decimal" placeholder="RM" value="<?= Html::encode($values['fee']) ?>">
                                <?= $fieldError('fee') ?>
                            </label>

                            <label class="grid-field grid-field-wide">
                                <span>URL <b>*</b></span>
                                <textarea name="url" rows="3"><?= Html::encode($values['url']) ?></textarea>
                                <?= $fieldError('url') ?>
                            </label>

                            <div class="record-check-group">
                                <label class="record-check">
                                    <input type="checkbox" name="status" value="1"<?= $checked('status') ?>>
                                    <span>Tandakan jika masih aktif</span>
                                </label>
                                <label class="record-check">
                                    <input type="checkbox" name="isMedicalBody" value="1"<?= $checked('isMedicalBody') ?>>
                                    <span>Tandakan jika berkaitan kelayakan perubatan</span>
                                </label>
                            </div>

                            <label class="grid-field grid-field-wide">
                                <span>Muat Naik Dokumen <?= $mode === 'create' ? '<b>*</b>' : '' ?></span>
                                <input type="file" name="file" accept="application/pdf,.pdf">
                                <input type="hidden" name="filename" value="<?= Html::encode($values['filename'] ?? '') ?>">
                                <em class="form-help">
                                    Fail PDF sahaja. Saiz maksimum 6.0 MB.
                                    <?php if ($mode === 'update' && ($values['filename'] ?? '') !== '' && ($values['filename'] ?? '') !== 'deleted'): ?>
                                        Fail sedia ada akan dikekalkan jika tiada fail baharu dimuat naik.
                                    <?php endif; ?>
                                </em>
                                <?= $fieldError('file') ?>
                            </label>
                        </div>

                        <div class="record-form-actions">
                            <a class="button-link button-link-secondary" href="/badan-profesional/view">
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
