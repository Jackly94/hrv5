<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var string $portalUrl
 */

$this->setTitle('Login - ' . $applicationParams->name);
?>

<main class="login-page">
    <section class="login-panel login-panel-wide" aria-labelledby="login-title">
        <div class="login-brand">
            <img class="login-mark login-logo" src="/images/hronline_logo.png" alt="HROnline">
            <div>
                <h1 id="login-title">HROnline Login</h1>
                <p>Universiti Malaysia Sabah</p>
            </div>
        </div>

        <div class="login-actions">
            <a class="button-link" href="<?= Html::encode($portalUrl) ?>">
                <i data-lucide="external-link"></i>
                <span>Portal Kinabalu</span>
            </a>
            <a class="button-link button-link-secondary" href="/external-login">
                <i data-lucide="log-in"></i>
                <span>External Login</span>
            </a>
        </div>

        <div class="login-note">
            Staff access continues through Portal Kinabalu. External users may use the Yii3 external-login pilot.
        </div>
    </section>
</main>
