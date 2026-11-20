<?php

declare(strict_types=1);

namespace App\Shared;

use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class LantikanService
{
    public function __construct(
        private ConnectionInterface $db,
        private EmployeeRepository $employees,
        private StaffDateFormatter $dateFormatter,
        private LantikanReferenceService $references,
        private ActiveDirectoryApiClient $activeDirectory,
        private LantikanStafBaruService $stafBaruService,
        private LantikanStafBaruTransactionService $stafBaruTransactionService,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function dashboardForIdentity(string $identityId): ?array
    {
        $icno = $this->employees->icnoFromIdentity($identityId);
        $staff = $this->employees->byIcno($icno);
        if ($staff === null || !$this->canAccess($icno)) {
            return null;
        }

        return [
            'tiles' => [
                $this->tile('Staf Baru', 'Lantikan staf baharu, termasuk lantikan tetap dan kontrak.', 'user-plus', 'lantikan/lantik-staf-baru', false, '/lantikan/lantik-staf-baru'),
                $this->tile('PHS', 'Lantikan kontrak jabatan dan Pekerja Sambilan Harian.', 'clock', 'lantikan/lantik-phs', true),
                $this->tile('Khas', 'Lantikan khas dan sementara.', 'star', 'lantikan/lantik-khas', true),
                $this->tile('Staf UMS', 'Lantikan semula staf UMS yang telah tamat perkhidmatan.', 'refresh-cw', 'lantikan/lssu-index', false, '/lantikan/lssu-index'),
                $this->tile('Sambilan Akademik', 'Lantikan sambilan akademik.', 'book-open', 'lantikan/lantik-sambilanakademik', true, '', false),
                $this->tile('Lantikan LPU', 'Lembaga Pengarah Universiti.', 'landmark', 'lantikan/lantikan-lpu', true, '', false),
                $this->tile('Lantikan SPDP', 'Lantikan Staf Skim SPDP UMS.', 'badge-check', 'lantikan/lantik-spdp', true),
            ],
            'adEnabled' => $this->activeDirectory->enabled(),
        ];
    }

    public function canAccess(string $icno): bool
    {
        if ($icno === '') {
            return false;
        }

        if ((bool) $this->db->createCommand(
            'SELECT 1 FROM system_core.tbl_user_access WHERE icno = :icno AND access = 1 LIMIT 1',
            [':icno' => $icno],
        )->queryScalar()) {
            return true;
        }

        $row = $this->db->createCommand(
            'SELECT accessLevel, accessSecondLevel FROM hronline.tblprcobiodata WHERE ICNO = :icno LIMIT 1',
            [':icno' => $icno],
        )->queryOne();
        if (!is_array($row)) {
            return false;
        }

        $level = (string) ($row['accessLevel'] ?? '');
        $second = (string) ($row['accessSecondLevel'] ?? '');

        return $level === '1'
            || ($level === '2' && in_array($second, ['1', '3'], true))
            || ($level === '3' && in_array($second, ['8', '9'], true));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function formBlueprintForIdentity(string $identityId, string $flow): ?array
    {
        $icno = $this->employees->icnoFromIdentity($identityId);
        $staff = $this->employees->byIcno($icno);
        if ($staff === null || !$this->canAccess($icno)) {
            return null;
        }

        $titles = [
            'staf-baru' => 'Lantik Staf Baru',
            'phs' => 'Lantik PHS',
            'khas' => 'Lantik Kakitangan Khas',
            'spdp' => 'Lantik SPDP',
        ];

        return [
            'flow' => $flow,
            'title' => $titles[$flow] ?? 'Lantikan',
            'staffName' => $this->employees->displayName($staff['CONm'] ?? null),
            'fields' => $this->references->appointmentFields($flow),
            'options' => $this->references->options(),
            'values' => [],
            'legacyUrl' => $this->legacyUrl($this->legacyRouteForFlow($flow)),
            'adEnabled' => $this->activeDirectory->enabled(),
            'writeEnabled' => $flow === 'staf-baru' && $this->stafBaruTransactionService->writeEnabled(),
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, values: array<string, string>, errors: array<string, string>, proposed: list<string>, plan?: array<string, mixed>}
     */
    public function dryRunAppointment(string $flow, array $input): array
    {
        if ($flow === 'staf-baru') {
            return $this->stafBaruService->dryRun($input);
        }

        $fields = $this->references->appointmentFields($flow);
        $values = [];
        $errors = [];
        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'];
            $value = $type === 'checkbox'
                ? (isset($input[$name]) && (string) $input[$name] === '1' ? '1' : '0')
                : trim((string) ($input[$name] ?? ''));
            $values[$name] = $value;

            if (($field['required'] ?? false) && $value === '') {
                $errors[$name] = $field['label'] . ' diperlukan.';
            }

            if ($type === 'email' && $value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                $errors[$name] = 'Format emel tidak sah.';
            }

            if ($type === 'date' && $value !== '' && !$this->dateFormatter->isValidFormDate($value)) {
                $errors[$name] = $field['label'] . ' tidak sah.';
            }
        }

        if (($values['COEmail'] ?? '') !== '' && !str_ends_with(strtolower($values['COEmail']), '@ums.edu.my')) {
            $errors['COEmail'] = 'Emel rasmi mesti menggunakan domain @ums.edu.my.';
        }

        return [
            'success' => $errors === [],
            'values' => $values,
            'errors' => $errors,
            'proposed' => [
                'Dry-run only: no appointment records were inserted.',
                'Would create/update hronline.tblprcobiodata and dependent appointment history tables.',
                'Would call AD create only after a successful DB commit when AD is enabled.',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function submitAppointment(string $identityId, string $flow, array $input, string $ipAddress): array
    {
        $icno = $this->employees->icnoFromIdentity($identityId);
        if (!$this->canAccess($icno)) {
            return [
                'success' => false,
                'mode' => 'live',
                'values' => [],
                'errors' => ['_form' => 'Anda tidak dibenarkan melaksanakan transaksi Lantikan.'],
                'proposed' => [],
            ];
        }

        if ($flow !== 'staf-baru') {
            $result = $this->dryRunAppointment($flow, $input);
            $result['success'] = false;
            $result['mode'] = 'live';
            $result['errors']['_form'] = 'Submit Yii3 untuk aliran ini belum dimigrasikan. Sila gunakan fallback Yii2.';

            return $result;
        }

        return $this->stafBaruTransactionService->submit($input, $icno, $ipAddress);
    }

    public function suggestedEmail(string $name): string
    {
        return $this->references->emailSuggestion($name);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>|null
     */
    public function reappointmentSearchForIdentity(string $identityId, array $filters): ?array
    {
        $icno = $this->employees->icnoFromIdentity($identityId);
        if (!$this->canAccess($icno)) {
            return null;
        }

        $params = [];
        $where = ["bio.Status = '06'"];
        $this->applyFilter($where, $params, 'bio.DeptId', 'department', $filters);
        $this->applyFilter($where, $params, 'bio.statLantikan', 'appointmentStatus', $filters);
        $this->applyFilter($where, $params, 'bio.HighestEduLevelCd', 'educationLevel', $filters);

        $access = $this->db->createCommand(
            'SELECT accessLevel, accessSecondLevel, DeptId FROM hronline.tblprcobiodata WHERE ICNO = :icno LIMIT 1',
            [':icno' => $icno],
        )->queryOne();
        if (is_array($access) && (string) ($access['accessLevel'] ?? '') === '3' && in_array((string) ($access['accessSecondLevel'] ?? ''), ['8', '9'], true)) {
            $where[] = 'bio.DeptId = :currentDept';
            $params[':currentDept'] = (string) ($access['DeptId'] ?? '');
        }

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $mode = (string) ($filters['mode'] ?? 'icno');
            $column = match ($mode) {
                'name' => 'bio.CONm',
                'staffNo' => 'bio.COOldID',
                default => 'bio.ICNO',
            };
            $where[] = "{$column} LIKE :search";
            $params[':search'] = '%' . $search . '%';
        }

        $category = trim((string) ($filters['jobCategory'] ?? ''));
        $join = '';
        if ($category !== '') {
            $join = ' LEFT JOIN hronline.gredjawatan job ON job.id = bio.gredJawatan';
            $where[] = 'job.job_category = :jobCategory';
            $params[':jobCategory'] = $category;
        }

        $rows = $this->db->createCommand(
            <<<SQL
            SELECT bio.ICNO, bio.COOldID, bio.CONm, bio.DeptId, bio.statLantikan, bio.HighestEduLevelCd,
                   dept.fullname AS department_name, status.ApmtStatusNm, edu.HighestEduLevel
            FROM hronline.tblprcobiodata bio
            LEFT JOIN hronline.department dept ON dept.id = bio.DeptId
            LEFT JOIN hronline.appointmentstatus status ON status.ApmtStatusCd = bio.statLantikan
            LEFT JOIN hronline.educationallevel edu ON edu.HighestEduLevelCd = bio.HighestEduLevelCd
            {$join}
            WHERE {$this->whereSql($where)}
            ORDER BY bio.CONm
            LIMIT 100
            SQL,
            $params,
        )->queryAll();

        $rows = array_map(
            function (array $row): array {
                $row['legacyUrl'] = $this->legacyUrl(
                    'lantikan/lantik-semula-staf-ums?id=' . rawurlencode((string) ($row['ICNO'] ?? '')),
                );

                return $row;
            },
            array_values(array_filter($rows, 'is_array')),
        );

        return [
            'filters' => $filters,
            'options' => $this->references->options(),
            'rows' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function incompleteForIdentity(string $identityId): ?array
    {
        $icno = $this->employees->icnoFromIdentity($identityId);
        if (!$this->canAccess($icno)) {
            return null;
        }

        $rows = $this->db->createCommand(
            <<<SQL
            SELECT lbs.*, bio.CONm, bio.COOldID
            FROM hronline.tbl_lantikan_belum_selesai lbs
            LEFT JOIN hronline.tblprcobiodata bio ON bio.ICNO = lbs.ICNO
            ORDER BY lbs.ICNO
            LIMIT 100
            SQL,
        )->queryAll();

        return ['rows' => array_values(array_filter($rows, 'is_array'))];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function verificationForIdentity(string $identityId, string $icno): ?array
    {
        $currentIcno = $this->employees->icnoFromIdentity($identityId);
        if (!$this->canAccess($currentIcno) || $icno === '') {
            return null;
        }

        $staff = $this->employees->byIcno($icno);
        if ($staff === null) {
            return null;
        }

        $tables = [
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
                'exists' => (bool) $this->db->createCommand(
                    "SELECT 1 FROM {$table} WHERE ICNO = :icno LIMIT 1",
                    [':icno' => $icno],
                )->queryScalar(),
            ];
        }

        return [
            'staff' => $staff,
            'checks' => $checks,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function stafBaruResultForIdentity(string $identityId, string $icno): ?array
    {
        $currentIcno = $this->employees->icnoFromIdentity($identityId);
        if (!$this->canAccess($currentIcno) || $icno === '') {
            return null;
        }

        return $this->stafBaruTransactionService->reconciliation($icno);
    }

    public function legacyUrl(string $route): string
    {
        return LegacyUrl::to($route);
    }

    /**
     * @return array<string, mixed>
     */
    private function tile(string $label, string $description, string $icon, string $legacyRoute, bool $legacy, string $localUrl = '', bool $enabled = true): array
    {
        return [
            'label' => $label,
            'description' => $description,
            'icon' => $icon,
            'url' => $enabled ? ($localUrl !== '' ? $localUrl : $this->legacyUrl($legacyRoute)) : '',
            'legacy' => $legacy,
            'enabled' => $enabled,
        ];
    }

    /**
     * @param list<string> $where
     * @param array<string, string> $params
     * @param array<string, mixed> $filters
     */
    private function applyFilter(array &$where, array &$params, string $column, string $key, array $filters): void
    {
        $value = trim((string) ($filters[$key] ?? ''));
        if ($value === '') {
            return;
        }

        $param = ':' . $key;
        $where[] = "{$column} = {$param}";
        $params[$param] = $value;
    }

    /**
     * @param list<string> $where
     */
    private function whereSql(array $where): string
    {
        return implode(' AND ', $where === [] ? ['1=1'] : $where);
    }

    private function legacyRouteForFlow(string $flow): string
    {
        return match ($flow) {
            'phs' => 'lantikan/lantik-phs',
            'khas' => 'lantikan/lantik-khas',
            'spdp' => 'lantikan/lantik-spdp',
            default => 'lantikan/lantik-staf-baru',
        };
    }
}
