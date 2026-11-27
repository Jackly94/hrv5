<?php

declare(strict_types=1);

namespace App\Shared\GambarProfile;

use App\Shared\EmployeeRepository;
use App\Shared\StaffDateFormatter;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class ProfilePictureAdminAccessService
{
    private const TABLE = 'keselamatan.utils_tbl_gambar_admin';
    private const PAGE_SIZE = 15;

    public function __construct(
        private ConnectionInterface $db,
        private EmployeeRepository $employees,
        private StaffDateFormatter $dateFormatter,
    ) {}

    public function isBpsmAdmin(string $icno): bool
    {
        return $this->hasAccess($icno, [1]);
    }

    public function isCounterAdmin(string $icno): bool
    {
        return $this->hasAccess($icno, [1, 2]);
    }

    public function activeAccessType(string $icno): int
    {
        if ($icno === '') {
            return 0;
        }

        try {
            $value = $this->db->createCommand(
                sprintf(
                    'SELECT access_type FROM %s WHERE icno = :icno AND is_active = 1 AND access_type IN (1, 2) ORDER BY access_type ASC LIMIT 1',
                    self::TABLE,
                ),
                [':icno' => $icno],
            )->queryScalar();
        } catch (\Throwable) {
            return 0;
        }

        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function managementList(string $currentIcno, string $search = '', int $page = 1): array
    {
        $accessType = $this->activeAccessType($currentIcno);
        $result = $this->adminRows($accessType, $search, $page);

        return [
            'title' => 'Senarai Admin Gambar Profil',
            'sectionTitle' => 'Senarai Admin',
            'currentUrl' => '/gambar-profile/senarai-admin',
            'createUrl' => '/gambar-profile/tambah-admin',
            'searchAction' => '/gambar-profile/senarai-admin',
            'search' => trim($search),
            'total' => $result['total'],
            'page' => $result['page'],
            'pageCount' => $result['pageCount'],
            'pageSize' => self::PAGE_SIZE,
            'offset' => $result['offset'],
            'columns' => ['Nama', 'Jenis Akses', 'Status', 'Tarikh Didaftarkan', 'Didaftarkan Oleh'],
            'rows' => array_map(fn (array $row): array => $this->adminListRow($row), $result['rows']),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function adminForm(string $currentIcno, ?string $id = null): ?array
    {
        $currentType = $this->activeAccessType($currentIcno);
        if ($currentType === 0) {
            return null;
        }

        $row = null;
        $isUpdate = $id !== null && $id !== '';
        if ($isUpdate) {
            $row = $this->adminRecord($id, $currentType);
            if ($row === null) {
                return null;
            }
        }

        $accessType = $isUpdate ? (int) ($row['access_type'] ?? $currentType) : $currentType;
        if ($currentType !== 1) {
            $accessType = $currentType;
        }

        return [
            'title' => $isUpdate ? 'Kemaskini Admin' : 'Tambah Admin',
            'sectionTitle' => $isUpdate ? 'Kemaskini Admin Gambar Profil' : 'Tambah Admin Gambar Profil',
            'action' => $isUpdate ? '/gambar-profile/kemaskini-admin?id=' . rawurlencode((string) $id) : '/gambar-profile/tambah-admin',
            'backUrl' => '/gambar-profile/senarai-admin',
            'isUpdate' => $isUpdate,
            'staffOptions' => $this->staffOptions($this->value($row['icno'] ?? null, '')),
            'accessOptions' => $this->accessOptions($currentType),
            'statusOptions' => [
                '1' => 'Aktif',
                '0' => 'Tidak Aktif',
            ],
            'values' => [
                'icno' => $this->value($row['icno'] ?? null, ''),
                'access_type' => (string) $accessType,
                'is_active' => $isUpdate ? (string) (int) ($row['is_active'] ?? 1) : '1',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, message: string, errors: array<string, string>, redirect: string}
     */
    public function saveAdmin(string $currentIcno, ?string $id, array $input): array
    {
        $currentType = $this->activeAccessType($currentIcno);
        if ($currentType === 0) {
            return [
                'success' => false,
                'message' => 'Akses admin tidak sah.',
                'errors' => ['general' => 'Akses admin tidak sah.'],
                'redirect' => '/gambar-profile/senarai-admin',
            ];
        }

        $isUpdate = $id !== null && $id !== '';
        $existing = $isUpdate ? $this->adminRecord((string) $id, $currentType) : null;
        if ($isUpdate && $existing === null) {
            return [
                'success' => false,
                'message' => 'Rekod admin tidak dijumpai.',
                'errors' => ['general' => 'Rekod admin tidak dijumpai.'],
                'redirect' => '/gambar-profile/senarai-admin',
            ];
        }

        $icno = $this->input($input, 'icno');
        $accessType = (int) $this->input($input, 'access_type');
        $isActive = $this->input($input, 'is_active');
        if ($currentType !== 1) {
            $accessType = $currentType;
        }

        $errors = [];
        if ($icno === '') {
            $errors['icno'] = 'Sila pilih admin.';
        } elseif (!$this->activeStaffExists($icno)) {
            $errors['icno'] = 'Rekod staf aktif tidak dijumpai.';
        }

        if (!array_key_exists((string) $accessType, $this->accessOptions($currentType))) {
            $errors['access_type'] = 'Jenis akses tidak sah.';
        }

        if (!in_array($isActive, ['0', '1'], true)) {
            $errors['is_active'] = 'Status tidak sah.';
        }

        if ($icno !== '' && $accessType > 0 && $this->duplicateExists($icno, $accessType, $id)) {
            $errors['icno'] = 'Admin ini telah didaftarkan untuk jenis akses yang sama.';
        }

        if ($errors !== []) {
            return [
                'success' => false,
                'message' => 'Sila semak maklumat admin.',
                'errors' => $errors,
                'redirect' => $isUpdate ? '/gambar-profile/kemaskini-admin?id=' . rawurlencode((string) $id) : '/gambar-profile/tambah-admin',
            ];
        }

        $record = [
            'icno' => $icno,
            'access_type' => $accessType,
            'is_active' => (int) $isActive,
            'add_by' => $currentIcno,
            'add_date' => date('Y-m-d'),
        ];

        try {
            if ($isUpdate) {
                $this->db->createCommand()->update(self::TABLE, $record, ['id' => $id])->execute();
            } else {
                $this->db->createCommand()->insert(self::TABLE, $record)->execute();
            }
        } catch (\Throwable) {
            return [
                'success' => false,
                'message' => $isUpdate ? 'Admin tidak berjaya dikemaskini.' : 'Admin tidak berjaya didaftarkan.',
                'errors' => ['general' => 'Terdapat masalah teknikal. Sila berhubung dengan Teknikal IT.'],
                'redirect' => $isUpdate ? '/gambar-profile/kemaskini-admin?id=' . rawurlencode((string) $id) : '/gambar-profile/tambah-admin',
            ];
        }

        return [
            'success' => true,
            'message' => $isUpdate ? 'Admin berjaya dikemaskini.' : 'Admin berjaya didaftarkan.',
            'errors' => [],
            'redirect' => '/gambar-profile/senarai-admin',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function adminDetail(string $currentIcno, string $id): ?array
    {
        $currentType = $this->activeAccessType($currentIcno);
        if ($currentType === 0 || $id === '') {
            return null;
        }

        $row = $this->adminRecord($id, $currentType);
        if ($row === null) {
            return null;
        }

        return [
            'title' => 'Maklumat Admin',
            'name' => $this->employees->displayName($row['staff_name'] ?? null),
            'backUrl' => '/gambar-profile/senarai-admin',
            'editUrl' => '/gambar-profile/kemaskini-admin?id=' . rawurlencode($this->value($row['id'] ?? null, '')),
            'fields' => [
                ['label' => 'ICNO', 'value' => $this->value($row['icno'] ?? null, 'Tiada Rekod')],
                ['label' => 'Nama', 'value' => $this->employees->displayName($row['staff_name'] ?? null)],
                ['label' => 'Jenis Akses', 'value' => $this->accessLabel($row['access_type'] ?? null)],
                ['label' => 'Status', 'value' => $this->activeLabel($row['is_active'] ?? null), 'kind' => 'status'],
                ['label' => 'Didaftarkan Oleh', 'value' => $this->employees->displayName($row['added_by_name'] ?? null)],
                ['label' => 'Didaftarkan Pada', 'value' => $this->date($row['add_date'] ?? null)],
            ],
        ];
    }

    /**
     * @param list<int> $accessTypes
     */
    private function hasAccess(string $icno, array $accessTypes): bool
    {
        if ($icno === '' || $accessTypes === []) {
            return false;
        }

        $placeholders = [];
        $params = [':icno' => $icno];
        foreach ($accessTypes as $index => $accessType) {
            $placeholder = ':access' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $accessType;
        }

        try {
            return (bool) $this->db->createCommand(
                sprintf(
                    'SELECT 1 FROM %s WHERE icno = :icno AND is_active = 1 AND access_type IN (%s) LIMIT 1',
                    self::TABLE,
                    implode(', ', $placeholders),
                ),
                $params,
            )->queryScalar();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total: int, page: int, pageCount: int, offset: int}
     */
    private function adminRows(int $accessType, string $search, int $page): array
    {
        if ($accessType === 0) {
            return ['rows' => [], 'total' => 0, 'page' => 1, 'pageCount' => 1, 'offset' => 0];
        }

        $params = [];
        $accessCondition = '';
        if ($accessType !== 1) {
            $accessCondition = 'AND admin.access_type = :accessType';
            $params[':accessType'] = $accessType;
        }

        $search = trim($search);
        $searchCondition = '';
        if ($search !== '') {
            $params[':searchName'] = '%' . $search . '%';
            $params[':searchIcno'] = '%' . $search . '%';
            $params[':searchAddedBy'] = '%' . $search . '%';
            $searchCondition = <<<SQL
              AND (
                    staff.CONm LIKE :searchName
                    OR admin.icno LIKE :searchIcno
                    OR added.CONm LIKE :searchAddedBy
                  )
            SQL;
        }

        try {
            $total = (int) $this->db->createCommand(
                <<<SQL
                SELECT COUNT(*)
                FROM keselamatan.utils_tbl_gambar_admin admin
                LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = admin.icno
                LEFT JOIN hronline.tblprcobiodata added ON added.ICNO = admin.add_by
                WHERE 1 = 1
                  {$accessCondition}
                  {$searchCondition}
                SQL,
                $params,
            )->queryScalar();
            $pageCount = max(1, (int) ceil($total / self::PAGE_SIZE));
            $page = min(max(1, $page), $pageCount);
            $offset = ($page - 1) * self::PAGE_SIZE;
            $limit = self::PAGE_SIZE;
            $rows = $this->db->createCommand(
                <<<SQL
                SELECT admin.*,
                       staff.CONm AS staff_name,
                       added.CONm AS added_by_name
                FROM keselamatan.utils_tbl_gambar_admin admin
                LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = admin.icno
                LEFT JOIN hronline.tblprcobiodata added ON added.ICNO = admin.add_by
                WHERE 1 = 1
                  {$accessCondition}
                  {$searchCondition}
                ORDER BY admin.add_date DESC, admin.id DESC
                LIMIT {$limit} OFFSET {$offset}
                SQL,
                $params,
            )->queryAll();
        } catch (\Throwable) {
            return ['rows' => [], 'total' => 0, 'page' => 1, 'pageCount' => 1, 'offset' => 0];
        }

        return [
            'rows' => array_values(array_filter($rows, 'is_array')),
            'total' => $total,
            'page' => $page,
            'pageCount' => $pageCount,
            'offset' => $offset,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function adminListRow(array $row): array
    {
        $id = $this->value($row['id'] ?? null, '');

        return [
            'id' => $id,
            'cells' => [
                $this->employees->displayName($row['staff_name'] ?? null),
                $this->accessLabel($row['access_type'] ?? null),
                $this->activeLabel($row['is_active'] ?? null),
                $this->date($row['add_date'] ?? null),
                $this->employees->displayName($row['added_by_name'] ?? null),
            ],
            'status' => $this->activeLabel($row['is_active'] ?? null),
            'actionLabel' => 'Lihat',
            'actionIcon' => 'eye',
            'viewUrl' => $id === '' ? '' : '/gambar-profile/lihat-admin?id=' . rawurlencode($id),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function adminRecord(string $id, int $currentType): ?array
    {
        $params = [':id' => $id];
        $accessCondition = '';
        if ($currentType !== 1) {
            $accessCondition = 'AND admin.access_type = :accessType';
            $params[':accessType'] = $currentType;
        }

        try {
            $row = $this->db->createCommand(
                <<<SQL
                SELECT admin.*,
                       staff.CONm AS staff_name,
                       added.CONm AS added_by_name
                FROM keselamatan.utils_tbl_gambar_admin admin
                LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = admin.icno
                LEFT JOIN hronline.tblprcobiodata added ON added.ICNO = admin.add_by
                WHERE admin.id = :id
                  {$accessCondition}
                LIMIT 1
                SQL,
                $params,
            )->queryOne();
        } catch (\Throwable) {
            return null;
        }

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<string, string>
     */
    private function accessOptions(int $currentType): array
    {
        if ($currentType === 1) {
            return [
                '1' => 'Admin BPSM',
                '2' => 'Admin Keselamatan',
            ];
        }

        return ['2' => 'Admin Keselamatan'];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function staffOptions(string $selectedIcno): array
    {
        $params = [];
        $selectedCondition = '';
        if ($selectedIcno !== '') {
            $params[':selectedIcno'] = $selectedIcno;
            $selectedCondition = 'OR staff.ICNO = :selectedIcno';
        }

        try {
            $rows = $this->db->createCommand(
                <<<SQL
                SELECT staff.ICNO,
                       staff.CONm,
                       staff.COOldID
                FROM hronline.tblprcobiodata staff
                WHERE staff.Status = '1'
                  AND (staff.DeptId IN (2, 230) {$selectedCondition})
                ORDER BY staff.CONm ASC
                LIMIT 500
                SQL,
                $params,
            )->queryAll();
        } catch (\Throwable) {
            return [];
        }

        $options = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $icno = $this->value($row['ICNO'] ?? null, '');
            if ($icno === '') {
                continue;
            }

            $staffNumber = $this->value($row['COOldID'] ?? null, '');
            $label = $this->employees->displayName($row['CONm'] ?? null);
            if ($staffNumber !== '') {
                $label .= ' (' . $staffNumber . ')';
            }

            $options[] = ['value' => $icno, 'label' => $label];
        }

        return $options;
    }

    private function activeStaffExists(string $icno): bool
    {
        try {
            return (bool) $this->db->createCommand(
                "SELECT 1 FROM hronline.tblprcobiodata WHERE ICNO = :icno AND Status = '1' LIMIT 1",
                [':icno' => $icno],
            )->queryScalar();
        } catch (\Throwable) {
            return false;
        }
    }

    private function duplicateExists(string $icno, int $accessType, ?string $id): bool
    {
        $params = [
            ':icno' => $icno,
            ':accessType' => $accessType,
        ];
        $idCondition = '';
        if ($id !== null && $id !== '') {
            $params[':id'] = $id;
            $idCondition = 'AND id <> :id';
        }

        try {
            return (bool) $this->db->createCommand(
                sprintf(
                    'SELECT 1 FROM %s WHERE icno = :icno AND access_type = :accessType %s LIMIT 1',
                    self::TABLE,
                    $idCondition,
                ),
                $params,
            )->queryScalar();
        } catch (\Throwable) {
            return true;
        }
    }

    private function accessLabel(mixed $value): string
    {
        return ((string) $value) === '1' ? 'Admin BPSM' : 'Admin Keselamatan';
    }

    private function activeLabel(mixed $value): string
    {
        return ((string) $value) === '1' ? 'Aktif' : 'Tidak Aktif';
    }

    private function date(mixed $value): string
    {
        return $this->dateFormatter->display($value, '-');
    }

    private function value(mixed $value, string $fallback = ''): string
    {
        if ($value === null) {
            return $fallback;
        }

        $value = trim((string) $value);

        return $value === '' ? $fallback : $value;
    }

    /**
     * @param array<string, mixed> $input
     */
    private function input(array $input, string $key): string
    {
        return is_scalar($input[$key] ?? null) ? trim((string) $input[$key]) : '';
    }
}
