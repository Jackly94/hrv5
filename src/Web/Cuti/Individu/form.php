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
$checked = static fn (string $value): string => in_array($value, $values['reasons'] ?? [], true) ? ' checked' : '';
$requiresDateRange = $key !== 'cb';
$requiresSubstitute = in_array($key, ['cr1', 'cr2'], true);
$requiresDestination = $key === 'cr2';
$requiresVerifier = in_array($key, ['cs1', 'cs2', 'hso'], true);
$requiresFile = in_array($key, ['ck', 'cb', 'ctr', 'cs1', 'cs2', 'hso'], true);
$showsRemark = in_array($key, ['cr1', 'cr1-aca', 'cr2', 'ctr', 'cb', 'cs1', 'cs2', 'hso'], true);
$formAction = '/cuti/individu/' . rawurlencode($key) . '?id=' . rawurlencode((string) ($values['typeId'] ?? $leaveType['id']));
?>

<main class="cuti-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p><?= Html::encode($staff['name']) ?> · <?= Html::encode($staff['department']) ?></p>
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
                        <?php if ($key === 'cb'): ?>
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
                        <?php endif; ?>

                        <?php if ($requiresDateRange): ?>
                            <label class="grid-field">
                                <span>Tarikh Mula <b>*</b></span>
                                <input type="date" name="startDate" value="<?= Html::encode((string) $values['startDate']) ?>">
                                <?= $fieldError('startDate') ?>
                            </label>

                            <label class="grid-field">
                                <span>Tarikh Tamat <b>*</b></span>
                                <input type="date" name="endDate" value="<?= Html::encode((string) $values['endDate']) ?>">
                                <?= $fieldError('endDate') ?>
                            </label>
                        <?php endif; ?>

                        <?php if ($requiresSubstitute): ?>
                            <label class="grid-field">
                                <span>Pengganti <b>*</b></span>
                                <select name="gantiBy" data-searchable-select>
                                    <option value="">Pilih pengganti</option>
                                    <?php foreach ($options['staff'] ?? [] as $option): ?>
                                        <option value="<?= Html::encode($option['id']) ?>"<?= $selected('gantiBy', $option['id']) ?>><?= Html::encode($option['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?= $fieldError('gantiBy') ?>
                            </label>
                        <?php endif; ?>

                        <?php if ($requiresDestination): ?>
                            <label class="grid-field">
                                <span>Destinasi <b>*</b></span>
                                <textarea name="destination" rows="3"><?= Html::encode((string) $values['destination']) ?></textarea>
                                <?= $fieldError('destination') ?>
                            </label>
                        <?php endif; ?>

                        <?php if ($requiresVerifier): ?>
                            <label class="grid-field">
                                <span>Pegawai Penyemak <b>*</b></span>
                                <select name="semakanBy" data-searchable-select>
                                    <option value="">Pilih pegawai penyemak</option>
                                    <?php foreach ($options['sickVerifiers'] ?? [] as $option): ?>
                                        <option value="<?= Html::encode($option['id']) ?>"<?= $selected('semakanBy', $option['id']) ?>><?= Html::encode($option['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?= $fieldError('semakanBy') ?>
                            </label>
                        <?php endif; ?>

                        <?php if ($key === 'ctr'): ?>
                            <label class="grid-field">
                                <span>Jenis</span>
                                <select name="type" data-searchable-select>
                                    <option value="0"<?= $selected('type', '0') ?>>Dalam Negara</option>
                                    <option value="1"<?= $selected('type', '1') ?>>Luar Negara</option>
                                </select>
                            </label>
                        <?php endif; ?>

                        <?php if ($key === 'ck'): ?>
                            <fieldset class="grid-field">
                                <span>Sebab Memohon Cuti Kecemasan <b>*</b></span>
                                <?php foreach ($options['emergencyReasons'] ?? [] as $reason): ?>
                                    <label class="checkbox-row">
                                        <input type="checkbox" name="reasons[]" value="<?= Html::encode($reason) ?>"<?= $checked($reason) ?>>
                                        <span><?= Html::encode($reason) ?></span>
                                    </label>
                                <?php endforeach; ?>
                                <?= $fieldError('reasons') ?>
                            </fieldset>

                            <label class="grid-field">
                                <span>Perincian</span>
                                <textarea name="others" rows="4"><?= Html::encode((string) $values['others']) ?></textarea>
                            </label>
                        <?php endif; ?>

                        <?php if ($showsRemark): ?>
                            <label class="grid-field">
                                <span>Catatan <b>*</b></span>
                                <textarea name="remark" rows="4"><?= Html::encode((string) $values['remark']) ?></textarea>
                                <?= $fieldError('remark') ?>
                            </label>
                        <?php endif; ?>

                        <?php if ($requiresFile): ?>
                            <label class="grid-field">
                                <span>Dokumen Sokongan <b>*</b></span>
                                <input type="file" name="file" accept="application/pdf,image/jpeg,image/png,.pdf,.jpg,.jpeg,.png">
                                <em class="form-help">PDF, JPG, JPEG atau PNG. Saiz maksimum 6 MB.</em>
                                <?= $fieldError('file') ?>
                            </label>
                        <?php endif; ?>

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
