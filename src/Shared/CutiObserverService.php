<?php

declare(strict_types=1);

namespace App\Shared;

use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class CutiObserverService
{
    public function __construct(
        private ConnectionInterface $db,
        private EmployeeRepository $employees,
    ) {}

    public function isObserver(string $icno): bool
    {
        return $this->exists(
            'SELECT 1 FROM hrm.cuti_access WHERE akses_cuti_icno = :icno AND akses_cuti_int IN (3, 4) LIMIT 1',
            [':icno' => $icno],
        );
    }

    public function isAdmin(string $icno): bool
    {
        return $this->exists(
            'SELECT 1 FROM hrm.cuti_access WHERE akses_cuti_icno = :icno AND akses_cuti_int = 3 LIMIT 1',
            [':icno' => $icno],
        );
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    public function departmentsFor(string $icno): array
    {
        $params = [];
        $scopeSql = '';
        if (!$this->isAdmin($icno)) {
            $scopeSql = ' AND ' . $this->inCondition('d.id', $this->scopeValues($icno, 'akses_jspiu_id'), 'dept', $params);
        }

        $rows = $this->db->createCommand(
            <<<SQL
            SELECT d.id, COALESCE(NULLIF(d.shortname, ''), d.fullname, CAST(d.id AS CHAR)) AS name
            FROM hronline.department d
            WHERE d.isActive = 1{$scopeSql}
            ORDER BY d.shortname, d.fullname
            SQL,
            $params,
        )->queryAll();

        return $this->options($rows, 'id', 'name');
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    public function campusesFor(string $icno): array
    {
        $params = [];
        $scopeSql = '';
        if (!$this->isAdmin($icno)) {
            $scopeSql = ' AND ' . $this->inCondition('c.campus_id', $this->scopeValues($icno, 'akses_kampus_id'), 'campus', $params);
        }

        $rows = $this->db->createCommand(
            <<<SQL
            SELECT c.campus_id AS id, COALESCE(NULLIF(c.campus_name, ''), CAST(c.campus_id AS CHAR)) AS name
            FROM hronline.campus c
            WHERE 1 = 1{$scopeSql}
            ORDER BY c.campus_name
            SQL,
            $params,
        )->queryAll();

        return $this->options($rows, 'id', 'name');
    }

    /**
     * @param array<string, mixed> $query
     * @return list<array<string, mixed>>
     */
    public function observedStaff(string $icno, array $query): array
    {
        $params = [':year' => (int) date('Y')];
        $where = $this->staffScopeWhere($icno, $params);
        $where[] = 'bio.Status != 6';

        if ($this->stringParam($query, 'q') !== '') {
            $params[':q0'] = '%' . $this->stringParam($query, 'q') . '%';
            $params[':q1'] = $params[':q0'];
            $params[':q2'] = $params[':q0'];
            $where[] = '(bio.CONm LIKE :q0 OR bio.COOldID LIKE :q1 OR bio.ICNO LIKE :q2)';
        }
        if ($this->intParam($query, 'department') !== null) {
            $params[':department'] = $this->intParam($query, 'department');
            $where[] = 'bio.DeptId = :department';
        }
        if ($this->intParam($query, 'campus') !== null) {
            $params[':campus'] = $this->intParam($query, 'campus');
            $where[] = 'bio.campus_id = :campus';
        }

        $rows = $this->db->createCommand(
            <<<SQL
            SELECT bio.ICNO, bio.CONm AS staff_name, bio.COOldID AS staff_no,
                   dept.shortname AS department_short, dept.fullname AS department_name,
                   campus.campus_name,
                   grade.gred AS grade_label,
                   ent.layak_mula, ent.layak_tamat,
                   COALESCE(ent.layak_cuti, 0) + COALESCE(ent.layak_bawa_lepas, 0) AS entitlement_total,
                   COALESCE(ent.layak_cuti, 0) + COALESCE(ent.layak_bawa_lepas, 0) + COALESCE(ent.layak_selaras, 0)
                     - COALESCE(ent.layak_gcr, 0)
                     - COALESCE((
                        SELECT SUM(r.tempoh)
                        FROM hrm.cuti_tbl_records r
                        WHERE r.icno = bio.ICNO
                          AND r.status = 'APPROVED'
                          AND ent.layak_mula IS NOT NULL
                          AND r.start_date BETWEEN ent.layak_mula AND ent.layak_tamat
                     ), 0) AS leave_balance,
                   CASE
                     WHEN gcr.id IS NOT NULL THEN 'Telah Memohon'
                     WHEN bio.statLantikan != 1 THEN 'Lantikan Kontrak'
                     ELSE 'Belum Memohon'
                   END AS gcr_status,
                   COALESCE(gcr.status, '') AS gcr_application_status
            FROM hronline.tblprcobiodata bio
            LEFT JOIN hronline.department dept ON dept.id = bio.DeptId
            LEFT JOIN hronline.campus campus ON campus.campus_id = bio.campus_id
            LEFT JOIN hronline.gredjawatan grade ON grade.id = bio.gredJawatan
            LEFT JOIN hrm.cuti_entitlement ent ON ent.layak_id = (
                SELECT e.layak_id
                FROM hrm.cuti_entitlement e
                WHERE e.layak_icno = bio.ICNO
                  AND YEAR(e.layak_mula) = :year
                  AND YEAR(e.layak_tamat) = :year
                ORDER BY e.layak_mula DESC, e.layak_id DESC
                LIMIT 1
            )
            LEFT JOIN hrm.cuti_gcr_application gcr ON gcr.id = (
                SELECT g.id
                FROM hrm.cuti_gcr_application g
                WHERE g.pemohon_icno = bio.ICNO
                  AND g.isActive = 1
                ORDER BY g.id DESC
                LIMIT 1
            )
            WHERE {$this->sqlAnd($where)}
            ORDER BY dept.shortname, bio.CONm
            LIMIT 300
            SQL,
            $params,
        )->queryAll();

        return $this->normalizeRows($rows);
    }

    /**
     * @param array<string, mixed> $params
     * @return list<string>
     */
    private function staffScopeWhere(string $icno, array &$params): array
    {
        if ($this->isAdmin($icno)) {
            return ['1 = 1'];
        }

        return [
            $this->inCondition('bio.DeptId', $this->scopeValues($icno, 'akses_jspiu_id'), 'scope_dept', $params),
            $this->inCondition('bio.campus_id', $this->scopeValues($icno, 'akses_kampus_id'), 'scope_campus', $params),
        ];
    }

    /**
     * @return list<int>
     */
    private function scopeValues(string $icno, string $column): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT {$column}
            FROM hrm.cuti_access
            WHERE akses_cuti_icno = :icno
              AND akses_cuti_int IN (3, 4)
            SQL,
            [':icno' => $icno],
        )->queryAll();

        $values = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $value = (int) ($row[$column] ?? 0);
            if ($value > 0) {
                $values[$value] = $value;
            }
        }

        return array_values($values);
    }

    /**
     * @param list<int> $values
     * @param array<string, mixed> $params
     */
    private function inCondition(string $column, array $values, string $prefix, array &$params): string
    {
        if ($values === []) {
            return '1 = 0';
        }

        $holders = [];
        foreach (array_values($values) as $index => $value) {
            $holder = ':' . $prefix . $index;
            $holders[] = $holder;
            $params[$holder] = $value;
        }

        return $column . ' IN (' . implode(', ', $holders) . ')';
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
            $row['campus_label'] = $this->value($row['campus_name'] ?? null);
            $row['grade_label'] = $this->value($row['grade_label'] ?? null);
            $row['entitlement_total'] = $this->value($row['entitlement_total'] ?? null, '0');
            $row['leave_balance'] = $this->value($row['leave_balance'] ?? null, '0');
            $row['gcr_status_label'] = trim($this->value($row['gcr_status'] ?? null, '-') . ' (' . $this->value($row['gcr_application_status'] ?? null, '') . ')', ' ()');

            $normalized[] = $row;
        }

        return $normalized;
    }

    /**
     * @param iterable<mixed> $rows
     * @return list<array{id: string, name: string}>
     */
    private function options(iterable $rows, string $idKey, string $nameKey): array
    {
        $options = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = $this->value($row[$idKey] ?? null, '');
            if ($id === '') {
                continue;
            }
            $options[] = ['id' => $id, 'name' => $this->value($row[$nameKey] ?? null, $id)];
        }

        return $options;
    }

    /**
     * @param array<string, mixed> $query
     */
    private function intParam(array $query, string $key): ?int
    {
        if (!isset($query[$key]) || $query[$key] === '') {
            return null;
        }

        return is_numeric($query[$key]) ? (int) $query[$key] : null;
    }

    /**
     * @param array<string, mixed> $query
     */
    private function stringParam(array $query, string $key): string
    {
        return isset($query[$key]) && is_scalar($query[$key]) ? trim((string) $query[$key]) : '';
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
