<?php

declare(strict_types=1);

namespace App\Shared;

use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class CutiOfficerService
{
    public function __construct(
        private ConnectionInterface $db,
        private EmployeeRepository $employees,
    ) {}

    public function isOfficer(string $icno): bool
    {
        return $this->exists(
            <<<SQL
            SELECT 1
            FROM hrm.cuti_set_pegawai
            WHERE peraku_icno = :icno OR pelulus_icno = :icno
            LIMIT 1
            SQL,
            [':icno' => $icno],
        ) || $this->exists(
            "SELECT 1 FROM hrm.cuti_behalf_action WHERE icno_tindakan = :icno AND status = '1' LIMIT 1",
            [':icno' => $icno],
        );
    }

    public function actingIcno(string $icno): string
    {
        $acting = $this->db->createCommand(
            "SELECT icno_pemberi_kuasa FROM hrm.cuti_behalf_action WHERE icno_tindakan = :icno AND status = '1' ORDER BY id DESC LIMIT 1",
            [':icno' => $icno],
        )->queryScalar();

        return is_scalar($acting) && trim((string) $acting) !== '' ? trim((string) $acting) : $icno;
    }

    public function isDepartment230Chief(string $icno): bool
    {
        return $this->exists(
            'SELECT 1 FROM hronline.department WHERE id = 230 AND chief = :icno LIMIT 1',
            [':icno' => $icno],
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pendingVerification(string $icno): array
    {
        return $this->leaveRows(
            'r.peraku_by = :icno AND r.status IN (\'AGREED\', \'CHECK\') AND r.jenis_cuti_id != 17',
            [':icno' => $this->actingIcno($icno)],
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pendingApproval(string $icno): array
    {
        return $this->leaveRows(
            'r.lulus_by = :icno AND r.status IN (\'CHECKED\', \'VERIFIED\') AND r.jenis_cuti_id != 17',
            [':icno' => $this->actingIcno($icno)],
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function approvedLeaveList(string $icno, int $year, ?int $month = null): array
    {
        $params = [':icno' => $this->actingIcno($icno), ':year' => $year];
        $monthSql = '';
        if ($month !== null && $month >= 1 && $month <= 12) {
            $monthSql = ' AND MONTH(r.start_date) = :month';
            $params[':month'] = $month;
        }

        return $this->leaveRows(
            "(r.peraku_by = :icno OR r.lulus_by = :icno) AND r.status = 'APPROVED' AND YEAR(r.start_date) = :year" . $monthSql,
            $params,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function staffUnderSupervision(string $icno): array
    {
        $actingIcno = $this->actingIcno($icno);
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT DISTINCT s.set_pegawai_id, s.pemohon_icno, s.jenis_cuti_id, s.peraku_icno, s.pelulus_icno,
                   staff.CONm AS staff_name, staff.COOldID AS staff_no,
                   dept.shortname AS department_short, dept.fullname AS department_name,
                   lt.jenis_cuti_catatan, lt.jenis_cuti_nama,
                   peraku.CONm AS peraku_name,
                   pelulus.CONm AS pelulus_name
            FROM hrm.cuti_set_pegawai s
            LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = s.pemohon_icno
            LEFT JOIN hronline.department dept ON dept.id = staff.DeptId
            LEFT JOIN hrm.cuti_leave_type lt ON lt.jenis_cuti_id = s.jenis_cuti_id
            LEFT JOIN hronline.tblprcobiodata peraku ON peraku.ICNO = s.peraku_icno
            LEFT JOIN hronline.tblprcobiodata pelulus ON pelulus.ICNO = s.pelulus_icno
            WHERE s.peraku_icno = :icno OR s.pelulus_icno = :icno
            ORDER BY staff.CONm, s.jenis_cuti_id
            SQL,
            [':icno' => $actingIcno],
        )->queryAll();

        return $this->normalizeRows($rows);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function gcrCheckedList(string $icno): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT g.*, staff.CONm AS staff_name, staff.COOldID AS staff_no,
                   dept.shortname AS department_short, dept.fullname AS department_name,
                   semak.CONm AS semakan_name,
                   peraku.CONm AS peraku_name,
                   lulus.CONm AS lulus_name
            FROM hrm.cuti_gcr_application g
            LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = g.pemohon_icno
            LEFT JOIN hronline.department dept ON dept.id = g.dept_id
            LEFT JOIN hronline.tblprcobiodata semak ON semak.ICNO = g.semakan_by
            LEFT JOIN hronline.tblprcobiodata peraku ON peraku.ICNO = g.peraku_by
            LEFT JOIN hronline.tblprcobiodata lulus ON lulus.ICNO = g.lulus_by
            WHERE g.peraku_by = :icno
              AND g.status = 'CHECKED'
            ORDER BY g.mohon_dt ASC, g.id ASC
            SQL,
            [':icno' => $this->actingIcno($icno)],
        )->queryAll();

        return $this->normalizeRows($rows);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function bsmGcrDepartments(string $icno): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT g.dept_id, dept.shortname AS department_short, dept.fullname AS department_name, COUNT(*) AS total
            FROM hrm.cuti_gcr_application g
            LEFT JOIN hronline.department dept ON dept.id = g.dept_id
            WHERE g.lulus_by = :icno
              AND g.status = 'VERIFIED'
            GROUP BY g.dept_id, dept.shortname, dept.fullname
            ORDER BY dept.shortname, dept.fullname
            SQL,
            [':icno' => $icno],
        )->queryAll();

        return $this->normalizeRows($rows);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function backOnDutyList(string $icno): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT bod.*, r.full_date, r.start_date, r.end_date, r.jenis_cuti_id,
                   staff.CONm AS staff_name, staff.COOldID AS staff_no,
                   dept.shortname AS department_short, dept.fullname AS department_name,
                   lt.jenis_cuti_catatan, lt.jenis_cuti_nama,
                   semak.CONm AS semakan_name,
                   pelulus.CONm AS pelulus_name,
                   bsm.CONm AS bsm_name
            FROM hrm.cuti_tbl_bod bod
            LEFT JOIN hrm.cuti_tbl_records r ON r.id = bod.record_id
            LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = r.icno
            LEFT JOIN hronline.department dept ON dept.id = staff.DeptId
            LEFT JOIN hrm.cuti_leave_type lt ON lt.jenis_cuti_id = r.jenis_cuti_id
            LEFT JOIN hronline.tblprcobiodata semak ON semak.ICNO = bod.semakan_id
            LEFT JOIN hronline.tblprcobiodata pelulus ON pelulus.ICNO = bod.pelulus_id
            LEFT JOIN hronline.tblprcobiodata bsm ON bsm.ICNO = bod.bsm_id
            WHERE bod.pelulus_id = :icno
              AND bod.status = 1
            ORDER BY bod.date_bod ASC, bod.id ASC
            SQL,
            [':icno' => $this->actingIcno($icno)],
        )->queryAll();

        return $this->normalizeRows($rows);
    }

    /**
     * @param array<string, mixed> $params
     * @return list<array<string, mixed>>
     */
    private function leaveRows(string $where, array $params): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT r.id, r.icno, r.jenis_cuti_id, r.full_date, r.start_date, r.end_date,
                   r.tempoh, r.remark, r.destination, r.status, r.mohon_dt,
                   r.ganti_by, r.semakan_by, r.peraku_by, r.lulus_by,
                   staff.CONm AS staff_name, staff.COOldID AS staff_no,
                   dept.shortname AS department_short, dept.fullname AS department_name,
                   lt.jenis_cuti_catatan, lt.jenis_cuti_nama,
                   ganti.CONm AS ganti_name,
                   semak.CONm AS semakan_name,
                   peraku.CONm AS peraku_name,
                   lulus.CONm AS lulus_name
            FROM hrm.cuti_tbl_records r
            LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = r.icno
            LEFT JOIN hronline.department dept ON dept.id = staff.DeptId
            LEFT JOIN hrm.cuti_leave_type lt ON lt.jenis_cuti_id = r.jenis_cuti_id
            LEFT JOIN hronline.tblprcobiodata ganti ON ganti.ICNO = r.ganti_by
            LEFT JOIN hronline.tblprcobiodata semak ON semak.ICNO = r.semakan_by
            LEFT JOIN hronline.tblprcobiodata peraku ON peraku.ICNO = r.peraku_by
            LEFT JOIN hronline.tblprcobiodata lulus ON lulus.ICNO = r.lulus_by
            WHERE {$where}
            ORDER BY r.start_date DESC, r.id DESC
            SQL,
            $params,
        )->queryAll();

        return $this->normalizeRows($rows);
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

            foreach (['staff_name', 'semakan_name', 'peraku_name', 'pelulus_name', 'lulus_name', 'ganti_name', 'bsm_name'] as $nameKey) {
                if (array_key_exists($nameKey, $row)) {
                    $row[$nameKey] = $this->employees->displayName($row[$nameKey]);
                }
            }
            $row['department_label'] = $this->value($row['department_short'] ?? $row['department_name'] ?? null);
            $row['jenis_cuti_label'] = $this->value($row['jenis_cuti_catatan'] ?? $row['jenis_cuti_nama'] ?? null);
            $row['date_label'] = $this->value($row['full_date'] ?? null, $this->dateRange($row));
            $row['status_label'] = $this->value($row['status'] ?? null, '-');

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
        if ($start === '') {
            return '-';
        }

        return $end === '' || $end === $start ? $start : $start . ' - ' . $end;
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
