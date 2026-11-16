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

        foreach (['dsn', 'username', 'password'] as $key) {
            if (!isset($config[$key]) || !is_string($config[$key])) {
                throw new RuntimeException("Yii2 DB config is missing {$key}.");
            }
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
