<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var string $title
 * @var list<array<string, mixed>> $statement
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
                <p>Penyata Cuti</p>
            </div>
            <a class="button-link button-link-secondary" href="/cuti/individu/index"><i data-lucide="arrow-left"></i><span>Kembali</span></a>
        </div>

        <div class="page-section-body page-card-section">
            <?php require dirname(__DIR__) . '/Shared/menu.php'; ?>
        </div>

        <div class="page-section-body">
            <section class="dashboard-panel">
                <form class="filter-bar" method="get" action="/cuti/individu/statement">
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

        <?php if ($statement === []): ?>
            <div class="page-section-body"><div class="profile-empty">Tiada rekod ditemui.</div></div>
        <?php endif; ?>

        <?php foreach ($statement as $section): ?>
            <?php /** @var array<string, mixed> $entitlement */
            $entitlement = $section['entitlement']; ?>
            <div class="page-section-body">
                <section class="dashboard-panel">
                    <div class="dashboard-panel-header">
                        <h2><?= Html::encode((string) ($entitlement['layak_mula'] ?? '-')) ?> - <?= Html::encode((string) ($entitlement['layak_tamat'] ?? '-')) ?></h2>
                        <span>Total <?= Html::encode((string) ($entitlement['total_layak'] ?? '0')) ?></span>
                    </div>
                    <div class="responsive-table">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>BCTL</th>
                                    <th>Entitlement</th>
                                    <th>Total</th>
                                    <th>CBTH</th>
                                    <th>GCR</th>
                                    <th>Discarded</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?= Html::encode((string) ($entitlement['layak_bawa_lepas'] ?? '0')) ?></td>
                                    <td><?= Html::encode((string) ($entitlement['layak_cuti'] ?? '0')) ?></td>
                                    <td><?= Html::encode((string) ($entitlement['total_layak'] ?? '0')) ?></td>
                                    <td><?= Html::encode((string) ($entitlement['layak_bawa_depan'] ?? '0')) ?></td>
                                    <td><?= Html::encode((string) ($entitlement['layak_gcr'] ?? '0')) ?></td>
                                    <td><?= Html::encode((string) ($entitlement['layak_hapus'] ?? '0')) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <?php /** @var list<array<string, mixed>> $records */
                    $records = $section['records']; ?>
                    <?php if ($records !== []): ?>
                        <div class="responsive-table">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Leave Date</th>
                                        <th>Period</th>
                                        <th>Cumulative</th>
                                        <th>Balance</th>
                                        <th>Leave Type</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($records as $row): ?>
                                        <tr>
                                            <td><?= Html::encode((string) ($row['date_label'] ?? '-')) ?></td>
                                            <td><?= Html::encode((string) ($row['tempoh_label'] ?? '0')) ?></td>
                                            <td><?= Html::encode((string) ($row['cumulative'] ?? '0')) ?></td>
                                            <td><?= Html::encode((string) ($row['balance'] ?? '0')) ?></td>
                                            <td><?= Html::encode((string) ($row['jenis_cuti_label'] ?? '-')) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="profile-empty">Tiada rekod cuti untuk tempoh kelayakan ini.</div>
                    <?php endif; ?>
                </section>
            </div>
        <?php endforeach; ?>
    </section>
</main>
