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
 * @var array<string, mixed>|null $overview
 */

$title = $overview['title'] ?? 'Rekod Kakitangan';
$this->setTitle($title . ' - ' . $applicationParams->name);
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p><?= $overview === null ? 'Maklumat dan rekod kakitangan' : Html::encode($overview['name']) ?></p>
            </div>
            <?php if ($overview !== null): ?>
                <div class="profile-actions">
                    <a class="button-link button-link-secondary" href="<?= Html::encode($overview['backUrl']) ?>">
                        <i data-lucide="arrow-left"></i>
                        <span>Kembali</span>
                    </a>
                    <?php if (($overview['addUrl'] ?? '') !== ''): ?>
                        <a class="button-link" href="<?= Html::encode($overview['addUrl']) ?>"<?= str_starts_with((string) $overview['addUrl'], 'http') ? ' target="_blank" rel="noopener"' : '' ?>>
                            <i data-lucide="plus"></i>
                            <span><?= Html::encode($overview['addLabel']) ?></span>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($overview === null): ?>
            <div class="profile-empty">Rekod staf tidak dijumpai.</div>
        <?php else: ?>
            <div class="page-card-section page-section-body staff-overview-stack">
                <?php foreach ($overview['groups'] as $group): ?>
                    <?php
                    $columns = $group['columns'] ?? [];
                    $rows = $group['rows'] ?? [];
                    $columnCount = count($columns) + 1;
                    ?>
                    <section class="content-inner-card">
                        <div class="profile-card-header grid-card-header grid-result-header">
                            <div>
                                <h2><?= Html::encode($group['title']) ?></h2>
                            </div>
                            <?php if (($group['addUrl'] ?? '') !== ''): ?>
                                <a class="grid-action staff-overview-add" href="<?= Html::encode($group['addUrl']) ?>"<?= str_starts_with((string) $group['addUrl'], 'http') ? ' target="_blank" rel="noopener"' : '' ?>>
                                    <i data-lucide="plus"></i>
                                    <span><?= Html::encode($group['addLabel']) ?></span>
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="table-scroll grid-table-wrap">
                            <table class="data-table staff-overview-table staff-overview-table-<?= Html::encode($group['variant'] ?? 'default') ?>">
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
                                    <tr>
                                        <?php foreach ($row['cells'] as $index => $cell): ?>
                                            <td<?= $index === 0 ? ' class="grid-name"' : '' ?>><?= Html::encode($cell) ?></td>
                                        <?php endforeach; ?>
                                        <td>
                                            <div class="grid-actions-inline">
                                                <?php if (($row['viewUrl'] ?? '') !== ''): ?>
                                                    <a class="grid-action" href="<?= Html::encode($row['viewUrl']) ?>">
                                                        <i data-lucide="eye"></i>
                                                        <span>Lihat</span>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (($row['updateUrl'] ?? '') !== ''): ?>
                                                    <a class="grid-action" href="<?= Html::encode($row['updateUrl']) ?>"<?= str_starts_with((string) $row['updateUrl'], 'http') ? ' target="_blank" rel="noopener"' : '' ?>>
                                                        <i data-lucide="pencil"></i>
                                                        <span>Kemaskini</span>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (($row['deleteUrl'] ?? '') !== ''): ?>
                                                    <?php if (str_starts_with((string) $row['deleteUrl'], '/')): ?>
                                                        <form class="grid-action-form" action="<?= Html::encode($row['deleteUrl']) ?>" method="post" onsubmit="return window.confirm('Padam rekod ini?');">
                                                            <?= $csrf->hiddenInput() ?>
                                                            <button class="grid-action grid-action-danger" type="submit">
                                                                <i data-lucide="trash-2"></i>
                                                                <span>Padam</span>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <a class="grid-action grid-action-danger" href="<?= Html::encode($row['deleteUrl']) ?>" target="_blank" rel="noopener">
                                                            <i data-lucide="trash-2"></i>
                                                            <span>Padam</span>
                                                        </a>
                                                    <?php endif; ?>
                                                <?php endif; ?>
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
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
