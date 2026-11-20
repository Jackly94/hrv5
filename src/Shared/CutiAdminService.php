<?php

declare(strict_types=1);

namespace App\Shared;

use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class CutiAdminService
{
    public function __construct(
        private ConnectionInterface $db,
        private EmployeeRepository $employees,
    ) {}

    public function isAdmin(string $icno): bool
    {
        return $this->exists(
            'SELECT 1 FROM hrm.cuti_access WHERE akses_cuti_icno = :icno AND akses_cuti_int = 3 LIMIT 1',
            [':icno' => $icno],
        );
    }

    public function isSpecialAdmin(string $icno): bool
    {
        return $this->exists(
            'SELECT 1 FROM hrm.cuti_access WHERE akses_cuti_icno = :icno AND akses_cuti_int = 5 LIMIT 1',
            [':icno' => $icno],
        );
    }

    public function isGcrAdmin(string $icno): bool
    {
        return $this->exists(
            'SELECT 1 FROM hrm.cuti_access WHERE akses_cuti_icno = :icno AND akses_cuti_int = 3 AND akses_jspiu_id IN (230, 35) LIMIT 1',
            [':icno' => $icno],
        );
    }

    public function isCr2Manager(string $icno): bool
    {
        return $this->exists(
            'SELECT 1 FROM hrm.cuti_tbl_management WHERE icno = :icno LIMIT 1',
            [':icno' => $icno],
        ) || $this->exists(
            'SELECT 1 FROM hrm.cuti_access WHERE akses_cuti_icno = :icno AND akses_cuti_int = 3 AND akses_jspiu_id = 35 LIMIT 1',
            [':icno' => $icno],
        );
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    public function departments(): array
    {
        return $this->options($this->db->createCommand(
            "SELECT id, COALESCE(NULLIF(shortname, ''), fullname, CAST(id AS CHAR)) AS name FROM hronline.department WHERE isActive = 1 ORDER BY shortname, fullname",
        )->queryAll(), 'id', 'name');
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    public function years(): array
    {
        $rows = $this->db->createCommand(
            "SELECT year AS id, year AS name FROM attendance.tbl_years WHERE status = 1 ORDER BY year DESC",
        )->queryAll();
        $years = $this->options($rows, 'id', 'name');
        $next = (string) ((int) date('Y') + 1);
        foreach ($years as $year) {
            if ($year['id'] === $next) {
                return $years;
            }
        }
        array_unshift($years, ['id' => $next, 'name' => $next]);

        return $years;
    }

    /**
     * @param array<string, mixed> $query
     * @return list<array<string, mixed>>
     */
    public function cr2Queue(string $icno, array $query): array
    {
        if (!$this->isCr2Manager($icno)) {
            return [];
        }

        return $this->leaveRows("r.status = 'BSMCHECK'", [], $query);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function behalfList(): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT b.id, b.icno_pemberi_kuasa, b.icno_tindakan, b.catatan, b.status, b.create_datetime, b.update_datetime,
                   officer.CONm AS officer_name, actioner.CONm AS actioner_name
            FROM hrm.cuti_behalf_action b
            LEFT JOIN hronline.tblprcobiodata officer ON officer.ICNO = b.icno_pemberi_kuasa
            LEFT JOIN hronline.tblprcobiodata actioner ON actioner.ICNO = b.icno_tindakan
            ORDER BY b.status DESC, b.id DESC
            LIMIT 300
            SQL,
        )->queryAll();

        return $this->normalizeRows($rows);
    }

    /**
     * @param array<string, mixed> $query
     * @return list<array<string, mixed>>
     */
    public function accessList(string $icno, array $query): array
    {
        $params = [];
        $where = ['1 = 1'];
        if (!$this->isAdmin($icno)) {
            $dept = $this->db->createCommand(
                'SELECT akses_jspiu_id FROM hrm.cuti_access WHERE akses_cuti_icno = :icno AND akses_cuti_int = 5 LIMIT 1',
                [':icno' => $icno],
            )->queryScalar();
            $params[':scope_dept'] = (int) $dept;
            $where[] = 'a.akses_jspiu_id = :scope_dept';
        }
        if ($this->stringParam($query, 'q') !== '') {
            $params[':q0'] = '%' . $this->stringParam($query, 'q') . '%';
            $params[':q1'] = $params[':q0'];
            $params[':q2'] = $params[':q0'];
            $where[] = '(bio.CONm LIKE :q0 OR bio.COOldID LIKE :q1 OR a.akses_cuti_icno LIKE :q2)';
        }

        $rows = $this->db->createCommand(
            <<<SQL
            SELECT a.akses_cuti_id, a.akses_cuti_icno, a.akses_cuti_int, a.akses_jspiu_id, a.akses_kampus_id,
                   bio.CONm AS staff_name, bio.COOldID AS staff_no,
                   dept.shortname AS department_short, dept.fullname AS department_name,
                   campus.campus_name
            FROM hrm.cuti_access a
            LEFT JOIN hronline.tblprcobiodata bio ON bio.ICNO = a.akses_cuti_icno
            LEFT JOIN hronline.department dept ON dept.id = a.akses_jspiu_id
            LEFT JOIN hronline.campus campus ON campus.campus_id = a.akses_kampus_id
            WHERE {$this->sqlAnd($where)}
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
    public function gcrApplications(array $query): array
    {
        $params = [];
        $where = ['g.isActive = 1'];
        if ($this->stringParam($query, 'status') !== '') {
            $params[':status'] = $this->stringParam($query, 'status');
            $where[] = 'g.status = :status';
        }
        if ($this->intParam($query, 'year') !== null) {
            $params[':year'] = $this->intParam($query, 'year');
            $where[] = 'YEAR(g.mohon_dt) = :year';
        }

        $rows = $this->db->createCommand(
            <<<SQL
            SELECT g.*, bio.CONm AS staff_name, bio.COOldID AS staff_no,
                   dept.shortname AS department_short, dept.fullname AS department_name
            FROM hrm.cuti_gcr_application g
            LEFT JOIN hronline.tblprcobiodata bio ON bio.ICNO = g.pemohon_icno
            LEFT JOIN hronline.department dept ON dept.id = g.dept_id
            WHERE {$this->sqlAnd($where)}
            ORDER BY g.mohon_dt DESC, g.id DESC
            LIMIT 300
            SQL,
            $params,
        )->queryAll();

        return $this->normalizeRows($rows);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function openApplications(): array
    {
        return $this->normalizeRows($this->db->createCommand(
            <<<SQL
            SELECT o.*, opener.CONm AS open_by_name, updater.CONm AS update_by_name
            FROM hrm.cuti_open_application o
            LEFT JOIN hronline.tblprcobiodata opener ON opener.ICNO = o.open_by
            LEFT JOIN hronline.tblprcobiodata updater ON updater.ICNO = o.update_by
            ORDER BY o.status DESC, o.start_date DESC, o.id DESC
            LIMIT 300
            SQL,
        )->queryAll());
    }

    /**
     * @param array<string, mixed> $query
     * @return list<array<string, mixed>>
     */
    public function publicHolidays(array $query): array
    {
        $year = $this->yearParam($query);

        return $this->normalizeRows($this->db->createCommand(
            <<<SQL
            SELECT id, nama_cuti, tarikh_cuti, sabah_sahaja, wilayah_sahaja, catatan
            FROM hrm.cuti_public_holiday
            WHERE YEAR(tarikh_cuti) = :year
            ORDER BY tarikh_cuti ASC
            SQL,
            [':year' => $year],
        )->queryAll());
    }

    /**
     * @param array<string, mixed> $query
     * @return list<array<string, mixed>>
     */
    public function maternityLeave(array $query): array
    {
        return $this->leaveRows('r.jenis_cuti_id = 28', [], $query);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function gcrAllowlist(): array
    {
        return $this->normalizeRows($this->db->createCommand(
            <<<SQL
            SELECT t.*, bio.CONm AS staff_name, bio.COOldID AS staff_no, added.CONm AS added_by_name
            FROM hrm.cuti_temp_table t
            LEFT JOIN hronline.tblprcobiodata bio ON bio.ICNO = t.icno
            LEFT JOIN hronline.tblprcobiodata added ON added.ICNO = t.added_by
            ORDER BY t.allowed DESC, t.added_dt DESC, t.id DESC
            LIMIT 300
            SQL,
        )->queryAll());
    }

    /**
     * @param array<string, mixed> $query
     * @return list<array<string, mixed>>
     */
    public function staffList(array $query): array
    {
        $params = [];
        $where = ['bio.Status != 6'];
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

        return $this->normalizeRows($this->db->createCommand(
            <<<SQL
            SELECT bio.ICNO, bio.CONm AS staff_name, bio.COOldID AS staff_no, bio.Status,
                   dept.shortname AS department_short, dept.fullname AS department_name,
                   campus.campus_name
            FROM hronline.tblprcobiodata bio
            LEFT JOIN hronline.department dept ON dept.id = bio.DeptId
            LEFT JOIN hronline.campus campus ON campus.campus_id = bio.campus_id
            WHERE {$this->sqlAnd($where)}
            ORDER BY dept.shortname, bio.CONm
            LIMIT 300
            SQL,
            $params,
        )->queryAll());
    }

    /**
     * @param array<string, mixed> $query
     * @return list<array<string, mixed>>
     */
    public function departmentReport(array $query): array
    {
        return $this->leaveRows('1 = 1', [], $query);
    }

    /**
     * @param array<string, mixed> $query
     * @return list<array<string, mixed>>
     */
    public function leaveTypeStatistic(array $query): array
    {
        $params = [':year' => $this->yearParam($query)];
        $where = ['YEAR(r.start_date) = :year'];
        if ($this->intParam($query, 'department') !== null) {
            $params[':department'] = $this->intParam($query, 'department');
            $where[] = 'bio.DeptId = :department';
        }

        $rows = $this->db->createCommand(
            <<<SQL
            SELECT r.jenis_cuti_id, lt.jenis_cuti_catatan, lt.jenis_cuti_nama,
                   COUNT(r.id) AS total_records, COALESCE(SUM(r.tempoh), 0) AS total_days
            FROM hrm.cuti_tbl_records r
            LEFT JOIN hronline.tblprcobiodata bio ON bio.ICNO = r.icno
            LEFT JOIN hrm.cuti_leave_type lt ON lt.jenis_cuti_id = r.jenis_cuti_id
            WHERE {$this->sqlAnd($where)}
            GROUP BY r.jenis_cuti_id, lt.jenis_cuti_catatan, lt.jenis_cuti_nama
            ORDER BY total_records DESC, lt.jenis_cuti_catatan
            LIMIT 300
            SQL,
            $params,
        )->queryAll();

        return $this->normalizeRows($rows);
    }

    /**
     * @param array<string, mixed> $params
     * @param array<string, mixed> $query
     * @return list<array<string, mixed>>
     */
    private function leaveRows(string $baseWhere, array $params, array $query): array
    {
        $where = [$baseWhere];
        if ($this->intParam($query, 'department') !== null) {
            $params[':department'] = $this->intParam($query, 'department');
            $where[] = 'bio.DeptId = :department';
        }
        if ($this->intParam($query, 'year') !== null) {
            $params[':year'] = $this->intParam($query, 'year');
            $where[] = 'YEAR(r.start_date) = :year';
        }
        if ($this->stringParam($query, 'status') !== '') {
            $params[':status'] = $this->stringParam($query, 'status');
            $where[] = 'r.status = :status';
        }

        $rows = $this->db->createCommand(
            <<<SQL
            SELECT r.id, r.icno, r.jenis_cuti_id, r.full_date, r.start_date, r.end_date,
                   r.tempoh, r.remark, r.status, r.mohon_dt, r.semakan_by, r.peraku_by, r.lulus_by,
                   bio.CONm AS staff_name, bio.COOldID AS staff_no,
                   dept.shortname AS department_short, dept.fullname AS department_name,
                   lt.jenis_cuti_catatan, lt.jenis_cuti_nama
            FROM hrm.cuti_tbl_records r
            LEFT JOIN hronline.tblprcobiodata bio ON bio.ICNO = r.icno
            LEFT JOIN hronline.department dept ON dept.id = bio.DeptId
            LEFT JOIN hrm.cuti_leave_type lt ON lt.jenis_cuti_id = r.jenis_cuti_id
            WHERE {$this->sqlAnd($where)}
            ORDER BY r.start_date DESC, r.id DESC
            LIMIT 300
            SQL,
            $params,
        )->queryAll();

        return $this->normalizeRows($rows);
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
            foreach (['staff_name', 'officer_name', 'actioner_name', 'open_by_name', 'update_by_name', 'added_by_name'] as $nameKey) {
                if (array_key_exists($nameKey, $row)) {
                    $row[$nameKey] = $this->employees->displayName($row[$nameKey]);
                }
            }
            $row['department_label'] = $this->value($row['department_short'] ?? $row['department_name'] ?? null);
            $row['campus_label'] = $this->value($row['campus_name'] ?? null);
            $row['jenis_cuti_label'] = $this->value($row['jenis_cuti_catatan'] ?? $row['jenis_cuti_nama'] ?? null);
            $row['date_label'] = $this->value($row['full_date'] ?? null, $this->dateRange($row));
            $row['status_label'] = $this->value($row['status'] ?? $row['allowed'] ?? null, '-');
            $row['access_label'] = $this->accessLabel((int) ($row['akses_cuti_int'] ?? 0));
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
            if ($id !== '') {
                $options[] = ['id' => $id, 'name' => $this->value($row[$nameKey] ?? null, $id)];
            }
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

    private function accessLabel(int $access): string
    {
        return match ($access) {
            2 => 'Penyelia Cuti',
            3 => 'Admin',
            4 => 'Pemantau',
            5 => 'Admin JAFPIB',
            default => '-',
        };
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
