<?php

declare(strict_types=1);

namespace App\Shared;

use DateTimeImmutable;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class CutiAccessService
{
    public function __construct(
        private ConnectionInterface $db,
    ) {}

    public function isCutiAdmin(string $icno): bool
    {
        return $this->hasAccess($icno, 3);
    }

    public function isAdminJafpib(string $icno): bool
    {
        return $this->hasAccess($icno, 5);
    }

    public function isObserver(string $icno): bool
    {
        return $this->exists(
            'SELECT 1 FROM hrm.cuti_access WHERE akses_cuti_icno = :icno AND akses_cuti_int IN (3, 4) LIMIT 1',
            [':icno' => $icno],
        );
    }

    public function hasAnyCutiAccess(string $icno): bool
    {
        return $this->exists(
            'SELECT 1 FROM hrm.cuti_access WHERE akses_cuti_icno = :icno LIMIT 1',
            [':icno' => $icno],
        );
    }

    public function isSupervisorMenuAllowed(string $icno): bool
    {
        return $this->exists(
            "SELECT 1 FROM hrm.cuti_access WHERE akses_cuti_icno = :icno AND akses_cuti_int != 4 LIMIT 1",
            [':icno' => $icno],
        );
    }

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

    public function isDepartment230Chief(string $icno): bool
    {
        return $this->exists(
            'SELECT 1 FROM hronline.department WHERE chief = :icno AND id = 230 LIMIT 1',
            [':icno' => $icno],
        );
    }

    public function isDepartmentChiefOrPp(string $icno): bool
    {
        return $this->exists(
            'SELECT 1 FROM hronline.department WHERE chief = :icno OR pp = :icno LIMIT 1',
            [':icno' => $icno],
        );
    }

    public function isGcrApplicationVisible(string $icno): bool
    {
        if ($this->exists(
            'SELECT 1 FROM hrm.cuti_temp_table WHERE icno = :icno AND allowed = 1 LIMIT 1',
            [':icno' => $icno],
        )) {
            return true;
        }

        return $this->exists(
            'SELECT 1 FROM hrm.cuti_open_application WHERE status = 1 AND end_date > :today LIMIT 1',
            [':today' => (new DateTimeImmutable('today'))->format('Y-m-d')],
        );
    }

    public function isPermanentStaff(string $icno): bool
    {
        return $this->exists(
            'SELECT 1 FROM hronline.tblprcobiodata WHERE ICNO = :icno AND statLantikan = 1 LIMIT 1',
            [':icno' => $icno],
        );
    }

    /**
     * @return array<string, bool>
     */
    public function flags(string $icno): array
    {
        return [
            'any' => $this->hasAnyCutiAccess($icno),
            'admin' => $this->isCutiAdmin($icno),
            'adminJafpib' => $this->isAdminJafpib($icno),
            'observer' => $this->isObserver($icno),
            'supervisor' => $this->isSupervisorMenuAllowed($icno),
            'officer' => $this->isOfficer($icno),
            'department230Chief' => $this->isDepartment230Chief($icno),
            'departmentChiefOrPp' => $this->isDepartmentChiefOrPp($icno),
            'gcrVisible' => $this->isGcrApplicationVisible($icno),
            'permanentStaff' => $this->isPermanentStaff($icno),
        ];
    }

    private function hasAccess(string $icno, int $accessType): bool
    {
        return $this->exists(
            'SELECT 1 FROM hrm.cuti_access WHERE akses_cuti_icno = :icno AND akses_cuti_int = :accessType LIMIT 1',
            [':icno' => $icno, ':accessType' => $accessType],
        );
    }

    /**
     * @param array<string, mixed> $params
     */
    private function exists(string $sql, array $params): bool
    {
        return (bool) $this->db->createCommand($sql, $params)->queryScalar();
    }
}
