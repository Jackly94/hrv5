<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var array<string, string>|null $settings
 */

$this->setTitle('Penetapan Pengguna - ' . $applicationParams->name);
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1>Penetapan Pengguna</h1>
                <p>Tetapan capaian pengguna biodata</p>
            </div>
        </div>

        <?php if ($settings === null): ?>
            <div class="profile-empty">
                Rekod staf tidak dijumpai.
            </div>
        <?php else: ?>
            <div class="page-card-section page-section-body">
                <section class="content-inner-card">
                    <div class="profile-card-header">
                        <h2><?= Html::encode($settings['name']) ?></h2>
                    </div>
                    <dl class="profile-detail-grid">
                        <div>
                            <dt>ICNO</dt>
                            <dd><?= Html::encode($settings['icno']) ?></dd>
                        </div>
                        <div>
                            <dt>UMSPER</dt>
                            <dd><?= Html::encode($settings['staffNo']) ?></dd>
                        </div>
                        <div>
                            <dt>Access Level</dt>
                            <dd><?= Html::encode($settings['accessLevel']) ?></dd>
                        </div>
                        <div>
                            <dt>Second Level</dt>
                            <dd><?= Html::encode($settings['accessSecondLevel']) ?></dd>
                        </div>
                    </dl>
                </section>
            </div>
            <div class="profile-note page-card-section">
                Kemaskini tetapan capaian kini dibuka melalui halaman Yii3.
                <a class="button-link" href="/biodata/kemaskinipenetapanpengguna?id=<?= Html::encode($settings['icno']) ?>">
                    <i data-lucide="pencil"></i>
                    <span>Kemaskini Tetapan</span>
                </a>
            </div>
        <?php endif; ?>
    </section>
</main>
