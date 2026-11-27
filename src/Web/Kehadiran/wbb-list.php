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
 * @var int|null $wpId
 * @var string $message
 * @var string $messageType
 */

$this->setTitle($page['title'] . ' - STARS - ' . $applicationParams->name);
$staff = $data['staff'];
$form = $data['form'];
$staffSubtitle = (string) ($staff['name'] ?? '-') . ' (' . (string) ($staff['staffNo'] ?? '-') . ')';
$actionUrl = '/kehadiran/wbb-list?id=' . rawurlencode($staffIcno) . ($wpId === null ? '' : '&wp_id=' . rawurlencode((string) $wpId));
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
                    <span class="attendance-standard-icon attendance-icon-info" aria-hidden="true"><i data-lucide="clock"></i></span>
                    <div>
                        <h2>Senarai Waktu Bekerja Berperingkat</h2>
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

        <div class="page-section-body page-card-section">
            <section class="content-inner-card staff-page-panel attendance-compact-panel">
                <div class="profile-card-header grid-card-header">
                    <h2>Rekod WBB</h2>
                    <span><?= Html::encode($staffSubtitle) ?></span>
                </div>
                <div class="responsive-table">
                    <table class="data-table attendance-compact-table">
                        <thead><tr><th>Bil</th><th>Waktu Pilihan</th><th>Tarikh Mula</th><th>Tarikh Tamat</th><th>Status</th><th>Kemaskini</th><th>Buang</th></tr></thead>
                        <tbody>
                            <?php if ($data['rows'] === []): ?><tr><td colspan="7" class="staff-empty">Tidak ada sebarang WBB setakat ini.</td></tr><?php endif; ?>
                            <?php foreach ($data['rows'] as $index => $row): ?>
                                <tr>
                                    <td><span class="attendance-row-number"><?= Html::encode((string) ($index + 1)) ?></span></td>
                                    <td><?= Html::encode((string) $row['label']) ?><br><small><?= Html::encode((string) $row['detail']) ?></small></td>
                                    <td><?= Html::encode((string) $row['startDate']) ?></td>
                                    <td><?= Html::encode((string) $row['endDate']) ?></td>
                                    <td><span class="status-badge"><?= Html::encode((string) $row['status']) ?></span></td>
                                    <td>
                                        <a class="grid-action" href="<?= Html::encode('/kehadiran/wbb-list?id=' . rawurlencode($staffIcno) . '&wp_id=' . rawurlencode((string) $row['id'])) ?>">
                                            <i data-lucide="pencil"></i><span>Edit</span>
                                        </a>
                                    </td>
                                    <td>
                                        <form class="grid-action-form" method="post" action="/kehadiran/del-wbb" onsubmit="return window.confirm('Buang rekod WBB ini?');">
                                            <?= $csrf->hiddenInput() ?>
                                            <input type="hidden" name="id" value="<?= Html::encode((string) $row['id']) ?>">
                                            <input type="hidden" name="staff_icno" value="<?= Html::encode($staffIcno) ?>">
                                            <button class="grid-action grid-action-danger" type="submit"><i data-lucide="trash-2"></i><span>Buang</span></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="page-section-body">
            <section class="content-inner-card">
                <div class="profile-card-header grid-card-header">
                    <h2><?= $wpId === null ? 'Tambah WBB' : 'Kemaskini WBB' ?></h2>
                    <span><?= Html::encode($staffSubtitle) ?></span>
                </div>
                <form class="record-form" method="post" action="<?= Html::encode($actionUrl) ?>">
                    <?= $csrf->hiddenInput() ?>
                    <div class="record-form-grid">
                        <label class="grid-field grid-field-wide">
                            <span>WBB <b>*</b></span>
                            <select name="wp_id" data-searchable-select required>
                                <option value="">Pilih WP</option>
                                <?php foreach ($data['options'] as $option): ?>
                                    <option value="<?= Html::encode((string) $option['id']) ?>" <?= (string) $option['id'] === (string) $form['wpId'] ? 'selected' : '' ?>>
                                        <?= Html::encode((string) $option['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label class="grid-field">
                            <span>Tarikh Mula <b>*</b></span>
                            <input type="date" name="start_date" value="<?= Html::encode((string) $form['startDate']) ?>" required>
                        </label>

                        <label class="grid-field">
                            <span>Tarikh Tamat</span>
                            <input type="date" name="end_date" value="<?= Html::encode((string) $form['endDate']) ?>">
                        </label>
                    </div>
                    <div class="record-form-actions">
                        <a class="button-link button-link-secondary" href="/kehadiran/wbb-list?id=<?= Html::encode(rawurlencode($staffIcno)) ?>"><i data-lucide="rotate-ccw"></i><span>Reset</span></a>
                        <button class="button-link-success" type="submit"><i data-lucide="save"></i><span><?= $wpId === null ? 'Tambah WBB' : 'Kemaskini WBB' ?></span></button>
                    </div>
                </form>
            </section>
        </div>
    </section>
</main>
