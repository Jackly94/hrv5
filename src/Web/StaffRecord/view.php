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
 * @var array{
 *     key: string,
 *     title: string,
 *     subtitle: string,
 *     legacyBase: string,
 *     addAction: string,
 *     viewAction: string,
 *     updateAction: string,
 *     idKey: string,
 *     columns: list<string>,
 *     icno: string,
 *     name: string,
 *     staffNo: string,
 *     rows: list<array{id: string, cells: list<string>, statusCode?: string}>
 * }|null $section
 * @var string $status
 */

$title = $section['title'] ?? 'Rekod Kakitangan';
$this->setTitle($title . ' - ' . $applicationParams->name);
$rows = $section['rows'] ?? [];
$columns = $section['columns'] ?? [];
$columnCount = count($columns) + 1;
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p>
                    <?= $section === null
                        ? 'Maklumat dan rekod kakitangan'
                        : Html::encode($section['name']) ?>
                </p>
            </div>
            <?php if ($section !== null): ?>
                <div class="profile-actions">
                    <a class="button-link button-link-secondary" href="/biodata/userview">
                        <i data-lucide="arrow-left"></i>
                        <span>Kembali</span>
                    </a>
                    <a class="button-link" href="<?= Html::encode($section['legacyBase'] . '/' . $section['addAction']) ?>">
                        <i data-lucide="plus"></i>
                        <span>Tambah</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($section === null): ?>
            <div class="profile-empty">Rekod staf tidak dijumpai.</div>
        <?php else: ?>
            <div class="page-card-section page-section-body">
                <section class="content-inner-card">
                    <div class="profile-card-header grid-card-header grid-result-header">
                        <div>
                            <h2><?= Html::encode($section['title']) ?></h2>
                        </div>
                    </div>

                    <div class="table-scroll grid-table-wrap">
                        <table class="data-table staff-record-table staff-record-table-<?= Html::encode($section['key']) ?>">
                            <thead>
                            <tr>
                                <?php foreach ($columns as $column): ?>
                                    <th><?= Html::encode($column) ?></th>
                                <?php endforeach; ?>
                                <th>Tindakan</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if ($rows === []): ?>
                                <tr>
                                    <td class="grid-empty" colspan="<?= Html::encode((string) $columnCount) ?>">Tiada Rekod</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($rows as $row): ?>
                                <?php $id = rawurlencode($row['id']); ?>
                                <tr>
                                    <?php foreach ($row['cells'] as $index => $cell): ?>
                                        <?php
                                        $isStatusColumn = $section['key'] === 'anugerah' && $index === count($row['cells']) - 1;
                                        $statusClass = ($row['statusCode'] ?? '') === '0' || ($row['statusCode'] ?? '') === ''
                                            ? 'status-badge-danger'
                                            : 'status-badge-success';
                                        ?>
                                        <td<?= $index === 0 ? ' class="grid-name"' : '' ?>>
                                            <?php if ($isStatusColumn): ?>
                                                <span class="status-badge <?= Html::encode($statusClass) ?>"><?= Html::encode($cell) ?></span>
                                            <?php else: ?>
                                                <?= Html::encode($cell) ?>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td>
                                        <div class="grid-actions-inline">
                                            <a class="grid-action" href="<?= Html::encode($section['legacyBase'] . '/' . $section['viewAction'] . '?id=' . $id) ?>">
                                                <i data-lucide="eye"></i>
                                                <span>Lihat</span>
                                            </a>
                                            <a class="grid-action" href="<?= Html::encode($section['legacyBase'] . '/' . $section['updateAction'] . '?id=' . $id) ?>">
                                                <i data-lucide="pencil"></i>
                                                <span>Kemaskini</span>
                                            </a>
                                            <form class="grid-action-form" action="<?= Html::encode($section['legacyBase'] . '/delete?id=' . $id) ?>" method="post" onsubmit="return window.confirm('Padam rekod ini?');">
                                                <?= $csrf->hiddenInput() ?>
                                                <button class="grid-action grid-action-danger" type="submit">
                                                    <i data-lucide="trash-2"></i>
                                                    <span>Padam</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="grid-count-footer">
                        <span class="grid-count">Papar <?= Html::encode((string) count($rows)) ?> rekod</span>
                    </div>
                </section>
            </div>
        <?php endif; ?>
    </section>
</main>
