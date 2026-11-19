<?php

declare(strict_types=1);

namespace App\Shared;

use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class DashboardService
{
    public function __construct(
        private ConnectionInterface $db,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function dashboardForIdentity(string $identityId): ?array
    {
        if (!str_starts_with($identityId, 'staff-')) {
            return null;
        }

        $icno = substr($identityId, strlen('staff-'));
        if ($icno === '') {
            return null;
        }

        $staff = $this->staff($icno);
        if ($staff === null) {
            return null;
        }

        $isSecurity = $this->isSecurityStaff($icno);

        return [
            'icno' => $icno,
            'staff' => $staff,
            'summary' => $this->summary($icno),
            'pendingTasks' => $this->pendingTasks($icno),
            'attendance' => [
                'type' => $isSecurity ? 'SKB' : 'STARS',
                'cardColor' => $this->cardColor($icno, $isSecurity),
                'mode' => $isSecurity ? 'Security attendance' : 'Staff attendance',
            ],
            'shortcuts' => $this->shortcuts($isSecurity),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function staff(string $icno): ?array
    {
        $row = $this->db->createCommand(
            <<<SQL
            SELECT ICNO, CONm, COOldID, DeptId, COEmail
            FROM hronline.tblprcobiodata
            WHERE ICNO = :icno
            LIMIT 1
            SQL,
            [':icno' => $icno],
        )->queryOne();

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<string, string>
     */
    private function summary(string $icno): array
    {
        $row = $this->db->createCommand(
            <<<SQL
            SELECT cuti, percentagecuti, idp, percentageidp, klinikpanel,
                   total_klinikpanel, percentageklinik, warnakad, tarikh_kemaskini
            FROM system_core.tbl_dashboard
            WHERE icno = :icno
            LIMIT 1
            SQL,
            [':icno' => $icno],
        )->queryOne();

        if (!is_array($row)) {
            return [
                'cuti' => '-',
                'percentagecuti' => '0',
                'idp' => '-',
                'percentageidp' => '0',
                'klinikpanel' => '0',
                'total_klinikpanel' => '0',
                'percentageklinik' => '0',
                'warnakad' => '-',
                'tarikh_kemaskini' => '-',
            ];
        }

        return array_map(static fn (mixed $value): string => $value === null ? '-' : (string) $value, $row);
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
              AND icno IN (:icno, 'all')
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

            [$title, $detail] = array_pad(explode(':', (string) ($row['name'] ?? ''), 2), 2, '');
            $tasks[] = [
                'title' => trim($title),
                'detail' => trim($detail),
                'count' => (string) ($row['count'] ?? '0'),
                'url' => (string) ($row['url'] ?? ''),
                'icon' => (string) ($row['icon'] ?? 'check'),
            ];
        }

        return $tasks;
    }

    private function isSecurityStaff(string $icno): bool
    {
        return (bool) $this->db->createCommand(
            <<<SQL
            SELECT 1
            FROM keselamatan.tbl_staff_keselamatan
            WHERE staff_icno = :icno
              AND isExcluded = 0
              AND isActive = 1
            LIMIT 1
            SQL,
            [':icno' => $icno],
        )->queryScalar();
    }

    private function cardColor(string $icno, bool $isSecurity): string
    {
        $table = $isSecurity ? 'keselamatan.tbl_warnakad' : 'attendance.tbl_warnakad';
        $today = new \DateTimeImmutable();
        $month = (int) $today->format('m');
        $year = (int) $today->format('Y');

        if (!$isSecurity) {
            if ($month === 1) {
                $month = 12;
                $year--;
            } elseif ((int) $today->format('d') <= 9) {
                $month--;
            }
        } elseif ((int) $today->format('d') <= 3) {
            $month = $month === 1 ? 12 : $month - 1;
            $year = $month === 12 ? $year - 1 : $year;
        }

        $color = $this->db->createCommand(
            <<<SQL
            SELECT color
            FROM {$table}
            WHERE icno = :icno
              AND MONTH(month_date) = :month
              AND YEAR(month_date) = :year
            LIMIT 1
            SQL,
            [
                ':icno' => $icno,
                ':month' => $month,
                ':year' => $year,
            ],
        )->queryScalar();

        return is_string($color) && $color !== '' ? strtolower($color) : '-';
    }

    /**
     * @return list<array{label: string, url: string, external: bool}>
     */
    private function shortcuts(bool $isSecurity): array
    {
        $shortcuts = [
            ['label' => 'Main Menu', 'url' => '/menu', 'external' => false],
            ['label' => 'Cuti', 'url' => '/cuti/individu/index', 'external' => false],
            ['label' => 'MyIDP', 'url' => LegacyUrl::to('idp/index'), 'external' => true],
            ['label' => 'Klinik Panel (MyHealth)', 'url' => LegacyUrl::to('klinikpanel/index'), 'external' => true],
            ['label' => 'Laporan Kehadiran', 'url' => LegacyUrl::to('kehadiran/laporan_kehadiran'), 'external' => true],
            ['label' => 'Keluar Pejabat', 'url' => 'https://nexfin.ums.edu.my/app/travel-request', 'external' => true],
        ];

        if ($isSecurity) {
            array_unshift($shortcuts, [
                'label' => 'Qr Reader',
                'url' => LegacyUrl::to('patrol/scan/qr-reader'),
                'external' => true,
            ]);
        }

        return $shortcuts;
    }
}
