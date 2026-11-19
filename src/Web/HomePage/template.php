<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 */

$this->setTitle($applicationParams->name);
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1>HROnline Yii3</h1>
                <p>Login migration pilot.</p>
            </div>
        </div>

        <div class="page-section-body profile-actions">
            <a class="button-link" href="/login">
                <i data-lucide="log-in"></i>
                <span>Open login</span>
            </a>
            <a class="button-link button-link-secondary" href="/dashboard">
                <i data-lucide="layout-dashboard"></i>
                <span>Open dashboard</span>
            </a>
            <a class="button-link" href="/external-login">
                <i data-lucide="external-link"></i>
                <span>Open external login</span>
            </a>
        </div>
    </section>
</main>
