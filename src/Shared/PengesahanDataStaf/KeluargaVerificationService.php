<?php

declare(strict_types=1);

namespace App\Shared\PengesahanDataStaf;

use App\Shared\EmployeeRepository;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class KeluargaVerificationService
{
    private const PAGE_SIZE = 15;

    public function __construct(
        private ConnectionInterface $db,
        private EmployeeRepository $employees,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function queuePage(string $mode, string $searchType = '', string $search = '', int $page = 1, string $signal = ''): array
    {
        $mode = $this->mode($mode);
        $isRelation = $mode === 'relation';

        return [
            'mode' => $mode,
            'title' => $isRelation ? 'Pengesahan Rekod Hubungan Keluarga' : 'Pengesahan Rekod Keluarga OKU',
            'subtitle' => 'Pengesahan Data Staf',
            'backUrl' => '/pengesahan-data-staf/index',
            'searchAction' => $isRelation ? '/pengesahan-data-staf/hubungan-keluarga' : '/pengesahan-data-staf/keluarga',
            'verifyAction' => $isRelation ? '/pengesahan-data-staf/hubungan-keluarga/verify' : '/pengesahan-data-staf/keluarga/verify',
            'searchType' => $this->searchType($searchType),
            'search' => trim($search),
            'signal' => $signal,
            'columns' => $isRelation
                ? ['No. KP / Paspot', 'Nama Kakitangan', 'No. KP / Paspot Keluarga', 'Nama Keluarga', 'Hubungan']
                : ['No. KP / Paspot', 'Nama Kakitangan', 'No. KP / Paspot Keluarga', 'Nama Keluarga', 'Hubungan', 'Status OKU'],
            'queue' => $this->pendingRows($mode, $searchType, $search, $page),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function pendingRows(string $mode, string $searchType = '', string $search = '', int $page = 1): array
    {
        $mode = $this->mode($mode);
        $pagination = $this->pagination($mode, $searchType, $search, $page);

        return [
            'rows' => array_map(function (array $row) use ($mode): array {
                $staffIcno = $this->value($row['ICNO'] ?? null, '-');
                $familyRecordId = $this->value($row['family_id'] ?? null, '');
                $selectionId = $mode === 'relation'
                    ? $this->value($row['pds_id'] ?? null, '')
                    : $familyRecordId;

                $cells = [
                    $staffIcno,
                    $this->employees->displayName($row['staff_name'] ?? null),
                    $this->value($row['FamilyId'] ?? null, '-'),
                    $this->value($row['FmyNm'] ?? null, '-'),
                    $this->value($row['RelNm'] ?? null, '-'),
                ];

                if ($mode === 'oku') {
                    $cells[] = ((string) ($row['FmyDisabilityStatus'] ?? '') === '1') ? 'YA' : 'TIDAK';
                }

                return [
                    'id' => $selectionId,
                    'cells' => $cells,
                    'detailUrl' => $familyRecordId === ''
                        ? ''
                        : '/biodata/keluarga/lihatkeluarga?id=' . rawurlencode($familyRecordId) . '&icno=' . rawurlencode($staffIcno),
                ];
            }, $this->queryRows($mode, $searchType, $search, self::PAGE_SIZE, $pagination['offset'])),
            'pageParam' => 'page',
            ...$pagination,
        ];
    }

    /**
     * @param array<mixed> $ids
     * @return array{success: bool, message: string}
     */
    public function verify(string $mode, array $ids, string $action, string $remarks): array
    {
        $mode = $this->mode($mode);
        $ids = $this->normalizeList($ids);
        $action = $action === 'accept' ? 'accept' : ($action === 'reject' ? 'reject' : '');
        $remarks = trim($remarks);

        if ($action === '') {
            return ['success' => false, 'message' => 'Tindakan tidak sah.'];
        }

        if ($ids === []) {
            return ['success' => false, 'message' => 'Sila pilih rekod untuk disahkan.'];
        }

        if ($remarks === '') {
            return ['success' => false, 'message' => 'Catatan diperlukan.'];
        }

        $status = $action === 'accept' ? 1 : 2;

        try {
            $updated = $this->db->transaction(function () use ($mode, $ids, $status, $remarks): int {
                return $mode === 'relation'
                    ? $this->updateRelationRows($ids, $status, $remarks)
                    : $this->updateOkuRows($ids, $status, $remarks);
            });
        } catch (\Throwable) {
            return ['success' => false, 'message' => 'Pengesahan keluarga tidak berjaya disimpan.'];
        }

        if ($updated < 1) {
            return ['success' => false, 'message' => 'Rekod telah diambil tindakan atau tidak dijumpai.'];
        }

        return [
            'success' => true,
            'message' => $action === 'accept' ? 'Rekod berjaya disahkan.' : 'Rekod berjaya ditolak.',
        ];
    }

    /**
     * @param list<string> $ids
     */
    private function updateOkuRows(array $ids, int $status, string $remarks): int
    {
        if ($this->countOkuRows($ids) !== count($ids)) {
            throw new \RuntimeException('Invalid selected rows.');
        }

        [$idSql, $params] = $this->inParams('id', $ids);
        $params[':status'] = (string) $status;
        $params[':remarks'] = $remarks;

        return $this->db->createCommand(
            <<<SQL
            UPDATE hronline.tblprfmy
            SET status_pengesahan = :status,
                catatan = :remarks
            WHERE id IN ($idSql)
              AND FmyDisabilityStatus = 1
              AND (status_pengesahan IS NULL OR status_pengesahan = 0)
            SQL,
            $params,
        )->execute();
    }

    /**
     * @param list<string> $ids
     */
    private function updateRelationRows(array $ids, int $status, string $remarks): int
    {
        if ($this->countRelationRows($ids) !== count($ids)) {
            throw new \RuntimeException('Invalid selected rows.');
        }

        [$idSql, $params] = $this->inParams('id', $ids);
        $params[':status'] = (string) $status;
        $params[':remarks'] = $remarks;

        return $this->db->createCommand(
            <<<SQL
            UPDATE hronline.pdstblkeluarga
            SET verification_status = :status,
                verification_remark = :remarks
            WHERE id IN ($idSql)
              AND verification_type = 1
              AND verification_status != 1
            SQL,
            $params,
        )->execute();
    }

    /**
     * @param list<string> $ids
     */
    private function countOkuRows(array $ids): int
    {
        [$idSql, $params] = $this->inParams('id', $ids);

        return (int) $this->db->createCommand(
            "SELECT COUNT(*) FROM hronline.tblprfmy WHERE id IN ($idSql) AND FmyDisabilityStatus = 1 AND (status_pengesahan IS NULL OR status_pengesahan = 0)",
            $params,
        )->queryScalar();
    }

    /**
     * @param list<string> $ids
     */
    private function countRelationRows(array $ids): int
    {
        [$idSql, $params] = $this->inParams('id', $ids);

        return (int) $this->db->createCommand(
            "SELECT COUNT(*) FROM hronline.pdstblkeluarga WHERE id IN ($idSql) AND verification_type = 1 AND verification_status != 1",
            $params,
        )->queryScalar();
    }

    /**
     * @return array{total: int, page: int, pageCount: int, offset: int, pageSize: int}
     */
    private function pagination(string $mode, string $searchType, string $search, int $page): array
    {
        $total = $this->countRows($mode, $searchType, $search);
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
    private function queryRows(string $mode, string $searchType, string $search, int $limit, int $offset): array
    {
        [$where, $params] = $this->whereAndParams($mode, $searchType, $search);
        $limit = max(1, $limit);
        $offset = max(0, $offset);

        try {
            $rows = $this->db->createCommand(
                $this->selectSql($mode, $where) . " ORDER BY staff.CONm ASC, family.FmyNm ASC LIMIT $limit OFFSET $offset",
                $params,
            )->queryAll();
        } catch (\Throwable) {
            return [];
        }

        return array_values(array_filter($rows, 'is_array'));
    }

    private function countRows(string $mode, string $searchType, string $search): int
    {
        [$where, $params] = $this->whereAndParams($mode, $searchType, $search);

        try {
            return (int) $this->db->createCommand('SELECT COUNT(*) FROM (' . $this->selectSql($mode, $where) . ') family_pending', $params)->queryScalar();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function whereAndParams(string $mode, string $searchType, string $search): array
    {
        $where = $mode === 'relation'
            ? 'pds.verification_type = 1 AND pds.verification_status != 1'
            : 'family.FmyDisabilityStatus = 1 AND (family.status_pengesahan IS NULL OR family.status_pengesahan = 0)';
        $params = [];
        $search = trim($search);

        if ($search !== '') {
            if ($this->searchType($searchType) === '2') {
                $where .= ' AND family.FmyNm LIKE :search';
                $params[':search'] = '%' . $search . '%';
            } else {
                $where .= ' AND (family.ICNO = :search OR family.FamilyId = :search)';
                $params[':search'] = $search;
            }
        }

        return [$where, $params];
    }

    private function selectSql(string $mode, string $where): string
    {
        $selectPrefix = $mode === 'relation'
            ? 'SELECT pds.id AS pds_id, pds.verification_status, pds.verification_remark, family.id AS family_id,'
            : 'SELECT family.id AS family_id,';
        $from = $mode === 'relation'
            ? 'FROM hronline.pdstblkeluarga pds INNER JOIN hronline.tblprfmy family ON family.id = pds.tblfmy_id'
            : 'FROM hronline.tblprfmy family';

        return <<<SQL
            $selectPrefix
                   family.ICNO,
                   family.FamilyId,
                   family.FmyNm,
                   family.FmyDisabilityStatus,
                   staff.CONm AS staff_name,
                   relationship.RelNm
            $from
            LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = family.ICNO
            LEFT JOIN hronline.relationship relationship ON relationship.RelCd = family.RelCd
            WHERE $where
            SQL;
    }

    private function mode(string $mode): string
    {
        return $mode === 'relation' ? 'relation' : 'oku';
    }

    private function searchType(string $searchType): string
    {
        return $searchType === '2' ? '2' : '1';
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

    private function value(mixed $value, string $fallback = 'Tiada Rekod'): string
    {
        if ($value === null) {
            return $fallback;
        }

        $value = trim((string) $value);

        return $value === '' ? $fallback : $value;
    }
}
