<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use App\Shared\LegacyUrl;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var list<array<string, mixed>> $rows
 * @var bool $authorized
 * @var list<array<string, mixed>> $menuSections
 * @var list<array{id: string, name: string}> $departments
 * @var list<array{id: string, name: string}> $campuses
 * @var array<string, mixed> $query
 */

$this->setTitle('Senarai Kakitangan Seliaan - Cuti - ' . $applicationParams->name);
$selected = static fn (string $key, string $value): string => isset($query[$key]) && (string) $query[$key] === $value ? ' selected' : '';
$value = static fn (string $key): string => isset($query[$key]) && is_scalar($query[$key]) ? (string) $query[$key] : '';
$rowNumber = 1;
?>

<main class="cuti-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1>Senarai Kakitangan Seliaan</h1>
                <p>Cuti</p>
            </div>
            <a class="button-link button-link-secondary" href="/cuti/individu/index"><i data-lucide="arrow-left"></i><span>Kembali</span></a>
        </div>

        <div class="page-section-body page-card-section">
            <?php require dirname(__DIR__) . '/Shared/menu.php'; ?>
        </div>

        <div class="page-section-body page-card-section">
            <section class="content-inner-card attendance-standard-toolbar">
                <div class="attendance-standard-title">
                    <span class="attendance-standard-icon attendance-icon-info" aria-hidden="true"><i data-lucide="eye"></i></span>
                    <div>
                        <h2>Pemantau</h2>
                        <p><?= Html::encode($authorized ? 'Kakitangan dalam skop pemantauan cuti.' : 'Access is not available for this account.') ?></p>
                    </div>
                </div>
                <?php if ($authorized): ?>
                    <form class="attendance-standard-filter" method="get" action="/cuti/observer/my-staff">
                        <div class="attendance-standard-fields attendance-standard-fields-wide">
                            <label>Search <input type="text" name="q" value="<?= Html::encode($value('q')) ?>"></label>
                            <label>Department
                                <select name="department" data-searchable-select>
                                    <option value="">All</option>
                                    <?php foreach ($departments as $option): ?>
                                        <option value="<?= Html::encode($option['id']) ?>"<?= $selected('department', $option['id']) ?>><?= Html::encode($option['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>Campus
                                <select name="campus" data-searchable-select>
                                    <option value="">All</option>
                                    <?php foreach ($campuses as $option): ?>
                                        <option value="<?= Html::encode($option['id']) ?>"<?= $selected('campus', $option['id']) ?>><?= Html::encode($option['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        </div>
                        <div class="attendance-standard-actions"><button class="button-link" type="submit"><i data-lucide="search"></i><span>Search</span></button></div>
                    </form>
                <?php else: ?>
                    <div class="attendance-standard-count"><span>Records</span><strong>0</strong></div>
                <?php endif; ?>
            </section>
        </div>

        <div class="page-section-body page-card-section">
            <section class="content-inner-card staff-page-panel attendance-compact-panel">
                <?php if (!$authorized): ?>
                    <div class="profile-empty">Anda tidak mempunyai akses untuk paparan ini.</div>
                <?php elseif ($rows === []): ?>
                    <div class="profile-empty">Tiada rekod ditemui.</div>
                <?php else: ?>
                    <div class="responsive-table">
                        <table class="data-table attendance-compact-table">
                            <thead>
                                <tr>
                                    <th>Bil</th>
                                    <th>Status GCR/CBTH</th>
                                    <th>Staff No</th>
                                    <th>Nama</th>
                                    <th>Gred</th>
                                    <th>Jabatan</th>
                                    <th>Kelayakan</th>
                                    <th>Jumlah</th>
                                    <th>Baki</th>
                                    <th>Tindakan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $row): ?>
                                    <?php $staffIcno = (string) ($row['ICNO'] ?? ''); ?>
                                    <tr>
                                        <td><?= Html::encode((string) $rowNumber++) ?></td>
                                        <td><span class="status-badge"><?= Html::encode((string) ($row['gcr_status_label'] ?? '-')) ?></span></td>
                                        <td><?= Html::encode((string) ($row['staff_no'] ?? '-')) ?></td>
                                        <td><?= Html::encode((string) ($row['staff_name'] ?? '-')) ?></td>
                                        <td><?= Html::encode((string) ($row['grade_label'] ?? '-')) ?></td>
                                        <td><?= Html::encode((string) ($row['department_label'] ?? '-')) ?></td>
                                        <td><?= Html::encode((string) ($row['layak_mula'] ?? '-')) ?></td>
                                        <td><?= Html::encode((string) ($row['entitlement_total'] ?? '0')) ?></td>
                                        <td><?= Html::encode((string) ($row['leave_balance'] ?? '0')) ?></td>
                                        <td>
                                            <a class="grid-action" href="<?= Html::encode(LegacyUrl::to('cuti/observer/o-leave') . '?id=' . rawurlencode($staffIcno)) ?>" target="_blank" rel="noopener"><i data-lucide="calendar-check"></i><span>Yii2</span></a>
                                            <a class="grid-action" href="<?= Html::encode(LegacyUrl::to('cuti/observer/leave-list-o') . '?id=' . rawurlencode($staffIcno)) ?>" target="_blank" rel="noopener"><i data-lucide="calendar-plus"></i><span>Yii2</span></a>
                                            <a class="grid-action" href="<?= Html::encode(LegacyUrl::to('cuti/observer/leave-statement-o') . '?id=' . rawurlencode($staffIcno)) ?>" target="_blank" rel="noopener"><i data-lucide="file-text"></i><span>Yii2</span></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                <div class="grid-count-footer">
                    <span class="grid-count">Papar <?= Html::encode((string) count($rows)) ?> rekod</span>
                </div>
            </section>
        </div>
    </section>
</main>
