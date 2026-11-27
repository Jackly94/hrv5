<?php

declare(strict_types=1);

namespace App\Shared\PengesahanDataStaf;

use App\Shared\EmployeeRepository;
use App\Shared\MediaHostFileService;
use App\Shared\StaffDateFormatter;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class KeahlianVerificationService
{
    private const TABLE = 'hronline.tblprprofassoc';
    private const PAGE_SIZE = 15;

    public function __construct(
        private ConnectionInterface $db,
        private EmployeeRepository $employees,
        private MediaHostFileService $fileService,
        private StaffDateFormatter $dateFormatter,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function queuePage(string $searchType = '', string $search = '', int $page = 1, string $signal = ''): array
    {
        return [
            'title' => 'Pengesahan Rekod Keahlian',
            'backUrl' => '/pengesahan-data-staf/index',
            'searchAction' => '/pengesahan-data-staf/keahlian',
            'verifyAction' => '/pengesahan-data-staf/keahlian/verify',
            'searchType' => $this->searchType($searchType),
            'search' => trim($search),
            'signal' => $signal,
            'queue' => $this->pendingStaffGroups($searchType, $search, $page),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function staffPage(string $icno, int $page = 1, string $signal = ''): ?array
    {
        $staff = $this->staffContext($icno);
        if ($staff === null) {
            return null;
        }

        return [
            'title' => 'Pengesahan Rekod Keahlian',
            'backUrl' => '/pengesahan-data-staf/keahlian',
            'verifyAction' => '/pengesahan-data-staf/keahlian/verify',
            'signal' => $signal,
            'staff' => $staff,
            'records' => $this->pendingForStaff($icno, $page),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function pendingStaffGroups(string $searchType = '', string $search = '', int $page = 1): array
    {
        $pagination = $this->pagination('staff-groups', $searchType, $search, '', $page);

        return [
            'rows' => array_map(function (array $row): array {
                $icno = $this->value($row['ICNO'] ?? null, '');

                return [
                    'icno' => $icno,
                    'cells' => [
                        $icno,
                        $this->employees->displayName($row['staff_name'] ?? null),
                        $this->value($row['department_name'] ?? null),
                        (string) (int) ($row['pending_count'] ?? 0),
                    ],
                    'actionUrl' => $icno === '' ? '' : '/pengesahan-data-staf/keahlian/staf?icno=' . rawurlencode($icno),
                ];
            }, $this->queryStaffGroups($searchType, $search, self::PAGE_SIZE, $pagination['offset'])),
            'pageParam' => 'page',
            ...$pagination,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function pendingForStaff(string $icno, int $page = 1): array
    {
        $pagination = $this->pagination('staff-records', '', '', $icno, $page);

        return [
            'rows' => array_map(function (array $row) use ($icno): array {
                $id = $this->value($row['profId'] ?? null, '');
                $file = $this->value($row['filename'] ?? null, '');

                return [
                    'id' => $id,
                    'cells' => [
                        $this->professionalBody($row),
                        $this->value($row['MembershipType'] ?? null, '-'),
                        $this->value($row['LvlNm'] ?? null, '-'),
                        $this->value($row['membership_no'] ?? null, 'Sila Kemaskini'),
                        $this->value($row['Designation'] ?? null, '-'),
                        $this->date($row['JoinDt'] ?? null),
                        $this->activeStatus($row['ProfAssocStatus'] ?? null),
                    ],
                    'fileUrl' => $this->fileService->viewUrl($file),
                    'detailUrl' => $id === ''
                        ? ''
                        : '/biodata/keahlian/lihatbadanprofesional?id=' . rawurlencode($id) . '&icno=' . rawurlencode($icno),
                ];
            }, $this->queryStaffRecords($icno, self::PAGE_SIZE, $pagination['offset'])),
            'pageParam' => 'page',
            ...$pagination,
        ];
    }

    /**
     * @return array<string, string>|null
     */
    public function staffContext(string $icno): ?array
    {
        $icno = trim($icno);
        if ($icno === '') {
            return null;
        }

        try {
            $row = $this->db->createCommand(
                <<<SQL
                SELECT bio.ICNO, bio.CONm, bio.COOldID,
                       dept.fullname AS department_name,
                       service.ServStatusNm
                FROM hronline.tblprcobiodata bio
                LEFT JOIN hronline.department dept ON dept.id = bio.DeptId
                LEFT JOIN hronline.servicestatus service ON service.ServStatusCd = bio.Status
                WHERE bio.ICNO = :icno
                LIMIT 1
                SQL,
                [':icno' => $icno],
            )->queryOne();
        } catch (\Throwable) {
            return null;
        }

        if (!is_array($row)) {
            return null;
        }

        return [
            'icno' => $this->value($row['ICNO'] ?? null, $icno),
            'name' => $this->employees->displayName($row['CONm'] ?? null),
            'staffNo' => $this->value($row['COOldID'] ?? null),
            'department' => $this->value($row['department_name'] ?? null),
            'serviceStatus' => $this->value($row['ServStatusNm'] ?? null),
        ];
    }

    /**
     * @param array<mixed> $icnos
     * @return array{success: bool, message: string}
     */
    public function verifyStaffGroups(array $icnos, string $actorIcno): array
    {
        $icnos = $this->normalizeList($icnos);
        if ($icnos === []) {
            return ['success' => false, 'message' => 'Sila pilih rekod untuk disahkan.'];
        }

        $now = date('Y-m-d H:i:s');

        try {
            $updated = $this->db->transaction(function () use ($icnos, $actorIcno, $now): int {
                foreach ($icnos as $icno) {
                    if ($this->countUnverifiedForStaff($icno) < 1) {
                        throw new \RuntimeException('Rekod telah diambil tindakan atau tidak dijumpai.');
                    }
                }

                $updated = 0;
                foreach ($icnos as $icno) {
                    $updated += $this->db->createCommand(
                        <<<SQL
                        UPDATE hronline.tblprprofassoc
                        SET isVerified = 1, verify_date = :verifiedAt, verify_icno = :actorIcno
                        WHERE ICNO = :icno
                          AND (isVerified IS NULL OR isVerified != 1)
                        SQL,
                        [
                            ':verifiedAt' => $now,
                            ':actorIcno' => $actorIcno,
                            ':icno' => $icno,
                        ],
                    )->execute();
                }

                return $updated;
            });
        } catch (\Throwable) {
            return ['success' => false, 'message' => 'Pengesahan keahlian tidak berjaya disimpan.'];
        }

        if ($updated < 1) {
            return ['success' => false, 'message' => 'Rekod telah diambil tindakan atau tidak dijumpai.'];
        }

        return ['success' => true, 'message' => 'Keahlian berjaya disahkan.'];
    }

    /**
     * @param array<mixed> $ids
     * @return array{success: bool, message: string}
     */
    public function verifyRowsForStaff(string $icno, array $ids, string $actorIcno): array
    {
        $icno = trim($icno);
        $ids = $this->normalizeList($ids);
        if ($icno === '' || $ids === []) {
            return ['success' => false, 'message' => 'Sila pilih rekod untuk disahkan.'];
        }

        $now = date('Y-m-d H:i:s');

        try {
            $updated = $this->db->transaction(function () use ($icno, $ids, $actorIcno, $now): int {
                if ($this->countUnverifiedRowsForStaff($icno, $ids) !== count($ids)) {
                    throw new \RuntimeException('Rekod telah diambil tindakan atau tidak dijumpai.');
                }

                [$idSql, $params] = $this->inParams('id', $ids);
                $params[':verifiedAt'] = $now;
                $params[':actorIcno'] = $actorIcno;
                $params[':icno'] = $icno;

                return $this->db->createCommand(
                    <<<SQL
                    UPDATE hronline.tblprprofassoc
                    SET isVerified = 1, verify_date = :verifiedAt, verify_icno = :actorIcno
                    WHERE ICNO = :icno
                      AND profId IN ($idSql)
                      AND (isVerified IS NULL OR isVerified != 1)
                    SQL,
                    $params,
                )->execute();
            });
        } catch (\Throwable) {
            return ['success' => false, 'message' => 'Pengesahan keahlian tidak berjaya disimpan.'];
        }

        if ($updated < 1) {
            return ['success' => false, 'message' => 'Rekod telah diambil tindakan atau tidak dijumpai.'];
        }

        return ['success' => true, 'message' => 'Keahlian berjaya disahkan.'];
    }

    private function countUnverifiedForStaff(string $icno): int
    {
        return (int) $this->db->createCommand(
            'SELECT COUNT(*) FROM ' . self::TABLE . ' WHERE ICNO = :icno AND (isVerified IS NULL OR isVerified != 1)',
            [':icno' => $icno],
        )->queryScalar();
    }

    /**
     * @param list<string> $ids
     */
    private function countUnverifiedRowsForStaff(string $icno, array $ids): int
    {
        [$idSql, $params] = $this->inParams('id', $ids);
        $params[':icno'] = $icno;

        return (int) $this->db->createCommand(
            'SELECT COUNT(*) FROM ' . self::TABLE . " WHERE ICNO = :icno AND profId IN ($idSql) AND (isVerified IS NULL OR isVerified != 1)",
            $params,
        )->queryScalar();
    }

    /**
     * @return array{total: int, page: int, pageCount: int, offset: int, pageSize: int}
     */
    private function pagination(string $scope, string $searchType, string $search, string $icno, int $page): array
    {
        $total = $scope === 'staff-records'
            ? $this->countStaffRecords($icno)
            : $this->countStaffGroups($searchType, $search);
        $pageCount = max(1, (int) ceil($total / self::PAGE_SIZE));
        $page = min(max(1, $page), $pageCount);

        return [
            'total' => $total,
            'page' => $page,
            'pageCount' => $pageCount,
            'offset' => ($page - 1) * self::PAGE_SIZE,
            'pageSize' => self::PAGE_SIZE,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function queryStaffGroups(string $searchType, string $search, int $limit, int $offset): array
    {
        [$where, $params] = $this->staffGroupWhere($searchType, $search);
        $limit = max(1, $limit);
        $offset = max(0, $offset);

        try {
            $rows = $this->db->createCommand(
                <<<SQL
                SELECT assoc.ICNO,
                       staff.CONm AS staff_name,
                       staff.COOldID AS staff_no,
                       dept.fullname AS department_name,
                       COUNT(*) AS pending_count
                FROM hronline.tblprprofassoc assoc
                LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = assoc.ICNO
                LEFT JOIN hronline.department dept ON dept.id = staff.DeptId
                WHERE $where
                GROUP BY assoc.ICNO, staff.CONm, staff.COOldID, dept.fullname
                ORDER BY staff.CONm ASC, assoc.ICNO ASC
                LIMIT $limit OFFSET $offset
                SQL,
                $params,
            )->queryAll();
        } catch (\Throwable) {
            return [];
        }

        return array_values(array_filter($rows, 'is_array'));
    }

    private function countStaffGroups(string $searchType, string $search): int
    {
        [$where, $params] = $this->staffGroupWhere($searchType, $search);

        try {
            return (int) $this->db->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM (
                    SELECT assoc.ICNO
                    FROM hronline.tblprprofassoc assoc
                    LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = assoc.ICNO
                    WHERE $where
                    GROUP BY assoc.ICNO
                ) pending_staff
                SQL,
                $params,
            )->queryScalar();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function staffGroupWhere(string $searchType, string $search): array
    {
        $where = '(assoc.isVerified IS NULL OR assoc.isVerified != 1)';
        $params = [];
        $search = trim($search);

        if ($search !== '') {
            if ($this->searchType($searchType) === '2') {
                $where .= ' AND staff.CONm LIKE :search';
                $params[':search'] = '%' . $search . '%';
            } else {
                $where .= ' AND assoc.ICNO = :search';
                $params[':search'] = $search;
            }
        }

        return [$where, $params];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function queryStaffRecords(string $icno, int $limit, int $offset): array
    {
        $limit = max(1, $limit);
        $offset = max(0, $offset);

        try {
            $rows = $this->db->createCommand(
                <<<SQL
                SELECT assoc.*,
                       body.ProfBody,
                       membership.MembershipType,
                       assoc_level.LvlNm
                FROM hronline.tblprprofassoc assoc
                LEFT JOIN hronline.professionalassociation body ON body.ProfBodyCd = assoc.ProfBodyCd
                LEFT JOIN hronline.membershiptype membership ON membership.MembershipTypeCd = assoc.MembershipTypeCd
                LEFT JOIN hronline.professional_association_level assoc_level ON assoc_level.id = assoc.ProfAssocLvl
                WHERE assoc.ICNO = :icno
                  AND (assoc.isVerified IS NULL OR assoc.isVerified != 1)
                ORDER BY assoc.JoinDt DESC, assoc.profId DESC
                LIMIT $limit OFFSET $offset
                SQL,
                [':icno' => $icno],
            )->queryAll();
        } catch (\Throwable) {
            return [];
        }

        return array_values(array_filter($rows, 'is_array'));
    }

    private function countStaffRecords(string $icno): int
    {
        try {
            return (int) $this->db->createCommand(
                'SELECT COUNT(*) FROM ' . self::TABLE . ' WHERE ICNO = :icno AND (isVerified IS NULL OR isVerified != 1)',
                [':icno' => $icno],
            )->queryScalar();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * @param array<mixed> $values
     * @return list<string>
     */
    private function normalizeList(array $values): array
    {
        $normalized = [];
        foreach ($values as $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $value = trim((string) $value);
            if ($value !== '') {
                $normalized[$value] = $value;
            }
        }

        return array_values($normalized);
    }

    /**
     * @param list<string> $values
     * @return array{0: string, 1: array<string, string>}
     */
    private function inParams(string $prefix, array $values): array
    {
        $placeholders = [];
        $params = [];
        foreach (array_values($values) as $index => $value) {
            $placeholder = ':' . $prefix . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $value;
        }

        return [implode(', ', $placeholders), $params];
    }

    private function searchType(string $searchType): string
    {
        return $searchType === '2' ? '2' : '1';
    }

    /**
     * @param array<string, mixed> $row
     */
    private function professionalBody(array $row): string
    {
        if ((string) ($row['ProfBodyCd'] ?? '') === '9999') {
            return $this->value($row['ProfBodyOther'] ?? null, '-');
        }

        return $this->value($row['ProfBody'] ?? null, '-');
    }

    private function activeStatus(mixed $status): string
    {
        $status = $this->value($status, '');

        return $status !== '' && $status !== '0' ? 'Aktif' : 'Tidak Aktif';
    }

    private function date(mixed $value): string
    {
        return $this->dateFormatter->display($value, '-');
    }

    private function value(mixed $value, string $fallback = 'Tiada Rekod'): string
    {
        if ($value === null) {
            return $fallback;
        }

        $value = trim((string) $value);

        return $value === '' ? $fallback : $value;
    }
}
