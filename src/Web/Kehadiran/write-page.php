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
 * @var array{slug: string, kind: string, title: string, back: string} $page
 * @var array<string, mixed> $record
 */

$this->setTitle($page['title'] . ' - STARS - ' . $applicationParams->name);
$badgeClass = static fn (string $status): string => match (strtoupper($status)) {
    'APPROVED', 'VERIFIED', 'GREEN', 'WFH' => 'status-badge-success',
    'REJECTED', 'RED', 'NO' => 'status-badge-danger',
    'ENTRY', 'RETURNED', 'YELLOW' => 'status-badge-warning',
    default => '',
};
$statusBadges = static function (array $labels) use ($badgeClass): string {
    if ($labels === []) {
        return '-';
    }

    $html = '<div class="attendance-status-stack">';
    foreach ($labels as $label) {
        $html .= '<span class="status-badge ' . Html::encode($badgeClass((string) $label)) . '">' . Html::encode((string) $label) . '</span>';
    }

    return $html . '</div>';
};
?>

<main class="attendance-page">
    <section class="profile-card page-main-card attendance-main-card">
        <div class="dashboard-band dashboard-heading page-card-heading">
            <div>
                <h1>STARS</h1>
                <p><?= Html::encode($page['title']) ?></p>
            </div>
            <a class="button-link button-link-secondary" href="<?= Html::encode($page['back']) ?>"><i data-lucide="arrow-left"></i><span>Kembali</span></a>
        </div>

        <div class="page-section-body page-card-section">
            <section class="content-inner-card attendance-standard-toolbar">
                <div class="attendance-standard-title">
                    <span class="attendance-standard-icon <?= $page['kind'] === 'wfh-delete' ? 'attendance-icon-danger' : 'attendance-icon-success' ?>" aria-hidden="true">
                        <i data-lucide="<?= $page['kind'] === 'wfh-delete' ? 'trash-2' : 'badge-check' ?>"></i>
                    </span>
                    <div>
                        <h2><?= Html::encode($page['title']) ?></h2>
                        <p><?= Html::encode((string) ($record['staffName'] ?? 'Tiada Rekod')) ?></p>
                    </div>
                </div>
                <div class="attendance-standard-count">
                    <span>Status</span>
                    <strong><?= Html::encode((string) ($record['status'] ?? 'ENTRY')) ?></strong>
                </div>
            </section>
        </div>

        <div class="page-section-body page-card-section">
            <section class="content-inner-card staff-page-panel attendance-compact-panel">
                <div class="responsive-table">
                    <table class="data-table attendance-compact-table">
                        <tbody>
                            <tr><th>Nama</th><td><?= Html::encode((string) ($record['staffName'] ?? '-')) ?></td></tr>
                            <tr><th>JFPIB</th><td><?= Html::encode((string) ($record['department'] ?? '-')) ?></td></tr>
                            <?php if ($page['kind'] === 'reason'): ?>
                                <tr><th>Tarikh</th><td><?= Html::encode((string) $record['date']) ?> / <?= Html::encode((string) $record['day']) ?></td></tr>
                                <tr><th>WBB</th><td><?= Html::encode((string) $record['wbb']) ?></td></tr>
                                <tr><th>Masa</th><td><?= Html::encode((string) $record['timeIn']) ?> - <?= Html::encode((string) $record['timeOut']) ?></td></tr>
                                <tr><th>Ketidakpatuhan</th><td><?= $statusBadges($record['statusLabels']) ?></td></tr>
                                <tr><th>Catatan Pemohon</th><td><?= Html::encode((string) $record['remark']) ?></td></tr>
                            <?php elseif (str_starts_with($page['kind'], 'wfh')): ?>
                                <tr><th>Tarikh WFH</th><td><?= Html::encode((string) $record['date']) ?></td></tr>
                                <tr><th>Tempoh</th><td><?= Html::encode((string) $record['period']) ?></td></tr>
                                <tr><th>Catatan</th><td><?= Html::encode((string) $record['remark']) ?></td></tr>
                                <?php if (($record['documentUrl'] ?? '') !== ''): ?><tr><th>Dokumen</th><td><a class="grid-action" href="<?= Html::encode((string) $record['documentUrl']) ?>" target="_blank" rel="noopener"><i data-lucide="download"></i><span>File</span></a></td></tr><?php endif; ?>
                            <?php else: ?>
                                <tr><th>Tarikh Absent</th><td><?= Html::encode((string) $record['date']) ?> / <?= Html::encode((string) $record['day']) ?></td></tr>
                                <tr><th>Remark Absent</th><td><?= Html::encode((string) $record['absentRemark']) ?></td></tr>
                                <tr><th>Catatan Pemohon</th><td><?= Html::encode((string) $record['remark']) ?></td></tr>
                                <?php if (($record['verifierRemark'] ?? '') !== ''): ?><tr><th>Catatan Penyelia</th><td><?= Html::encode((string) $record['verifierRemark']) ?></td></tr><?php endif; ?>
                                <?php if (($record['documentUrl'] ?? '') !== ''): ?><tr><th>Dokumen</th><td><a class="grid-action" href="<?= Html::encode((string) $record['documentUrl']) ?>" target="_blank" rel="noopener"><i data-lucide="download"></i><span>File</span></a></td></tr><?php endif; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="page-section-body page-card-section">
            <section class="content-inner-card attendance-standard-toolbar">
                <form class="attendance-standard-filter" method="post" action="/kehadiran/<?= Html::encode($page['slug']) ?>?id=<?= Html::encode((string) $record['id']) ?>">
                    <?= $csrf->hiddenInput() ?>
                    <input type="hidden" name="redirect" value="<?= Html::encode($page['back']) ?>">
                    <?php if ($page['kind'] === 'wfh-delete'): ?>
                        <div class="attendance-standard-title">
                            <span class="attendance-standard-icon attendance-icon-danger" aria-hidden="true"><i data-lucide="trash-2"></i></span>
                            <div>
                                <h2>Padam rekod ini?</h2>
                                <p>Tindakan ini akan memadam permohonan WFH yang dipilih.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="attendance-standard-fields attendance-standard-fields-wide">
                            <label>Status
                                <select name="status" required>
                                    <option value="">Pilih status</option>
                                    <?php if ($page['kind'] === 'reason'): ?>
                                        <option value="APPROVED">DITERIMA</option>
                                        <option value="REJECTED">DITOLAK</option>
                                    <?php elseif ($page['kind'] === 'wfh-approval'): ?>
                                        <option value="APPROVED">DILULUSKAN</option>
                                        <option value="REJECTED">DITOLAK</option>
                                    <?php elseif ($page['kind'] === 'thtc-verify'): ?>
                                        <option value="2">VERIFIED</option>
                                        <option value="4">RETURNED</option>
                                    <?php elseif ($page['kind'] === 'thtc-approve'): ?>
                                        <option value="3">APPROVED</option>
                                        <option value="5">REJECTED</option>
                                    <?php endif; ?>
                                </select>
                            </label>
                            <label>Catatan
                                <textarea name="remark" rows="4"><?= Html::encode((string) ($record['appRemark'] ?? '')) ?></textarea>
                            </label>
                        </div>
                    <?php endif; ?>
                    <div class="attendance-standard-actions">
                        <button class="<?= $page['kind'] === 'wfh-delete' ? 'grid-action grid-action-danger' : 'button-link' ?>" type="submit">
                            <i data-lucide="<?= $page['kind'] === 'wfh-delete' ? 'trash-2' : 'save' ?>"></i>
                            <span><?= $page['kind'] === 'wfh-delete' ? 'Padam' : 'Hantar' ?></span>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </section>
</main>
