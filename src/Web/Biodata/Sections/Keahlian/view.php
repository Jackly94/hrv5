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
 *     icno: string,
 *     name: string,
 *     staffNo: string,
 *     rows: list<array<string, string>>
 * }|null $memberships
 * @var string $status
 * @var string $backUrl
 * @var array<string, string> $actionQuery
 */

$this->setTitle('Keahlian - ' . $applicationParams->name);
$rows = $memberships['rows'] ?? [];
$actionQuery = is_array($actionQuery ?? null) ? $actionQuery : [];
$sectionUrl = static function (string $url, array $query) {
    if ($query === []) {
        return $url;
    }

    return $url . (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
};
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1>Keahlian</h1>
                <p>
                    <?= $memberships === null
                        ? 'Kelab, persatuan, institusi dan kesatuan'
                        : Html::encode($memberships['name']) ?>
                </p>
            </div>
            <div class="profile-actions">
                <a class="button-link button-link-secondary" href="<?= Html::encode($backUrl) ?>">
                    <i data-lucide="arrow-left"></i>
                    <span>Kembali</span>
                </a>
                <a class="button-link" href="<?= Html::encode($sectionUrl('/biodata/keahlian/tambahbadanprofesional', $actionQuery)) ?>">
                    <i data-lucide="plus"></i>
                    <span>Tambah Keahlian</span>
                </a>
            </div>
        </div>

        <?php if ($memberships === null): ?>
            <div class="profile-empty">Rekod staf tidak dijumpai.</div>
        <?php else: ?>
            <div class="page-card-section page-section-body">
                <section class="content-inner-card">
                    <div class="profile-card-header grid-card-header grid-result-header">
                        <div>
                            <h2>Keahlian (Kelab/Persatuan/Institusi/Kesatuan)</h2>
                        </div>
                    </div>

                    <div class="table-scroll grid-table-wrap">
                        <table class="data-table membership-table">
                            <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Peringkat</th>
                                <th>No. Keahlian</th>
                                <th>Tarikh Mula Menyertai</th>
                                <th>Tarikh Tamat Menyertai</th>
                                <th>Yuran Dikenakan</th>
                                <th>Status</th>
                                <th>Tindakan</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if ($rows === []): ?>
                                <tr>
                                    <td class="grid-empty" colspan="8">Tiada Rekod</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($rows as $membership): ?>
                                <?php
                                $id = rawurlencode($membership['id']);
                                $statusClass = $membership['statusCode'] === '0' || $membership['statusCode'] === ''
                                    ? 'status-badge-danger'
                                    : 'status-badge-success';
                                ?>
                                <tr>
                                    <td class="grid-name"><?= Html::encode($membership['body']) ?></td>
                                    <td><?= Html::encode($membership['level']) ?></td>
                                    <td><?= Html::encode($membership['membershipNo']) ?></td>
                                    <td><?= Html::encode($membership['startDate']) ?></td>
                                    <td><?= Html::encode($membership['endDate']) ?></td>
                                    <td><?= Html::encode($membership['fee']) ?></td>
                                    <td><span class="status-badge <?= Html::encode($statusClass) ?>"><?= Html::encode($membership['status']) ?></span></td>
                                    <td>
                                        <div class="grid-actions-inline">
                                            <a class="grid-action" href="<?= Html::encode($sectionUrl('/biodata/keahlian/lihatbadanprofesional?id=' . $id, $actionQuery)) ?>">
                                                <i data-lucide="eye"></i>
                                                <span>Lihat</span>
                                            </a>
                                            <a class="grid-action" href="<?= Html::encode($sectionUrl('/biodata/keahlian/update?id=' . $id, $actionQuery)) ?>">
                                                <i data-lucide="pencil"></i>
                                                <span>Kemaskini</span>
                                            </a>
                                            <form class="grid-action-form" action="<?= Html::encode($sectionUrl('/biodata/keahlian/delete?id=' . $id, $actionQuery)) ?>" method="post" onsubmit="return window.confirm('Padam rekod ini?');">
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
