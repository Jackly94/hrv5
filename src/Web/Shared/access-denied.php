<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var array{title: string, message: string, backUrl: string} $page
 */

$this->setTitle($page['title'] . ' - ' . $applicationParams->name);
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($page['title']) ?></h1>
                <p>HROnline</p>
            </div>
            <div class="profile-actions">
                <a class="button-link button-link-secondary" href="<?= Html::encode($page['backUrl']) ?>">
                    <i data-lucide="arrow-left"></i>
                    <span>Kembali</span>
                </a>
            </div>
        </div>

        <div class="page-card-section page-section-body">
            <section class="content-inner-card access-denied-card">
                <img class="access-denied-image" src="/images/access-denied-soft.svg" alt="Akses tidak dibenarkan">
                <div class="access-denied-copy">
                    <span>403</span>
                    <h2><?= Html::encode($page['message']) ?></h2>
                </div>
            </section>
        </div>
    </section>
</main>
