<?php

declare(strict_types=1);

namespace App\Shared;

use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class LantikanStafBaruTransactionService
{
    private const LOCK_NAME = 'hronline.umsper.reserve';

    public function __construct(
        private ConnectionInterface $db,
        private LantikanStafBaruService $stafBaru,
    ) {}

    public function writeEnabled(): bool
    {
        return $this->flag('HRONLINE_LANTIKAN_STAF_BARU_WRITE');
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function submit(array $input, string $createdBy, string $ipAddress): array
    {
        if (!$this->writeEnabled()) {
            $dryRun = $this->stafBaru->dryRun($input);
            $dryRun['errors']['_form'] = 'Submit Yii3 belum diaktifkan. Tetapkan HRONLINE_LANTIKAN_STAF_BARU_WRITE=1 untuk UAT terkawal.';
            $dryRun['success'] = false;
            $dryRun['mode'] = 'live';

            return $dryRun;
        }

        $prepared = $this->stafBaru->prepare($input, true);
        if (!$prepared['success']) {
            $prepared['mode'] = 'live';

            return $prepared;
        }

        $values = $prepared['values'];
        $lockAcquired = false;
        try {
            $lockAcquired = $this->acquireLock();
            if (!$lockAcquired) {
                return [
                    'success' => false,
                    'mode' => 'live',
                    'values' => $values,
                    'errors' => ['_form' => 'Terdapat proses lantikan lain yang sedang berjalan. Sila cuba sebentar lagi.'],
                    'proposed' => [],
                ];
            }

            $now = date('Y-m-d H:i:s');
            $plan = $this->db->transaction(function () use ($values, $createdBy, $ipAddress, $now): array {
                $this->assertStillUnique($values);

                $plan = $this->stafBaru->insertPlan($values, true, $createdBy, $ipAddress, $now);
                $coreTables = $plan['coreTables'] ?? [];
                if (!is_array($coreTables)) {
                    throw new \RuntimeException('Staf Baru insert plan is invalid.');
                }

                foreach ($coreTables as $table => $row) {
                    if ($row === null) {
                        continue;
                    }

                    if (!is_array($row)) {
                        throw new \RuntimeException('Invalid insert row for ' . (string) $table);
                    }

                    $this->db->createCommand()->insert((string) $table, $row)->execute();
                }

                return $plan;
            });

            return [
                'success' => true,
                'mode' => 'live',
                'values' => $values,
                'errors' => [],
                'proposed' => [
                    'Rekod lantikan telah disimpan dalam transaksi Yii3.',
                    'AD, IdP, notifikasi, allocation profile, dan kesan sampingan silang modul masih direkod sebagai post-commit/pending sehingga flag dan owner approval lengkap.',
                ],
                'plan' => $plan,
                'icno' => $values['ICNO'],
                'coOldId' => (string) (($plan['summary']['COOldID'] ?? '') ?: ''),
                'resultUrl' => '/lantikan/lantik-staf-baru/result?icno=' . rawurlencode($values['ICNO']),
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'mode' => 'live',
                'values' => $values,
                'errors' => ['_form' => 'Lantikan gagal disimpan. Semua rekod transaksi telah dibatalkan. ' . $e->getMessage()],
                'proposed' => [],
            ];
        } finally {
            if ($lockAcquired) {
                $this->releaseLock();
            }
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function reconciliation(string $icno): ?array
    {
        $staff = $this->db->createCommand(
            'SELECT ICNO, CONm, COOldID, COEmail, DeptId, statLantikan, startDateLantik, endDateLantik FROM hronline.tblprcobiodata WHERE ICNO = :icno LIMIT 1',
            [':icno' => $icno],
        )->queryOne();
        if (!is_array($staff)) {
            return null;
        }

        $tables = [
            'hronline.tblprcobiodata',
            'hronline.umsper',
            'hronline.tblrscosandangan',
            'hronline.tblrscoapmtstatus',
            'hronline.tblrscoservstatus',
            'hronline.tblpreduach',
            'hronline.tblpenempatan',
            'hronline.tblrscoaptathy',
            'hronline.tblrscoservload',
            'hronline.tblrscoconfirmstatus',
            'hronline.tblrscoprobtnperiod',
            'hronline.tblrscopsnstatus',
            'hronline.tblrscopsnathy',
            'hronline.tblrscosaltype',
            'hronline.tblrscoservtype',
            'hronline.tblrscosalmovemth',
            'hronline.tblrscofileno',
        ];

        $checks = [];
        foreach ($tables as $table) {
            $checks[] = [
                'table' => $table,
                'exists' => $this->recordExists($table, $icno),
            ];
        }

        return [
            'staff' => $staff,
            'checks' => $checks,
            'postCommit' => [
                ['label' => 'Active Directory create user', 'status' => $this->flag('HRONLINE_LANTIKAN_AD_CREATE') ? 'Pending execution status' : 'Disabled / manual follow-up'],
                ['label' => 'IdP Mata addStaff', 'status' => 'Pending owner decision'],
                ['label' => 'IT/admin notifications', 'status' => $this->flag('HRONLINE_LANTIKAN_NOTIFICATIONS') ? 'Pending execution status' : 'Disabled / manual follow-up'],
                ['label' => 'Allocation profile', 'status' => 'Legacy handoff available'],
            ],
            'allocationUrl' => LegacyUrl::to('allocation-profile/create-profile?icno=' . rawurlencode($icno)),
        ];
    }

    /**
     * @param array<string, string> $values
     */
    private function assertStillUnique(array $values): void
    {
        if ((bool) $this->db->createCommand(
            'SELECT 1 FROM hronline.tblprcobiodata WHERE ICNO = :icno LIMIT 1',
            [':icno' => $values['ICNO'] ?? ''],
        )->queryScalar()) {
            throw new \RuntimeException('No. KP / Paspot sudah wujud.');
        }

        if ((bool) $this->db->createCommand(
            'SELECT 1 FROM hronline.tblprcobiodata WHERE COEmail = :email LIMIT 1',
            [':email' => $values['COEmail'] ?? ''],
        )->queryScalar()) {
            throw new \RuntimeException('Emel rasmi sudah wujud.');
        }
    }

    private function acquireLock(): bool
    {
        return (string) $this->db->createCommand(
            'SELECT GET_LOCK(:name, 10)',
            [':name' => self::LOCK_NAME],
        )->queryScalar() === '1';
    }

    private function releaseLock(): void
    {
        $this->db->createCommand(
            'SELECT RELEASE_LOCK(:name)',
            [':name' => self::LOCK_NAME],
        )->queryScalar();
    }

    private function recordExists(string $table, string $icno): bool
    {
        return (bool) $this->db->createCommand(
            "SELECT 1 FROM {$table} WHERE ICNO = :icno LIMIT 1",
            [':icno' => $icno],
        )->queryScalar();
    }

    private function flag(string $name): bool
    {
        $value = getenv($name);

        return is_string($value) && trim($value) === '1';
    }
}
