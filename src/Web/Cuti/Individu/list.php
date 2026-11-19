<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use App\Shared\LegacyUrl;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var string $title
 * @var list<array<string, mixed>> $rows
 * @var list<array{id: string, name: string}> $years
 * @var list<array{id: string, name: string}> $leaveTypes
 * @var int $year
 * @var int|null $leaveTypeId
 * @var list<array<string, mixed>> $menuSections
 */

$this->setTitle($title . ' - ' . $applicationParams->name);
?>

<main class="cuti-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p>Cuti</p>
            </div>
            <a class="button-link button-link-secondary" href="/cuti/individu/index">
                <i data-lucide="arrow-left"></i>
                <span>Kembali</span>
            </a>
        </div>

        <div class="page-section-body page-card-section">
            <?php require dirname(__DIR__) . '/Shared/menu.php'; ?>
        </div>

        <div class="page-section-body">
            <section class="dashboard-panel">
                <form class="filter-bar" method="get" action="/cuti/individu/list">
                    <label>
                        <span>Tahun</span>
                        <select name="year" data-searchable-select>
                            <?php foreach ($years === [] ? [['id' => (string) $year, 'name' => (string) $year]] : $years as $option): ?>
                                <option value="<?= Html::encode($option['id']) ?>"<?= (int) $option['id'] === $year ? ' selected' : '' ?>><?= Html::encode($option['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <span>Jenis Cuti</span>
                        <select name="jenis_cuti_id" data-searchable-select>
                            <option value="">Semua</option>
                            <?php foreach ($leaveTypes as $option): ?>
                                <option value="<?= Html::encode($option['id']) ?>"<?= $leaveTypeId !== null && (int) $option['id'] === $leaveTypeId ? ' selected' : '' ?>><?= Html::encode($option['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button class="button-link" type="submit">
                        <i data-lucide="search"></i>
                        <span>Search</span>
                    </button>
                </form>
            </section>
        </div>

        <div class="page-section-body">
            <section class="dashboard-panel">
                <div class="dashboard-panel-header">
                    <h2>Senarai Permohonan</h2>
                    <span><?= count($rows) ?> rekod</span>
                </div>
                <?php if ($rows === []): ?>
                    <div class="profile-empty">Tiada rekod ditemui.</div>
                <?php else: ?>
                    <div class="responsive-table">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Period</th>
                                    <th>Remark</th>
                                    <th>Status</th>
                                    <th>Details</th>
                                    <th>File</th>
                                    <th>Upload</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td><?= Html::encode((string) $row['jenis_cuti_label']) ?></td>
                                        <td><?= Html::encode((string) $row['date_label']) ?></td>
                                        <td><?= Html::encode((string) $row['tempoh_label']) ?></td>
                                        <td><?= Html::encode((string) ($row['remark'] ?? '-')) ?></td>
                                        <td><span class="status-badge"><?= Html::encode((string) $row['status_label']) ?></span></td>
                                        <td><a class="grid-action" href="/cuti/individu/leave-detail?id=<?= Html::encode((string) $row['id']) ?>"><i data-lucide="eye"></i><span>Lihat</span></a></td>
                                        <?php if ((int) ($row['jenis_cuti_id'] ?? 0) === 28 && (string) ($row['status'] ?? '') === 'APPROVED'): ?>
                                            <td><a class="grid-action" href="<?= Html::encode(LegacyUrl::to('cuti/individu/surat') . '?id=' . (string) $row['id']) ?>" target="_blank" rel="noopener"><i data-lucide="download"></i><span>Surat</span></a></td>
                                            <td><a class="grid-action" href="<?= Html::encode(LegacyUrl::to('cuti/individu/upload') . '?id=' . (string) $row['id']) ?>" target="_blank" rel="noopener"><i data-lucide="upload"></i><span>Upload</span></a></td>
                                        <?php else: ?>
                                            <td></td>
                                            <td></td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </section>
</main>
