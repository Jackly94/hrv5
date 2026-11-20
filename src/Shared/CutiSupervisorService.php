<?php

declare(strict_types=1);

namespace App\Shared;

use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class CutiSupervisorService
{
    public function __construct(
        private ConnectionInterface $db,
        private EmployeeRepository $employees,
    ) {}

    public function isSupervisor(string $icno): bool
    {
        return $this->exists(
            'SELECT 1 FROM hrm.cuti_access WHERE akses_cuti_icno = :icno AND akses_cuti_int != 4 LIMIT 1',
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
     * @return list<array<string, mixed>>
     */
    public function accessRows(string $icno): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT akses_cuti_id, akses_cuti_int, akses_jspiu_id, akses_kampus_id
            FROM hrm.cuti_access
            WHERE akses_cuti_icno = :icno
              AND akses_cuti_int != 4
            ORDER BY akses_cuti_int, akses_jspiu_id, akses_kampus_id
            SQL,
            [':icno' => $icno],
        )->queryAll();

        return $this->normalizeRows($rows);
    }

    /**
     * @return list<int>
     */
    public function departmentScope(string $icno): array
    {
        return $this->scopeValues($icno, 'akses_jspiu_id');
    }

    /**
     * @return list<int>
     */
    public function campusScope(string $icno): array
    {
        return $this->scopeValues($icno, 'akses_kampus_id');
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    public function departmentsFor(string $icno): array
    {
        $params = [];
        $scopeSql = '';
        if (!$this->isAdmin($icno)) {
            $scopeSql = ' AND ' . $this->inCondition('d.id', $this->departmentScope($icno), 'dept', $params);
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
            $scopeSql = ' AND ' . $this->inCondition('c.campus_id', $this->campusScope($icno), 'campus', $params);
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
     * @return list<array{id: string, name: string}>
     */
    public function leaveTypes(): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT jenis_cuti_id AS id, COALESCE(NULLIF(jenis_cuti_catatan, ''), jenis_cuti_nama, CAST(jenis_cuti_id AS CHAR)) AS name
            FROM hrm.cuti_leave_type
            ORDER BY jenis_cuti_catatan, jenis_cuti_nama
            SQL,
        )->queryAll();

        return $this->options($rows, 'id', 'name');
    }

    /**
     * @param array<string, mixed> $query
     * @return list<array<string, mixed>>
     */
    public function supervisedStaff(string $icno, array $query): array
    {
        $params = [];
        $where = $this->staffScopeWhere($icno, $params);

        if ($this->stringParam($query, 'q') !== '') {
            $params[':q'] = '%' . $this->stringParam($query, 'q') . '%';
            $where[] = '(bio.CONm LIKE :q OR bio.COOldID LIKE :q OR bio.ICNO LIKE :q)';
        }
        if ($this->intParam($query, 'department') !== null) {
            $params[':department'] = $this->intParam($query, 'department');
            $where[] = 'bio.DeptId = :department';
        }
        if ($this->intParam($query, 'campus') !== null) {
            $params[':campus'] = $this->intParam($query, 'campus');
            $where[] = 'bio.campus_id = :campus';
        }
        if ($this->intParam($query, 'status') !== null) {
            $params[':status'] = $this->intParam($query, 'status');
            $where[] = 'bio.Status = :status';
        } else {
            $where[] = 'bio.Status != 6';
        }

        $rows = $this->db->createCommand(
            $this->staffSelect() . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY bio.CONm LIMIT 300',
            $params,
        )->queryAll();

        return $this->normalizeRows($rows);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function sickLeaveList(string $icno): array
    {
        $verification = $this->leaveRows(
            "r.semakan_by = :icno AND r.status = 'ENTRY' AND r.jenis_cuti_id IN (20, 21, 58)",
            [':icno' => $icno],
            'Semakan',
        );
        $approval = $this->leaveRows(
            "r.lulus_by = :icno AND r.status = 'VERIFIED'",
            [':icno' => $icno],
            'Kelulusan',
        );

        return [...$verification, ...$approval];
    }

    /**
     * @param array<string, mixed> $query
     * @return list<array<string, mixed>>
     */
    public function monthlyStatement(string $icno, array $query): array
    {
        $params = [
            ':year' => $this->yearParam($query),
        ];
        $where = $this->staffScopeWhere($icno, $params);
        $where[] = 'YEAR(r.start_date) = :year';

        if ($this->intParam($query, 'month') !== null) {
            $params[':month'] = $this->intParam($query, 'month');
            $where[] = 'MONTH(r.start_date) = :month';
        }
        if ($this->intParam($query, 'department') !== null) {
            $params[':department'] = $this->intParam($query, 'department');
            $where[] = 'bio.DeptId = :department';
        }
        if ($this->intParam($query, 'jenis_cuti_id') !== null) {
            $params[':jenis_cuti_id'] = $this->intParam($query, 'jenis_cuti_id');
            $where[] = 'r.jenis_cuti_id = :jenis_cuti_id';
        }

        $rows = $this->db->createCommand(
            <<<SQL
            SELECT bio.ICNO, bio.CONm AS staff_name, bio.COOldID AS staff_no,
                   dept.shortname AS department_short, dept.fullname AS department_name,
                   COUNT(r.id) AS total_records, COALESCE(SUM(r.tempoh), 0) AS total_days,
                   MIN(r.start_date) AS first_leave, MAX(r.end_date) AS last_leave
            FROM hronline.tblprcobiodata bio
            JOIN hrm.cuti_tbl_records r ON r.icno = bio.ICNO
            LEFT JOIN hronline.department dept ON dept.id = bio.DeptId
            WHERE {$this->sqlAnd($where)}
            GROUP BY bio.ICNO, bio.CONm, bio.COOldID, dept.shortname, dept.fullname
            ORDER BY dept.shortname, bio.CONm
            LIMIT 300
            SQL,
            $params,
        )->queryAll();

        return $this->normalizeRows($rows);
    }

    /**
     * @param array<string, mixed> $query
     * @return list<array<string, mixed>>
     */
    public function leaveMonitoring(string $icno, array $query): array
    {
        $params = [];
        $where = $this->staffScopeWhere($icno, $params);

        if ($this->intParam($query, 'department') !== null) {
            $params[':department'] = $this->intParam($query, 'department');
            $where[] = 'bio.DeptId = :department';
        }
        if ($this->intParam($query, 'jenis_cuti_id') !== null) {
            $params[':jenis_cuti_id'] = $this->intParam($query, 'jenis_cuti_id');
            $where[] = 'r.jenis_cuti_id = :jenis_cuti_id';
        }
        if ($this->stringParam($query, 'status') !== '') {
            $params[':status'] = $this->stringParam($query, 'status');
            $where[] = 'r.status = :status';
        } else {
            $where[] = "r.status NOT IN ('APPROVED', 'REJECTED')";
        }
        if ($this->yearParam($query) > 0) {
            $params[':year'] = $this->yearParam($query);
            $where[] = 'YEAR(r.start_date) = :year';
        }
        if ($this->stringParam($query, 'q') !== '') {
            $params[':q'] = '%' . $this->stringParam($query, 'q') . '%';
            $where[] = '(bio.CONm LIKE :q OR bio.COOldID LIKE :q OR bio.ICNO LIKE :q)';
        }

        return $this->leaveRows($this->sqlAnd($where), $params, 'Pantau', false);
    }

    /**
     * @param array<string, mixed> $query
     * @return list<array<string, mixed>>
     */
    public function leaveCalendar(string $icno, array $query): array
    {
        $params = [
            ':year' => $this->yearParam($query),
            ':month' => $this->monthParam($query),
        ];
        $where = $this->staffScopeWhere($icno, $params);
        $where[] = "r.status = 'APPROVED'";
        $where[] = 'r.jenis_cuti_id IN (1, 2)';
        $where[] = 'YEAR(r.start_date) = :year';
        $where[] = 'MONTH(r.start_date) = :month';

        if ($this->intParam($query, 'department') !== null) {
            $params[':department'] = $this->intParam($query, 'department');
            $where[] = 'bio.DeptId = :department';
        }

        return $this->leaveRows($this->sqlAnd($where), $params, 'Kalendar', false);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function gcrList(string $icno): array
    {
        return $this->gcrRows(
            "g.semakan_by = :icno AND g.status = 'ENTRY' AND g.isActive = 1",
            [':icno' => $icno],
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function gcrResetList(string $icno): array
    {
        return $this->gcrRows(
            'g.semakan_by = :icno AND g.isActive = 1',
            [':icno' => $icno],
        );
    }

    /**
     * @param array<string, mixed> $query
     */
    public function yearParam(array $query): int
    {
        $year = $this->intParam($query, 'year');

        return $year !== null && $year >= 2000 && $year <= ((int) date('Y') + 1) ? $year : (int) date('Y');
    }

    /**
     * @param array<string, mixed> $query
     */
    public function monthParam(array $query): int
    {
        $month = $this->intParam($query, 'month');

        return $month !== null && $month >= 1 && $month <= 12 ? $month : (int) date('n');
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
            $this->inCondition('bio.DeptId', $this->departmentScope($icno), 'scope_dept', $params),
            $this->inCondition('bio.campus_id', $this->campusScope($icno), 'scope_campus', $params),
        ];
    }

    private function staffSelect(): string
    {
        return <<<SQL
        SELECT bio.ICNO, bio.CONm AS staff_name, bio.COOldID AS staff_no, bio.DeptId, bio.campus_id,
               bio.Status, bio.statLantikan,
               dept.shortname AS department_short, dept.fullname AS department_name,
               campus.campus_name
        FROM hronline.tblprcobiodata bio
        LEFT JOIN hronline.department dept ON dept.id = bio.DeptId
        LEFT JOIN hronline.campus campus ON campus.campus_id = bio.campus_id
        SQL;
    }

    /**
     * @param array<string, mixed> $params
     * @return list<array<string, mixed>>
     */
    private function leaveRows(string $where, array $params, string $queue, bool $appendWhere = true): array
    {
        $whereSql = $appendWhere ? $where : $where;
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT r.id, r.icno, r.jenis_cuti_id, r.full_date, r.start_date, r.end_date,
                   r.tempoh, r.remark, r.status, r.mohon_dt, r.ganti_by, r.semakan_by,
                   r.peraku_by, r.lulus_by,
                   :queue AS queue_label,
                   bio.CONm AS staff_name, bio.COOldID AS staff_no,
                   dept.shortname AS department_short, dept.fullname AS department_name,
                   lt.jenis_cuti_catatan, lt.jenis_cuti_nama,
                   ganti.CONm AS ganti_name,
                   semak.CONm AS semakan_name,
                   peraku.CONm AS peraku_name,
                   lulus.CONm AS lulus_name
            FROM hrm.cuti_tbl_records r
            LEFT JOIN hronline.tblprcobiodata bio ON bio.ICNO = r.icno
            LEFT JOIN hronline.department dept ON dept.id = bio.DeptId
            LEFT JOIN hrm.cuti_leave_type lt ON lt.jenis_cuti_id = r.jenis_cuti_id
            LEFT JOIN hronline.tblprcobiodata ganti ON ganti.ICNO = r.ganti_by
            LEFT JOIN hronline.tblprcobiodata semak ON semak.ICNO = r.semakan_by
            LEFT JOIN hronline.tblprcobiodata peraku ON peraku.ICNO = r.peraku_by
            LEFT JOIN hronline.tblprcobiodata lulus ON lulus.ICNO = r.lulus_by
            WHERE {$whereSql}
            ORDER BY r.start_date DESC, r.id DESC
            LIMIT 300
            SQL,
            [...$params, ':queue' => $queue],
        )->queryAll();

        return $this->normalizeRows($rows);
    }

    /**
     * @param array<string, mixed> $params
     * @return list<array<string, mixed>>
     */
    private function gcrRows(string $where, array $params): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT g.*, bio.CONm AS staff_name, bio.COOldID AS staff_no,
                   dept.shortname AS department_short, dept.fullname AS department_name,
                   semak.CONm AS semakan_name,
                   peraku.CONm AS peraku_name,
                   lulus.CONm AS lulus_name
            FROM hrm.cuti_gcr_application g
            LEFT JOIN hronline.tblprcobiodata bio ON bio.ICNO = g.pemohon_icno
            LEFT JOIN hronline.department dept ON dept.id = g.dept_id
            LEFT JOIN hronline.tblprcobiodata semak ON semak.ICNO = g.semakan_by
            LEFT JOIN hronline.tblprcobiodata peraku ON peraku.ICNO = g.peraku_by
            LEFT JOIN hronline.tblprcobiodata lulus ON lulus.ICNO = g.lulus_by
            WHERE {$where}
            ORDER BY g.mohon_dt ASC, g.id ASC
            LIMIT 300
            SQL,
            $params,
        )->queryAll();

        return $this->normalizeRows($rows);
    }

    /**
     * @return list<int>
     */
    private function scopeValues(string $icno, string $column): array
    {
        $rows = $this->accessRows($icno);
        $values = [];
        foreach ($rows as $row) {
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

            foreach (['staff_name', 'semakan_name', 'peraku_name', 'pelulus_name', 'lulus_name', 'ganti_name'] as $nameKey) {
                if (array_key_exists($nameKey, $row)) {
                    $row[$nameKey] = $this->employees->displayName($row[$nameKey]);
                }
            }
            $row['department_label'] = $this->value($row['department_short'] ?? $row['department_name'] ?? null);
            $row['campus_label'] = $this->value($row['campus_name'] ?? null);
            $row['jenis_cuti_label'] = $this->value($row['jenis_cuti_catatan'] ?? $row['jenis_cuti_nama'] ?? null);
            $row['date_label'] = $this->value($row['full_date'] ?? null, $this->dateRange($row));
            $row['status_label'] = $this->value($row['status'] ?? null, '-');

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
     * @param array<string, mixed> $row
     */
    private function dateRange(array $row): string
    {
        $start = $this->value($row['start_date'] ?? null, '');
        $end = $this->value($row['end_date'] ?? null, '');
        if ($start === '') {
            return '-';
        }

        return $end === '' || $end === $start ? $start : $start . ' - ' . $end;
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
