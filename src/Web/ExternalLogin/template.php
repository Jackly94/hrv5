<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use App\Shared\ExternalLoginResult;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;
use Yiisoft\Yii\View\Renderer\Csrf;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var Csrf $csrf
 * @var array{username: string} $values
 * @var ExternalLoginResult|null $result
 */

$this->setTitle('External Login - ' . $applicationParams->name);
$errors = $result?->errors ?? [];
?>

<main class="login-page">
    <section class="login-panel" aria-labelledby="external-login-title">
        <div class="login-brand">
            <div class="login-mark">HR</div>
            <div>
                <h1 id="external-login-title">External Login</h1>
                <p>HROnline Yii3 migration pilot</p>
            </div>
        </div>

        <?php if ($result?->success): ?>
            <div class="alert alert-success">
                <strong>Login verified.</strong>
                Yii2-compatible session created for <?= Html::encode((string) $result->identityId) ?>.
            </div>
            <dl class="result-list">
                <dt>Name</dt>
                <dd><?= Html::encode((string) $result->displayName) ?></dd>
                <dt>Return URL</dt>
                <dd>
                    <?php if ($result->returnUrl === null): ?>
                        Not configured
                    <?php else: ?>
                        <a href="<?= Html::encode($result->returnUrl) ?>"><?= Html::encode($result->returnUrl) ?></a>
                    <?php endif; ?>
                </dd>
            </dl>
        <?php else: ?>
            <?php if (isset($errors['general'])): ?>
                <div class="alert alert-danger"><?= Html::encode($errors['general']) ?></div>
            <?php endif; ?>

            <form method="post" action="/external-login" class="login-form">
                <?= $csrf->hiddenInput() ?>

                <label>
                    <span>Username</span>
                    <input
                        name="username"
                        value="<?= Html::encode($values['username']) ?>"
                        autocomplete="username"
                        required
                    >
                </label>
                <?php if (isset($errors['username'])): ?>
                    <p class="field-error"><?= Html::encode($errors['username']) ?></p>
                <?php endif; ?>

                <label>
                    <span>Password</span>
                    <input
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        required
                    >
                </label>
                <?php if (isset($errors['password'])): ?>
                    <p class="field-error"><?= Html::encode($errors['password']) ?></p>
                <?php endif; ?>

                <button type="submit">Login</button>
            </form>
        <?php endif; ?>
    </section>
</main>
