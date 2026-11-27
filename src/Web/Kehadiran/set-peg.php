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
 * @var array<string, mixed> $attendance
 * @var string $identityId
 * @var array{title: string} $page
 * @var array<string, mixed> $data
 * @var string $staffIcno
 * @var string $message
 * @var string $messageType
 */

$this->setTitle($page['title'] . ' - STARS - ' . $applicationParams->name);
$staff = $data['staff'];
$officer = $data['officer'];
$staffSubtitle = (string) ($staff['name'] ?? '-') . ' (' . (string) ($staff['staffNo'] ?? '-') . ')';
?>

<?php
/** @var list<array<string, mixed>> $menuSections */
$menuSections = $attendance['menuSections'];
?>
<main class="attendance-page">
    <section class="profile-card page-main-card attendance-main-card">
        <div class="dashboard-band dashboard-heading page-card-heading">
            <div>
                <h1>STARS</h1>
                <p><?= Html::encode($page['title']) ?></p>
            </div>
            <a class="button-link button-link-secondary" href="/kehadiran/staff-wbb">
                <i data-lucide="arrow-left"></i>
                <span>Kembali</span>
            </a>
        </div>

        <?php require __DIR__ . '/_topMenu.php'; ?>

        <div class="page-section-body page-card-section">
            <section class="content-inner-card attendance-standard-toolbar">
                <div class="attendance-standard-title">
                    <span class="attendance-standard-icon attendance-icon-warning" aria-hidden="true"><i data-lucide="users"></i></span>
                    <div>
                        <h2>Kemaskini Pegawai Peraku/Lulus</h2>
                        <p><?= Html::encode($staffSubtitle) ?></p>
                    </div>
                </div>
                <div class="attendance-standard-actions">
                    <a class="button-link button-link-secondary" href="/kehadiran/staff-wbb"><i data-lucide="arrow-left"></i><span>Kembali Staff Working Hours</span></a>
                </div>
            </section>
        </div>

        <?php if ($message !== ''): ?>
            <div class="page-section-body page-card-section">
                <section class="content-inner-card staff-page-panel attendance-compact-panel">
                    <span class="status-badge <?= $messageType === 'success' ? 'status-badge-success' : 'status-badge-danger' ?>"><?= Html::encode($message) ?></span>
                </section>
            </div>
        <?php endif; ?>

        <div class="page-section-body">
            <section class="content-inner-card">
                <div class="profile-card-header grid-card-header">
                    <h2>Pegawai Peraku/Lulus</h2>
                    <span><?= Html::encode($staffSubtitle) ?></span>
                </div>
                <form class="record-form" method="post" action="<?= Html::encode('/kehadiran/set-peg?id=' . rawurlencode($staffIcno)) ?>">
                    <?= $csrf->hiddenInput() ?>
                    <div class="record-form-grid">
                        <label class="grid-field grid-field-wide">
                            <span>Nama Kakitangan</span>
                            <input type="text" value="<?= Html::encode($staffSubtitle) ?>" disabled>
                        </label>

                        <label class="grid-field">
                            <span>Pegawai Peraku</span>
                            <select name="peraku_icno" data-searchable-select>
                                <option value="">Terus kepada Pegawai Pelulus</option>
                                <?php foreach ($data['staffOptions'] as $option): ?>
                                    <option value="<?= Html::encode((string) $option['icno']) ?>" <?= (string) $option['icno'] === (string) $officer['perakuIcno'] ? 'selected' : '' ?>>
                                        <?= Html::encode((string) $option['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label class="grid-field">
                            <span>Pegawai Pelulus <b>*</b></span>
                            <select name="pelulus_icno" data-searchable-select required>
                                <option value="">Pilih Pegawai Pelulus</option>
                                <?php foreach ($data['staffOptions'] as $option): ?>
                                    <option value="<?= Html::encode((string) $option['icno']) ?>" <?= (string) $option['icno'] === (string) $officer['pelulusIcno'] ? 'selected' : '' ?>>
                                        <?= Html::encode((string) $option['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                    <div class="record-form-actions">
                        <a class="button-link button-link-secondary" href="/kehadiran/staff-wbb"><i data-lucide="arrow-left"></i><span>Kembali</span></a>
                        <button class="button-link-success" type="submit"><i data-lucide="save"></i><span>Kemaskini</span></button>
                    </div>
                </form>
            </section>
        </div>
    </section>
</main>
