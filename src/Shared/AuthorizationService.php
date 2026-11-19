<?php

declare(strict_types=1);

namespace App\Shared;

use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class AuthorizationService
{
    public function __construct(
        private ConnectionInterface $db,
        private EmployeeRepository $employees,
    ) {}

    public function canAccessController(string $icno, string $controllerId): bool
    {
        if ($icno === '' || !$this->staffExists($icno)) {
            return false;
        }

        return (bool) $this->db->createCommand(
            <<<SQL
            SELECT 1
            FROM system_core.tbl_user_role user_role
            INNER JOIN system_core.tbl_role role ON role.role_id = user_role.role_id
            WHERE user_role.user_id = :icno AND role.controller_id = :controller
            LIMIT 1
            SQL,
            [':icno' => $icno, ':controller' => $controllerId],
        )->queryScalar();
    }

    public function canAccessOwnRecord(string $loggedInIcno, string $recordIcno): bool
    {
        return $loggedInIcno !== '' && hash_equals($loggedInIcno, $recordIcno);
    }

    public function canAccessStaffRecord(string $loggedInIcno, string $targetIcno): bool
    {
        return $this->canAccessOwnRecord($loggedInIcno, $targetIcno)
            || ($this->isKetuaProgram($loggedInIcno) && $this->isInSameProgram($loggedInIcno, $targetIcno));
    }

    public function staffExists(string $icno): bool
    {
        $row = $this->employees->byIcno($icno);

        return $row !== null && (string) ($row['Status'] ?? '') !== '6';
    }

    public function isKetuaProgram(string $icno): bool
    {
        return (bool) $this->db->createCommand(
            'SELECT 1 FROM hronline.department WHERE pp = :icno LIMIT 1',
            [':icno' => $icno],
        )->queryScalar();
    }

    public function isInSameProgram(string $loggedInIcno, string $targetIcno): bool
    {
        $loggedIn = $this->employees->byIcno($loggedInIcno);
        $target = $this->employees->byIcno($targetIcno);
        if ($loggedIn === null || $target === null) {
            return false;
        }

        $loggedInProgram = trim((string) ($loggedIn['KodProgram'] ?? ''));
        $targetProgram = trim((string) ($target['KodProgram'] ?? ''));

        return $loggedInProgram !== '' && $loggedInProgram === $targetProgram;
    }

    public function isFpskPp(string $icno): bool
    {
        $pp = $this->db->createCommand(
            'SELECT pp FROM hronline.department WHERE id = :dept LIMIT 1',
            [':dept' => '138'],
        )->queryScalar();

        return is_scalar($pp) && hash_equals((string) $pp, $icno);
    }
}
