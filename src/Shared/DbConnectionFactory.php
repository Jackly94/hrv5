<?php

declare(strict_types=1);

namespace App\Shared;

use RuntimeException;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mysql\Connection;
use Yiisoft\Db\Mysql\Driver;

final class DbConnectionFactory
{
    public static function create(string $yii2DbConfigFile): ConnectionInterface
    {
        if (!is_file($yii2DbConfigFile)) {
            throw new RuntimeException('Yii2 DB config file was not found.');
        }

        $config = require $yii2DbConfigFile;

        return self::fromConfig($config);
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromConfig(array $config): ConnectionInterface
    {
        foreach (['dsn', 'username', 'password'] as $key) {
            if (!isset($config[$key]) || !is_string($config[$key])) {
                throw new RuntimeException("DB config is missing {$key}.");
            }
        }

        if (!str_starts_with($config['dsn'], 'mysql:')) {
            $driver = strtok($config['dsn'], ':') ?: 'unknown';
            throw new RuntimeException(
                "DB driver {$driver} is configured but this Yii3 app currently supports mysql only.",
            );
        }

        $connection = new Connection(
            new Driver($config['dsn'], $config['username'], $config['password']),
            new SchemaCache(),
        );

        $connection->createCommand(
            "SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))",
        )->execute();

        return $connection;
    }
}
