<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var array<string, mixed>|null $overview
 */

$title = $overview['title'] ?? 'Permohonan Penukaran Akaun Bank';
$this->setTitle($title . ' - ' . $applicationParams->name);
$columns = $overview['columns'] ?? [];
$rows = $overview['rows'] ?? [];
$columnCount = count($columns) + 1;
$statusClass = static function (string $status): string {
    return match ($status) {
        'Diluluskan' => 'status-badge-success',
        'Ditolak', 'Dikembalikan' => 'status-badge-danger',
        default => 'status-badge-warning',
    };
};
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p><?= $overview === null ? 'Senarai permohonan penukaran akaun bank' : Html::encode((string) $overview['name']) ?></p>
            </div>
            <?php if ($overview !== null): ?>
                <div class="profile-actions">
                    <a class="button-link button-link-secondary" href="<?= Html::encode((string) $overview['backUrl']) ?>">
                        <i data-lucide="arrow-left"></i>
                        <span>Kembali</span>
                    </a>
                    <a class="button-link" href="<?= Html::encode((string) $overview['addUrl']) ?>">
                        <i data-lucide="plus"></i>
                        <span><?= Html::encode((string) $overview['addLabel']) ?></span>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($overview === null): ?>
            <div class="profile-empty">Rekod staf tidak dijumpai.</div>
        <?php else: ?>
            <div class="page-card-section page-section-body staff-overview-stack">
                <section class="content-inner-card">
                    <div class="profile-card-header grid-card-header grid-result-header">
                        <div>
                            <h2>Senarai Permohonan</h2>
                        </div>
                        <a class="grid-action staff-overview-add" href="<?= Html::encode((string) $overview['addUrl']) ?>">
                            <i data-lucide="plus"></i>
                            <span><?= Html::encode((string) $overview['addLabel']) ?></span>
                        </a>
                    </div>

                    <div class="record-form-alert">
                        <div class="alert alert-info"><?= Html::encode((string) $overview['info']) ?></div>
                    </div>

                    <div class="table-scroll grid-table-wrap">
                        <table class="data-table staff-overview-table staff-overview-table-account">
                            <thead>
                            <tr>
                                <?php foreach ($columns as $column): ?>
                                    <th><?= Html::encode((string) $column) ?></th>
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
                                        <?php $isStatusColumn = ($columns[$index] ?? '') === 'Status'; ?>
                                        <td<?= $index === 1 ? ' class="grid-name"' : '' ?>>
                                            <?php if ($isStatusColumn): ?>
                                                <span class="status-badge <?= Html::encode($statusClass((string) $cell)) ?>"><?= Html::encode((string) $cell) ?></span>
                                            <?php else: ?>
                                                <?= Html::encode((string) $cell) ?>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td>
                                        <div class="grid-actions-inline">
                                            <?php if (($row['viewUrl'] ?? '') !== ''): ?>
                                                <a class="grid-action" href="<?= Html::encode((string) $row['viewUrl']) ?>">
                                                    <i data-lucide="eye"></i>
                                                    <span>Lihat</span>
                                                </a>
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
            </div>
        <?php endif; ?>
    </section>
</main>
