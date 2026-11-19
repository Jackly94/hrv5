<?php

declare(strict_types=1);

namespace App\Shared;

use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class BiodataTouchService
{
    public function __construct(
        private ConnectionInterface $db,
    ) {}

    public function touch(string $icno, ?string $updatedBy = null, ?string $now = null): void
    {
        $now ??= date('Y-m-d H:i:s');

        $this->db->createCommand()->update('hronline.tblprcobiodata', [
            'last_update' => $now,
            'kemaskini_terakhir' => $now,
            'last_updater' => $updatedBy ?? $icno,
        ], ['ICNO' => $icno])->execute();
    }
}
