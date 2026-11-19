<?php

declare(strict_types=1);

namespace App\Shared;

use InvalidArgumentException;
use Yiisoft\Db\Connection\ConnectionInterface;

final class DbConnectionRegistry
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $configs;

    /**
     * @var array<string, ConnectionInterface>
     */
    private array $connections = [];

    /**
     * @param array<string, array<string, mixed>> $configs
     */
    public function __construct(array $configs)
    {
        $this->configs = $configs;
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->configs);
    }

    public function get(string $name): ConnectionInterface
    {
        if (!isset($this->configs[$name])) {
            throw new InvalidArgumentException("DB connection {$name} is not configured.");
        }

        return $this->connections[$name] ??= DbConnectionFactory::fromConfig($this->configs[$name]);
    }

    public function primary(): ConnectionInterface
    {
        return $this->get('db');
    }
}
