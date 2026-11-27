<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var array<string, mixed>|null $detail
 * @var string $backUrl
 */

$title = $detail === null ? 'Lihat Rekod' : 'Lihat ' . $detail['title'];
$this->setTitle($title . ' - ' . $applicationParams->name);
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p><?= $detail === null ? 'Maklumat dan rekod kakitangan' : Html::encode($detail['name']) ?></p>
            </div>
            <?php if ($detail !== null): ?>
                <div class="profile-actions">
                    <a class="button-link button-link-secondary" href="<?= Html::encode($backUrl) ?>">
                        <i data-lucide="arrow-left"></i>
                        <span>Kembali</span>
                    </a>
                    <?php if (($detail['updateUrl'] ?? '') !== ''): ?>
                        <a class="button-link" href="<?= Html::encode($detail['updateUrl']) ?>"<?= str_starts_with((string) $detail['updateUrl'], 'http') ? ' target="_blank" rel="noopener"' : '' ?>>
                            <i data-lucide="pencil"></i>
                            <span>Kemaskini</span>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($detail === null): ?>
            <div class="profile-empty">Rekod tidak dijumpai.</div>
        <?php else: ?>
            <div class="page-card-section page-section-body">
                <section class="content-inner-card">
                    <div class="profile-card-header">
                        <h2><?= Html::encode($detail['groupTitle']) ?></h2>
                    </div>
                    <dl class="detail-list keluarga-detail-list">
                        <?php foreach ($detail['fields'] as $field): ?>
                            <div>
                                <dt><?= Html::encode($field['label']) ?></dt>
                                <dd>
                                    <?php if (($field['kind'] ?? '') === 'file' && ($field['url'] ?? '') !== ''): ?>
                                        <a class="grid-action" href="<?= Html::encode($field['url']) ?>" target="_blank" rel="noopener">
                                            <i data-lucide="file-text"></i>
                                            <span><?= Html::encode($field['value']) ?></span>
                                        </a>
                                    <?php else: ?>
                                        <?= Html::encode($field['value']) ?>
                                    <?php endif; ?>
                                </dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                </section>

                <?php foreach (($detail['tables'] ?? []) as $table): ?>
                    <section class="content-inner-card">
                        <div class="profile-card-header">
                            <h2><?= Html::encode((string) ($table['title'] ?? 'Maklumat')) ?></h2>
                        </div>
                        <div class="table-scroll grid-table-wrap">
                            <table class="data-table staff-overview-table">
                                <thead>
                                <tr>
                                    <?php foreach (($table['columns'] ?? []) as $column): ?>
                                        <th><?= Html::encode((string) $column) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if (($table['rows'] ?? []) === []): ?>
                                    <tr>
                                        <td class="grid-empty" colspan="<?= Html::encode((string) max(1, count($table['columns'] ?? []))) ?>">Tiada Rekod</td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach (($table['rows'] ?? []) as $row): ?>
                                    <tr>
                                        <?php foreach ($row as $cell): ?>
                                            <td><?= Html::encode((string) $cell) ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
