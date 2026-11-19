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
 */

$title = $mode === 'update' ? 'Kemaskini Lesen' : 'Tambah Lesen';
$this->setTitle($title . ' - ' . $applicationParams->name);

$fieldError = static fn (string $field): string => isset($errors[$field])
    ? '<p class="field-error">' . Html::encode($errors[$field]) . '</p>'
    : '';

$selected = static fn (string $field, string $value): string => ($values[$field] ?? '') === $value ? ' selected' : '';
$formAction = $mode === 'update' ? '/lesen/update?id=' . rawurlencode($values['id'] ?? '') : '/lesen/tambahlesen';
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p><?= $staff === null ? 'Maklumat lesen kakitangan' : Html::encode($staff['name']) ?></p>
            </div>
            <div class="profile-actions">
                <a class="button-link button-link-secondary" href="/lesen/view">
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
                        <h2>Maklumat Lesen</h2>
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
                                <span>No. Lesen <b>*</b></span>
                                <input type="text" name="licNo" maxlength="20" value="<?= Html::encode($values['licNo']) ?>">
                                <?= $fieldError('licNo') ?>
                            </label>

                            <label class="grid-field">
                                <span>Nama Pemilik Lesen</span>
                                <input type="text" name="name" value="<?= Html::encode($values['name']) ?>" placeholder="Perlu sekiranya lesen bukan milik sendiri">
                                <em class="form-help">Isi sekiranya lesen yang dimasukkan bukan milik sendiri.</em>
                                <?= $fieldError('name') ?>
                            </label>

                            <label class="grid-field">
                                <span>Jenis Lesen <b>*</b></span>
                                <select name="licCd" data-searchable-select>
                                    <option value="">Pilih Jenis Lesen</option>
                                    <?php foreach ($options['types'] ?? [] as $option): ?>
                                        <option value="<?= Html::encode($option['id']) ?>"<?= $selected('licCd', $option['id']) ?>><?= Html::encode($option['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?= $fieldError('licCd') ?>
                            </label>

                            <label class="grid-field">
                                <span>Kelas Lesen <b>*</b></span>
                                <select name="licClassCd" data-searchable-select>
                                    <option value="">Pilih Kelas Lesen</option>
                                    <?php foreach ($options['classes'] ?? [] as $option): ?>
                                        <option value="<?= Html::encode($option['id']) ?>"<?= $selected('licClassCd', $option['id']) ?>><?= Html::encode($option['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?= $fieldError('licClassCd') ?>
                            </label>

                            <label class="grid-field">
                                <span>Tarikh Dikeluarkan <b>*</b></span>
                                <input type="date" name="issuedDate" value="<?= Html::encode($values['issuedDate']) ?>">
                                <?= $fieldError('issuedDate') ?>
                            </label>

                            <label class="grid-field">
                                <span>Tarikh Luput <b>*</b></span>
                                <input type="date" name="expiryDate" value="<?= Html::encode($values['expiryDate']) ?>">
                                <?= $fieldError('expiryDate') ?>
                            </label>

                            <label class="grid-field">
                                <span>Yuran Pembaharuan <b>*</b></span>
                                <input type="text" name="fee" inputmode="decimal" placeholder="RM" value="<?= Html::encode($values['fee']) ?>">
                                <?= $fieldError('fee') ?>
                            </label>

                            <label class="grid-field">
                                <span>Upload Lesen <b>*</b></span>
                                <input type="file" name="file" accept="application/pdf,image/jpeg,image/png,.pdf,.jpg,.jpeg,.png">
                                <em class="form-help">
                                    <?php if ($mode === 'update' && ($values['filename'] ?? '') !== '' && ($values['filename'] ?? '') !== 'deleted'): ?>
                                        Fail sedia ada dikekalkan jika tiada fail baharu dimuat naik.
                                    <?php else: ?>
                                        PDF, JPG, JPEG atau PNG. Saiz maksimum 6.0 MB.
                                    <?php endif; ?>
                                </em>
                                <?= $fieldError('file') ?>
                            </label>
                        </div>

                        <div class="record-form-actions">
                            <a class="button-link button-link-secondary" href="/lesen/view">
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
