<?php

declare(strict_types=1);

namespace App\Shared;

use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class CutiSectorHrService
{
    public function __construct(
        private ConnectionInterface $db,
        private EmployeeRepository $employees,
    ) {}

    public function isSectorHrUser(string $icno): bool
    {
        return $this->exists(
            'SELECT 1 FROM system_core.tbl_user_access WHERE icno = :icno LIMIT 1',
            [':icno' => $icno],
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function managementRows(): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT m.*, bio.CONm AS staff_name, bio.COOldID AS staff_no
            FROM hrm.cuti_tbl_management m
            LEFT JOIN hronline.tblprcobiodata bio ON bio.ICNO = m.icno
            ORDER BY m.user, m.level, m.isActive DESC, m.id
            LIMIT 300
            SQL,
        )->queryAll();

        $normalized = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $row['staff_name'] = $this->employees->displayName($row['staff_name'] ?? null);
            $row['level_label'] = match ((int) ($row['level'] ?? -1)) {
                0 => 'Penyelia BSM',
                1 => 'Pegawai Cuti',
                default => (string) ($row['level'] ?? '-'),
            };
            $row['status_label'] = (int) ($row['isActive'] ?? 0) === 1 ? 'Aktif' : 'Tidak Aktif';
            $normalized[] = $row;
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $params
     */
    private function exists(string $sql, array $params): bool
    {
        return (bool) $this->db->createCommand($sql, $params)->queryScalar();
    }
}
