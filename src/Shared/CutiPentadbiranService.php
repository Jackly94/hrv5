<?php

declare(strict_types=1);

namespace App\Shared;

use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class CutiPentadbiranService
{
    public function __construct(
        private ConnectionInterface $db,
        private EmployeeRepository $employees,
    ) {}

    public function isDepartmentChiefOrPp(string $icno): bool
    {
        return $this->exists(
            'SELECT 1 FROM hronline.department WHERE chief = :icno OR pp = :icno LIMIT 1',
            [':icno' => $icno],
        );
    }

    /**
     * @param array<string, mixed> $query
     * @return list<array<string, mixed>>
     */
    public function hsoApplications(string $icno, array $query): array
    {
        $deptId = $this->db->createCommand(
            'SELECT id FROM hronline.department WHERE chief = :icno OR pp = :icno ORDER BY id LIMIT 1',
            [':icno' => $icno],
        )->queryScalar();

        $year = isset($query['year']) && is_numeric($query['year']) ? (int) $query['year'] : (int) date('Y');
        $params = [':dept' => (int) $deptId, ':year' => $year];
        $where = ['r.jenis_cuti_id = 58', 'bio.DeptId = :dept', 'YEAR(r.start_date) = :year'];
        if (isset($query['status']) && is_scalar($query['status']) && trim((string) $query['status']) !== '') {
            $params[':status'] = trim((string) $query['status']);
            $where[] = 'r.status = :status';
        }

        $rows = $this->db->createCommand(
            <<<SQL
            SELECT r.id, r.icno, r.full_date, r.start_date, r.end_date, r.tempoh, r.remark, r.status, r.mohon_dt,
                   bio.CONm AS staff_name, bio.COOldID AS staff_no,
                   dept.shortname AS department_short, dept.fullname AS department_name,
                   lt.jenis_cuti_catatan, lt.jenis_cuti_nama
            FROM hrm.cuti_tbl_records r
            LEFT JOIN hronline.tblprcobiodata bio ON bio.ICNO = r.icno
            LEFT JOIN hronline.department dept ON dept.id = bio.DeptId
            LEFT JOIN hrm.cuti_leave_type lt ON lt.jenis_cuti_id = r.jenis_cuti_id
            WHERE {$this->sqlAnd($where)}
            ORDER BY r.start_date ASC, r.id ASC
            LIMIT 300
            SQL,
            $params,
        )->queryAll();

        return $this->normalizeRows($rows);
    }

    /**
     * @param list<string> $where
     */
    private function sqlAnd(array $where): string
    {
        return implode(' AND ', array_filter($where, static fn (string $part): bool => trim($part) !== ''));
    }

    /**
     * @param iterable<mixed> $rows
     * @return list<array<string, mixed>>
     */
    private function normalizeRows(iterable $rows): array
    {
        $normalized = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $row['staff_name'] = $this->employees->displayName($row['staff_name'] ?? null);
            $row['department_label'] = $this->value($row['department_short'] ?? $row['department_name'] ?? null);
            $row['jenis_cuti_label'] = $this->value($row['jenis_cuti_catatan'] ?? $row['jenis_cuti_nama'] ?? null);
            $row['date_label'] = $this->value($row['full_date'] ?? null, $this->dateRange($row));
            $row['status_label'] = $this->value($row['status'] ?? null);
            $normalized[] = $row;
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function dateRange(array $row): string
    {
        $start = $this->value($row['start_date'] ?? null, '');
        $end = $this->value($row['end_date'] ?? null, '');

        return $start === '' ? '-' : ($end === '' || $end === $start ? $start : $start . ' - ' . $end);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function exists(string $sql, array $params): bool
    {
        return (bool) $this->db->createCommand($sql, $params)->queryScalar();
    }

    private function value(mixed $value, string $fallback = '-'): string
    {
        if ($value === null || !is_scalar($value)) {
            return $fallback;
        }
        $value = trim((string) $value);

        return $value === '' ? $fallback : $value;
    }
}
