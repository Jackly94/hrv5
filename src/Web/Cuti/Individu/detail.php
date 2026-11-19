<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var array<string, mixed>|null $record
 */

$this->setTitle('Butiran Cuti - ' . $applicationParams->name);
?>

<main class="cuti-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1>Butiran Cuti</h1>
                <p><?= $record === null ? 'Tiada rekod' : Html::encode((string) $record['date_label']) ?></p>
            </div>
            <a class="button-link button-link-secondary" href="/cuti/individu/list">
                <i data-lucide="arrow-left"></i>
                <span>Kembali</span>
            </a>
        </div>

        <div class="page-section-body">
            <?php if ($record === null): ?>
                <div class="profile-empty">Rekod tidak ditemui atau bukan milik sesi semasa.</div>
            <?php else: ?>
                <section class="dashboard-panel">
                    <dl class="record-detail-list">
                        <dt>Jenis Cuti</dt>
                        <dd><?= Html::encode((string) $record['jenis_cuti_label']) ?></dd>
                        <dt>Nama</dt>
                        <dd><?= Html::encode((string) ($record['staff_name'] ?? '-')) ?></dd>
                        <dt>No. Staf</dt>
                        <dd><?= Html::encode((string) ($record['staff_no'] ?? '-')) ?></dd>
                        <dt>Jawatan</dt>
                        <dd><?= Html::encode((string) ($record['position_name'] ?? '-')) ?></dd>
                        <dt>Tarikh</dt>
                        <dd><?= Html::encode((string) $record['date_label']) ?></dd>
                        <dt>Tempoh</dt>
                        <dd><?= Html::encode((string) $record['tempoh_label']) ?></dd>
                        <dt>Status</dt>
                        <dd><span class="status-badge"><?= Html::encode((string) $record['status_label']) ?></span></dd>
                        <dt>Catatan</dt>
                        <dd><?= Html::encode((string) ($record['remark'] ?? '-')) ?></dd>
                        <dt>Destinasi</dt>
                        <dd><?= Html::encode((string) ($record['destination'] ?? '-')) ?></dd>
                        <dt>Pengganti</dt>
                        <dd><?= Html::encode((string) ($record['ganti_name'] ?? '-')) ?></dd>
                        <dt>Semakan</dt>
                        <dd><?= Html::encode((string) ($record['semakan_name'] ?? '-')) ?></dd>
                        <dt>Peraku</dt>
                        <dd><?= Html::encode((string) ($record['peraku_name'] ?? '-')) ?></dd>
                        <dt>Pelulus</dt>
                        <dd><?= Html::encode((string) ($record['lulus_name'] ?? '-')) ?></dd>
                        <dt>Mohon Pada</dt>
                        <dd><?= Html::encode((string) ($record['mohon_dt'] ?? '-')) ?></dd>
                        <dt>Fail</dt>
                        <dd>
                            <?= Html::encode((string) ($record['file_hashcode'] ?? '-')) ?>
                            <?php if (!empty($record['file1'])): ?>
                                <br><?= Html::encode((string) $record['file1']) ?>
                            <?php endif; ?>
                            <?php if (!empty($record['file2'])): ?>
                                <br><?= Html::encode((string) $record['file2']) ?>
                            <?php endif; ?>
                        </dd>
                    </dl>
                </section>
            <?php endif; ?>
        </div>
    </section>
</main>
