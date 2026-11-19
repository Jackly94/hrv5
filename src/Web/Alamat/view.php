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
 * @var array{name: string, rows: list<array<string, string>>}|null $addresses
 * @var string $status
 */

$this->setTitle('Alamat - ' . $applicationParams->name);
$rows = $addresses['rows'] ?? [];
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1>Alamat</h1>
                <p><?= $addresses === null ? 'Maklumat alamat kakitangan' : Html::encode($addresses['name']) ?></p>
            </div>
            <?php if ($addresses !== null): ?>
                <div class="profile-actions">
                    <a class="button-link button-link-secondary" href="/biodata/userview">
                        <i data-lucide="arrow-left"></i>
                        <span>Kembali</span>
                    </a>
                    <a class="button-link" href="/alamat/tambahalamat">
                        <i data-lucide="plus"></i>
                        <span>Tambah Alamat</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($addresses === null): ?>
            <div class="profile-empty">Rekod staf tidak dijumpai.</div>
        <?php else: ?>
            <div class="page-card-section page-section-body">
                <section class="content-inner-card">
                    <div class="profile-card-header grid-card-header grid-result-header">
                        <div>
                            <h2>Senarai Alamat</h2>
                        </div>
                    </div>

                    <div class="table-scroll grid-table-wrap">
                        <table class="data-table address-table">
                            <thead>
                            <tr>
                                <th>Jenis Alamat</th>
                                <th>Alamat</th>
                                <th>Alamat 2</th>
                                <th>Alamat 3</th>
                                <th>Tindakan</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if ($rows === []): ?>
                                <tr>
                                    <td class="grid-empty" colspan="5">Tiada Rekod</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($rows as $address): ?>
                                <?php $id = rawurlencode($address['id']); ?>
                                <tr>
                                    <td class="grid-name"><?= Html::encode($address['type']) ?></td>
                                    <td><?= Html::encode($address['addr1']) ?></td>
                                    <td><?= Html::encode($address['addr2']) ?></td>
                                    <td><?= Html::encode($address['addr3']) ?></td>
                                    <td>
                                        <div class="grid-actions-inline">
                                            <a class="grid-action" href="/alamat/lihatalamat?id=<?= Html::encode($id) ?>">
                                                <i data-lucide="eye"></i>
                                                <span>Lihat</span>
                                            </a>
                                            <a class="grid-action" href="/alamat/update?id=<?= Html::encode($id) ?>">
                                                <i data-lucide="pencil"></i>
                                                <span>Kemaskini</span>
                                            </a>
                                            <form class="grid-action-form" action="/alamat/delete?id=<?= Html::encode($id) ?>" method="post" onsubmit="return window.confirm('Padam rekod alamat ini?');">
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
