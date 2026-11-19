<?php

declare(strict_types=1);

namespace App\Shared;

use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class CutiDashboardService
{
    public function __construct(
        private ConnectionInterface $db,
        private EmployeeRepository $employees,
        private CutiAccessService $access,
        private CutiMenuService $menu,
        private CutiRecordService $records,
        private CutiEntitlementService $entitlements,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function dashboardForIdentity(string $identityId): ?array
    {
        $icno = $this->employees->icnoFromIdentity($identityId);
        $staff = $this->employees->byIcno($icno);
        if ($icno === '' || $staff === null) {
            return null;
        }

        $year = (int) date('Y');

        return [
            'icno' => $icno,
            'staffName' => $this->employees->displayName($staff['CONm'] ?? null),
            'department' => $this->employees->value($staff['department_name'] ?? null),
            'year' => $year,
            'access' => $this->access->flags($icno),
            'menuSections' => $this->menu->sectionsForStaff($icno),
            'pendingTasks' => $this->pendingTasks($icno),
            'records' => $this->records->recordsForStaff($icno, $year),
            'substituteRecords' => $this->records->substituteRecords($icno, $year),
            'currentEntitlement' => $this->entitlements->currentForStaff($icno, $year),
            'entitlements' => $this->entitlements->allForStaff($icno),
        ];
    }

    /**
     * @return list<array<string, string>>
     */
    private function pendingTasks(string $icno): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT name, count, url, icon
            FROM system_core.tbl_pending_task
            WHERE status = 1
              AND icno = :icno
              AND command_id IN (7, 8, 9, 10, 55, 56, 58, 81)
              AND count > 0
            ORDER BY name
            SQL,
            [':icno' => $icno],
        )->queryAll();

        $tasks = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $tasks[] = [
                'name' => (string) ($row['name'] ?? ''),
                'count' => (string) ($row['count'] ?? '0'),
                'url' => CutiMenuService::normalizeRoute((string) ($row['url'] ?? '')),
                'icon' => (string) ($row['icon'] ?? 'check'),
            ];
        }

        return $tasks;
    }
}
