<?php

declare(strict_types=1);

namespace App\Shared;

use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class EmployeeRepository
{
    public function __construct(
        private ConnectionInterface $db,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function byIcno(string $icno): ?array
    {
        $row = $this->db->createCommand(
            <<<SQL
            SELECT bio.ICNO, bio.CONm, bio.COOldID, bio.COEmail, bio.DeptId, bio.campus_id,
                   bio.KodProgram, bio.Status, dept.fullname AS department_name, campus.campus_name
            FROM hronline.tblprcobiodata bio
            LEFT JOIN hronline.department dept ON dept.id = bio.DeptId
            LEFT JOIN hronline.campus campus ON campus.campus_id = bio.campus_id
            WHERE bio.ICNO = :icno
            LIMIT 1
            SQL,
            [':icno' => $icno],
        )->queryOne();

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function byIdentityId(string $identityId): ?array
    {
        return $this->byIcno($this->icnoFromIdentity($identityId));
    }

    public function icnoFromIdentity(string $identityId): string
    {
        return str_starts_with($identityId, 'staff-') ? substr($identityId, 6) : $identityId;
    }

    public function displayName(mixed $value): string
    {
        if (!is_scalar($value)) {
            return 'Tiada Rekod';
        }

        $value = trim((string) $value);

        return $value === '' ? 'Tiada Rekod' : ucwords(strtolower($value));
    }

    public function staffNumber(array $row): string
    {
        return $this->value($row['COOldID'] ?? null);
    }

    public function value(mixed $value, string $fallback = 'Tiada Rekod'): string
    {
        if ($value === null) {
            return $fallback;
        }

        $value = trim((string) $value);

        return $value === '' ? $fallback : $value;
    }
}
