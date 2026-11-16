<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use App\Shared\DbConnectionFactory;
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
    ConnectionInterface::class => static fn () => DbConnectionFactory::create(
        dirname(__DIR__, 4) . '/staff/config/db.php',
    ),
];
