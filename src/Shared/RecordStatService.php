<?php

declare(strict_types=1);

namespace App\Shared;

use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class RecordStatService
{
    public function __construct(
        private ConnectionInterface $db,
    ) {}

    public function insertPending(string $icno, string $table, string $id, ?string $now = null): void
    {
        $this->insert($icno, $table, $id, 0, $now ?? date('Y-m-d H:i:s'));
    }

    public function upsertUpdated(string $icno, string $table, string $id, ?string $now = null): void
    {
        $now ??= date('Y-m-d H:i:s');
        $exists = (bool) $this->db->createCommand(
            'SELECT 1 FROM hronline.tbl_stat WHERE ICNO = :icno AND `table` = :table AND idval = :id LIMIT 1',
            [':icno' => $icno, ':table' => $table, ':id' => $id],
        )->queryScalar();

        if ($exists) {
            $this->db->createCommand()->update('hronline.tbl_stat', [
                'status' => 1,
                'date_submit' => $now,
            ], [
                'ICNO' => $icno,
                'table' => $table,
                'idval' => $id,
            ])->execute();
            return;
        }

        $this->insert($icno, $table, $id, 1, $now);
    }

    public function deleteForRecord(string $icno, string $table, string $id): void
    {
        $this->db->createCommand()->delete('hronline.tbl_stat', [
            'ICNO' => $icno,
            'table' => $table,
            'idval' => $id,
        ])->execute();
    }

    private function insert(string $icno, string $table, string $id, int $status, string $now): void
    {
        $this->db->createCommand()->insert('hronline.tbl_stat', [
            'ICNO' => $icno,
            'table' => $table,
            'idval' => $id,
            'status' => $status,
            'date_submit' => $now,
        ])->execute();
    }
}
