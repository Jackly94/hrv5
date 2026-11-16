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

<div class="text-center">
    <h1>HROnline Yii3</h1>

    <p>External-login migration pilot.</p>

    <p>
        <a class="button-link" href="/external-login">Open external login</a>
    </p>
</div>
