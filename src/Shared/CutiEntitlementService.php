<?php

declare(strict_types=1);

namespace App\Shared;

use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class CutiEntitlementService
{
    public function __construct(
        private ConnectionInterface $db,
        private CutiRecordService $records,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function currentForStaff(string $icno, ?int $year = null): ?array
    {
        $year ??= (int) date('Y');
        $row = $this->db->createCommand(
            <<<SQL
            SELECT *
            FROM hrm.cuti_entitlement
            WHERE layak_icno = :icno
              AND YEAR(layak_tamat) = :year
            ORDER BY layak_tamat DESC, layak_id DESC
            LIMIT 1
            SQL,
            [':icno' => $icno, ':year' => $year],
        )->queryOne();

        return is_array($row) ? $this->normalize($row) : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function allForStaff(string $icno): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT *
            FROM hrm.cuti_entitlement
            WHERE layak_icno = :icno
            ORDER BY layak_mula DESC, layak_id DESC
            SQL,
            [':icno' => $icno],
        )->queryAll();

        $entitlements = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                $entitlements[] = $this->normalize($row);
            }
        }

        return $entitlements;
    }

    /**
     * @return array{before_adjustment: int, after_adjustment: int}
     */
    public function gcrTotals(string $icno, int $year = 0): array
    {
        $where = 'layak_icno = :icno';
        $params = [':icno' => $icno];
        if ($year > 0) {
            $where .= ' AND YEAR(layak_mula) = :year';
            $params[':year'] = $year;
        }

        $row = $this->db->createCommand(
            <<<SQL
            SELECT COALESCE(SUM(layak_gcr), 0) AS total_gcr,
                   COALESCE(SUM(layak_selaras), 0) AS total_adjustment
            FROM hrm.cuti_entitlement
            WHERE {$where}
            SQL,
            $params,
        )->queryOne();

        $totalGcr = is_array($row) ? (int) ($row['total_gcr'] ?? 0) : 0;
        $totalAdjustment = is_array($row) ? (int) ($row['total_adjustment'] ?? 0) : 0;

        return [
            'before_adjustment' => $totalGcr,
            'after_adjustment' => $totalGcr - $totalAdjustment,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalize(array $row): array
    {
        $total = (int) ($row['layak_cuti'] ?? 0)
            + (int) ($row['layak_bawa_lepas'] ?? 0)
            + (int) ($row['layak_selaras'] ?? 0);
        $used = $this->records->usedAnnualLeave(
            (string) ($row['layak_icno'] ?? ''),
            (string) ($row['layak_mula'] ?? ''),
            (string) ($row['layak_tamat'] ?? ''),
        );
        $balance = $total
            - $used
            - (int) ($row['layak_gcr'] ?? 0)
            - (int) ($row['layak_bawa_depan'] ?? 0);

        $row['total_layak'] = $total;
        $row['used_cuti'] = $used;
        $row['balance'] = $balance;

        return $row;
    }
}
