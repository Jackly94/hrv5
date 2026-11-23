<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Definitions\Reference;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\View\Renderer\CsrfViewInjection;

$mediahost = [
    'baseUrl' => 'https://mediahost.ums.edu.my',
    'uploaderId' => '0302',
    'connectTimeout' => 5,
    'timeout' => 60,
];
$mediahostLocal = __DIR__ . '/mediahost.local.php';
if (is_file($mediahostLocal)) {
    $localMediahost = require $mediahostLocal;
    if (is_array($localMediahost)) {
        $mediahost = array_replace_recursive($mediahost, $localMediahost);
    }
}

return [
    'application' => require __DIR__ . '/application.php',
    'mediahost' => $mediahost,

    'yiisoft/aliases' => [
        'aliases' => require __DIR__ . '/aliases.php',
    ],

    'yiisoft/view' => [
        'basePath' => null,
        'parameters' => [
            'assetManager' => Reference::to(AssetManager::class),
            'applicationParams' => Reference::to(ApplicationParams::class),
            'aliases' => Reference::to(Aliases::class),
            'urlGenerator' => Reference::to(UrlGeneratorInterface::class),
            'currentRoute' => Reference::to(CurrentRoute::class),
        ],
    ],

    'yiisoft/yii-view-renderer' => [
        'viewPath' => null,
        'layout' => '@src/Web/Shared/Layout/Main/layout.php',
        'injections' => [
            Reference::to(CsrfViewInjection::class),
        ],
    ],
];
