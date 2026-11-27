<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var array<string, mixed>|null $profile
 * @var string $identityId
 */

$this->setTitle('Profil Staf - ' . $applicationParams->name);
?>

<?php if ($profile === null): ?>
    <main class="profile-page">
        <section class="profile-card page-main-card">
            <div class="profile-heading page-card-heading">
            <h1>Profil staf unavailable</h1>
            <p>The current legacy session <?= Html::encode($identityId) ?> is not a staff biodata session.</p>
            </div>
            <div class="page-section-body">
                <a class="button-link" href="/login">
                    <i data-lucide="log-in"></i>
                    <span>Back to login</span>
                </a>
            </div>
        </section>
    </main>
<?php else: ?>
    <?php
    /** @var list<array{label: string, value: string}> $fields */
    $fields = $profile['fields'];
    /** @var list<array{label: string, url: string, external: bool}> $sections */
    $sections = $profile['sections'];
    $adminMode = ($profile['mode'] ?? 'user') === 'admin';
    ?>

    <main class="profile-page">
        <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1>Profil Staf</h1>
                <p>Maklumat dan rekod kakitangan</p>
            </div>
            <?php if ($adminMode): ?>
                <a class="button-link button-link-secondary" href="/biodata/index">
                    <i data-lucide="arrow-left"></i>
                    <span>Kembali</span>
                </a>
            <?php else: ?>
                <a class="button-link button-link-secondary" href="/gambar-profile/semakan">
                    <i data-lucide="image-up"></i>
                    <span>Kemaskini Gambar</span>
                </a>
            <?php endif; ?>
        </div>

        <div class="profile-hero page-card-section">
            <img class="profile-photo" src="<?= Html::encode($profile['profileImage']) ?>" alt="Profile image">
            <div class="profile-identity">
                <h2><?= Html::encode($profile['displayName']) ?></h2>
                <p><?= Html::encode($profile['icno']) ?> · <?= Html::encode($profile['staffNo']) ?></p>
                <p><?= Html::encode($profile['email']) ?></p>
            </div>
        </div>

        <div class="page-card-section page-section-body">
            <section class="content-inner-card">
                <div class="profile-card-header">
                    <h2>Ringkasan Perkhidmatan</h2>
                </div>
            <dl class="profile-detail-grid">
                <?php foreach ($fields as $field): ?>
                    <div>
                        <dt><?= Html::encode($field['label']) ?></dt>
                        <dd><?= Html::encode($field['value']) ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
            </section>
        </div>

        <div class="page-card-section">
            <div class="profile-card-header">
                <h2>Maklumat Dan Rekod Kakitangan</h2>
            </div>
            <nav class="profile-section-grid" aria-label="Biodata sections">
                <?php foreach ($sections as $section): ?>
                    <a href="<?= Html::encode($section['url']) ?>"<?= $section['external'] ? ' target="_blank" rel="noopener"' : '' ?>>
                        <i data-lucide="<?= $section['external'] ? 'external-link' : 'arrow-right' ?>"></i>
                        <span><?= Html::encode($section['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
        </section>
    </main>
<?php endif; ?>
