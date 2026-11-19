<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var string $title
 * @var list<array<string, mixed>> $rows
 * @var list<array{id: string, name: string}> $years
 * @var int $year
 * @var list<array<string, mixed>> $menuSections
 * @var string $legacyUrl
 */

$this->setTitle($title . ' - ' . $applicationParams->name);
?>

<main class="cuti-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p>Menggantikan</p>
            </div>
            <a class="button-link button-link-secondary" href="/cuti/individu/index"><i data-lucide="arrow-left"></i><span>Kembali</span></a>
        </div>

        <div class="page-section-body page-card-section">
            <?php require dirname(__DIR__) . '/Shared/menu.php'; ?>
        </div>

        <div class="page-section-body">
            <section class="dashboard-panel">
                <form class="filter-bar" method="get" action="/cuti/individu/substitute-list">
                    <label>
                        <span>Tahun</span>
                        <select name="year" data-searchable-select>
                            <?php foreach ($years === [] ? [['id' => (string) $year, 'name' => (string) $year]] : $years as $option): ?>
                                <option value="<?= Html::encode($option['id']) ?>"<?= (int) $option['id'] === $year ? ' selected' : '' ?>><?= Html::encode($option['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button class="button-link" type="submit"><i data-lucide="search"></i><span>Search</span></button>
                    <a class="grid-action" href="<?= Html::encode($legacyUrl) ?>" target="_blank" rel="noopener"><i data-lucide="external-link"></i><span>Yii2</span></a>
                </form>
            </section>
        </div>

        <div class="page-section-body">
            <section class="dashboard-panel">
                <div class="dashboard-panel-header">
                    <h2>Substitute For</h2>
                    <span><?= count($rows) ?> rekod</span>
                </div>
                <?php if ($rows === []): ?>
                    <div class="profile-empty">Tiada rekod ditemui.</div>
                <?php else: ?>
                    <div class="responsive-table">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Substitute For</th>
                                    <th>Leave Start</th>
                                    <th>Leave End</th>
                                    <th>Duration</th>
                                    <th>Remark</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td><?= Html::encode((string) ($row['staff_name'] ?? '-')) ?></td>
                                        <td><?= Html::encode((string) ($row['start_date'] ?? '-')) ?></td>
                                        <td><?= Html::encode((string) ($row['end_date'] ?? '-')) ?></td>
                                        <td><?= Html::encode((string) ($row['tempoh_label'] ?? '-')) ?></td>
                                        <td><?= Html::encode((string) ($row['remark'] ?? '-')) ?></td>
                                        <td><span class="status-badge"><?= Html::encode((string) ($row['status_label'] ?? '-')) ?></span></td>
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
