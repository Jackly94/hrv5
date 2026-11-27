<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use App\Shared\DbConnectionRegistry;
use App\Shared\GambarProfile\BkumsProfilePhotoService;
use App\Shared\MediaHostFileService;
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var array $params */

return [
    ApplicationParams::class => [
        '__construct()' => [
            'name' => $params['application']['name'],
            'charset' => $params['application']['charset'],
            'locale' => $params['application']['locale'],
        ],
    ],
    DbConnectionRegistry::class => static fn () => new DbConnectionRegistry(
        require dirname(__DIR__) . '/db-connections.php',
    ),
    MediaHostFileService::class => [
        '__construct()' => [
            'config' => $params['mediahost'] ?? [],
        ],
    ],
    BkumsProfilePhotoService::class => [
        '__construct()' => [
            'config' => $params['bkums']['profilePhoto'] ?? [],
        ],
    ],
    ConnectionInterface::class => static fn (DbConnectionRegistry $registry) => $registry->primary(),
];
