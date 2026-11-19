<?php

declare(strict_types=1);

namespace App\Web\Shared\Layout\Main;

use Yiisoft\Assets\AssetBundle;

final class MainAsset extends AssetBundle
{
    public ?string $basePath = '@assets/main';
    public ?string $baseUrl = '@assetsUrl/main';
    public ?string $sourcePath = '@assetsSource/main';

    public array $css = [
        'vendor/tom-select/tom-select.css',
        'site.css',
    ];

    public array $js = [
        'vendor/tom-select/tom-select.complete.min.js',
        'vendor/lucide/lucide.min.js',
        'site.js',
    ];
}
