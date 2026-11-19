<?php

declare(strict_types=1);

namespace App\Shared;

use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class AuditTrailService
{
    public function __construct(
        private ConnectionInterface $db,
    ) {}

    /**
     * @param array<string, mixed> $record
     */
    public function createChanges(array $record): array
    {
        $changes = [];
        foreach ($record as $field => $value) {
            $changes[] = [$field, $value];
        }

        return $changes;
    }

    /**
     * @param array<string, mixed> $old
     * @param array<string, mixed> $new
     */
    public function updateChanges(array $old, array $new): array
    {
        $changes = [];
        foreach ($new as $field => $value) {
            if ((string) ($old[$field] ?? '') !== (string) ($value ?? '')) {
                $changes[] = [$field, (string) ($old[$field] ?? ''), $value];
            }
        }

        return $changes;
    }

    /**
     * @param list<array<mixed>> $changes
     */
    public function logCreate(string $icno, string $table, string $id, array $changes, string $ipAddress, string $action, ?string $now = null): void
    {
        $this->log($icno, $table, $id, 0, $changes, $ipAddress, $action, $now);
    }

    /**
     * @param list<array<mixed>> $changes
     */
    public function logUpdate(string $icno, string $table, string $id, array $changes, string $ipAddress, string $action, ?string $now = null): void
    {
        if ($changes === []) {
            return;
        }

        $this->log($icno, $table, $id, 1, $changes, $ipAddress, $action, $now);
    }

    /**
     * @param list<array<mixed>> $changes
     */
    public function logDelete(string $icno, string $table, string $id, array $changes, string $ipAddress, string $action, ?string $now = null): void
    {
        $this->log($icno, $table, $id, 2, $changes, $ipAddress, $action, $now);
    }

    /**
     * @param list<array<mixed>> $changes
     */
    private function log(string $icno, string $table, string $id, int $activity, array $changes, string $ipAddress, string $action, ?string $now): void
    {
        $now ??= date('Y-m-d H:i:s');

        $this->db->createCommand()->insert('hronline.updatestatus', [
            'usern' => $icno,
            'COTableName' => $table,
            'COActivity' => $activity,
            'COUpadteDate' => $now,
            'COUpdateIP' => $ipAddress,
            'COUpdateComp' => $ipAddress,
            'COUpdateCompUser' => $icno,
            'COUpdateSQL' => serialize($changes),
            'idval' => $id,
            'con_action' => $action,
        ])->execute();
    }
}
