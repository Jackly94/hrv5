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
 * @var array{name: string, rows: list<array<string, string>>}|null $staff
 * @var 'create'|'update' $mode
 * @var array<string, list<array{id: string, name: string, country?: string, state?: string}>> $options
 * @var array<string, string> $values
 * @var array<string, string> $errors
 * @var bool $hasCurrentAddress
 */

$title = $mode === 'update' ? 'Kemaskini Alamat' : 'Tambah Alamat';
$this->setTitle($title . ' - ' . $applicationParams->name);

$fieldError = static fn (string $field): string => isset($errors[$field])
    ? '<p class="field-error">' . Html::encode($errors[$field]) . '</p>'
    : '';

$selected = static fn (string $field, string $value): string => ($values[$field] ?? '') === $value ? ' selected' : '';
$stateOptions = ($values['countryCd'] ?? '') === ''
    ? []
    : array_values(array_filter(
        $options['states'] ?? [],
        static fn (array $option): bool => ($option['country'] ?? '') === ($values['countryCd'] ?? ''),
    ));
$cityOptions = ($values['stateCd'] ?? '') === ''
    ? []
    : array_values(array_filter(
        $options['cities'] ?? [],
        static fn (array $option): bool => ($option['state'] ?? '') === ($values['stateCd'] ?? ''),
    ));
$formAction = $mode === 'update' ? '/alamat/update?id=' . rawurlencode($values['id'] ?? '') : '/alamat/tambahalamat';
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p><?= $staff === null ? 'Maklumat alamat kakitangan' : Html::encode($staff['name']) ?></p>
            </div>
            <div class="profile-actions">
                <a class="button-link button-link-secondary" href="/alamat/view">
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
                        <h2>Maklumat Alamat</h2>
                    </div>

                    <?php if (isset($errors['general'])): ?>
                        <div class="record-form-alert">
                            <div class="alert alert-danger"><?= Html::encode($errors['general']) ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if ($mode === 'create' && $hasCurrentAddress): ?>
                        <div class="record-form-alert">
                            <div class="alert alert-info address-copy-alert">
                                <span>Alamat Tetap sama seperti Alamat Semasa?</span>
                                <a class="grid-action" href="/alamat/tambahalamat?samadengan=01&amp;tambah=03">
                                    <i data-lucide="copy"></i>
                                    <span>Salin Alamat</span>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form class="record-form" action="<?= Html::encode($formAction) ?>" method="post" novalidate>
                        <?= $csrf->hiddenInput() ?>

                        <div class="record-form-grid">
                            <label class="grid-field">
                                <span>Jenis Alamat <b>*</b></span>
                                <?php if ($mode === 'update'): ?>
                                    <input type="hidden" name="addrTypeCd" value="<?= Html::encode($values['addrTypeCd']) ?>">
                                <?php endif; ?>
                                <select name="addrTypeCd" data-searchable-select<?= $mode === 'update' ? ' disabled' : '' ?>>
                                    <option value="">Pilih Jenis Alamat</option>
                                    <?php foreach ($options['types'] ?? [] as $option): ?>
                                        <option value="<?= Html::encode($option['id']) ?>"<?= $selected('addrTypeCd', $option['id']) ?>><?= Html::encode($option['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?= $fieldError('addrTypeCd') ?>
                            </label>

                            <label class="grid-field">
                                <span>Negara <b>*</b></span>
                                <select name="countryCd" data-searchable-select data-address-country data-endpoint="/alamat/statelist" data-state-target="select[name='stateCd']" data-city-target="select[name='cityCd']">
                                    <option value="">Pilih Negara</option>
                                    <?php foreach ($options['countries'] ?? [] as $option): ?>
                                        <option value="<?= Html::encode($option['id']) ?>"<?= $selected('countryCd', $option['id']) ?>><?= Html::encode($option['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?= $fieldError('countryCd') ?>
                            </label>

                            <label class="grid-field grid-field-wide">
                                <span>Alamat <b>*</b></span>
                                <input type="text" name="addr1" maxlength="100" value="<?= Html::encode($values['addr1']) ?>">
                                <?= $fieldError('addr1') ?>
                            </label>

                            <label class="grid-field grid-field-wide">
                                <span>Alamat 2</span>
                                <input type="text" name="addr2" maxlength="100" value="<?= Html::encode($values['addr2']) ?>">
                                <?= $fieldError('addr2') ?>
                            </label>

                            <label class="grid-field grid-field-wide">
                                <span>Alamat 3</span>
                                <input type="text" name="addr3" maxlength="100" value="<?= Html::encode($values['addr3']) ?>">
                                <?= $fieldError('addr3') ?>
                            </label>

                            <label class="grid-field">
                                <span>Negeri <b>*</b></span>
                                <select name="stateCd" data-searchable-select data-address-state data-endpoint="/alamat/citylist" data-city-target="select[name='cityCd']">
                                    <option value="">Pilih Negeri</option>
                                    <?php foreach ($stateOptions as $option): ?>
                                        <option value="<?= Html::encode($option['id']) ?>"<?= $selected('stateCd', $option['id']) ?>><?= Html::encode($option['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?= $fieldError('stateCd') ?>
                            </label>

                            <label class="grid-field">
                                <span>Daerah <b>*</b></span>
                                <select name="cityCd" data-searchable-select>
                                    <option value="">Pilih Daerah</option>
                                    <?php foreach ($cityOptions as $option): ?>
                                        <option value="<?= Html::encode($option['id']) ?>"<?= $selected('cityCd', $option['id']) ?>><?= Html::encode($option['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?= $fieldError('cityCd') ?>
                            </label>

                            <label class="grid-field">
                                <span>Poskod <b>*</b></span>
                                <input type="text" name="postcode" maxlength="10" inputmode="numeric" value="<?= Html::encode($values['postcode']) ?>">
                                <?= $fieldError('postcode') ?>
                            </label>

                            <label class="grid-field">
                                <span>No. Telefon <b>*</b></span>
                                <input type="text" name="telNo" maxlength="14" inputmode="tel" value="<?= Html::encode($values['telNo']) ?>">
                                <?= $fieldError('telNo') ?>
                            </label>
                        </div>

                        <div class="record-form-actions">
                            <a class="button-link button-link-secondary" href="/alamat/view">
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
