<?php

declare(strict_types=1);

namespace App\Shared\PengesahanDataStaf;

use App\Shared\MediaHostFileService;
use App\Shared\StaffDateFormatter;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class AkaunBankVerificationService
{
    private const APPLICATION_TABLE = 'hronline.tbl_akaun_permohonan_tukar';
    private const QUEUE_TABLE = 'hronline.tbl_akaun_queue_tukar';
    private const PAGE_SIZE = 15;

    public function __construct(
        private ConnectionInterface $db,
        private MediaHostFileService $fileService,
        private StaffDateFormatter $dateFormatter,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboard(): array
    {
        return [
            'title' => 'Pengesahan Penukaran Akaun Bank (Gaji) Kakitangan',
            'cards' => [
                [
                    'title' => 'Verification',
                    'subtitle' => 'Verification by P/O',
                    'count' => $this->countWhere('verify_status IS NULL'),
                    'url' => '/pengesahan-data-staf/akaun-bank/verification',
                    'icon' => 'clipboard-check',
                ],
                [
                    'title' => 'Approval',
                    'subtitle' => 'Approval by Officer',
                    'count' => $this->countWhere('verify_status = 1 AND approval_status IS NULL'),
                    'url' => '/pengesahan-data-staf/akaun-bank/approval',
                    'icon' => 'badge-check',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function verificationPage(string $search = '', int $pendingPage = 1, int $historyPage = 1): array
    {
        return [
            'mode' => 'verification',
            'title' => '[Pengesahan] Senarai Permohonan Penukaran Akaun Bank (Gaji)',
            'backUrl' => '/pengesahan-data-staf/akaun-bank',
            'searchAction' => '/pengesahan-data-staf/akaun-bank/verification',
            'search' => $search,
            'pending' => [
                'title' => '[Pengesahan] Senarai Permohonan Penukaran Akaun Bank (Gaji)',
                'columns' => ['No. KP/Paspot', 'IC Pemilik', 'Nama Pemilik', 'No. Akaun', 'Nama Bank', 'Tarikh Mohon', 'Status'],
                ...$this->pendingVerification($search, $pendingPage),
            ],
            'history' => [
                'title' => 'Permohonan yang telah diambil tindakan',
                'columns' => ['Nama Pemilik', 'Nama Bank', 'Tarikh Mohon', 'Status', 'Status Oleh', 'Tarikh Status'],
                ...$this->verificationHistory($search, $historyPage),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function approvalPage(string $search = '', int $pendingPage = 1, int $historyPage = 1): array
    {
        return [
            'mode' => 'approval',
            'title' => '[Kelulusan] Senarai Permohonan Penukaran Akaun Bank (Gaji)',
            'backUrl' => '/pengesahan-data-staf/akaun-bank',
            'searchAction' => '/pengesahan-data-staf/akaun-bank/approval',
            'search' => $search,
            'pending' => [
                'title' => '[Kelulusan] Senarai Permohonan Penukaran Akaun Bank (Gaji)',
                'columns' => ['No. KP/Paspot', 'IC Pemilik', 'Nama Pemilik', 'No. Akaun', 'Nama Bank', 'Status'],
                ...$this->pendingApproval($search, $pendingPage),
            ],
            'history' => [
                'title' => 'Permohonan yang telah diambil tindakan',
                'columns' => ['Nama Pemilik', 'Nama Bank', 'Tarikh Mohon', 'Tarikh Verify', 'Status', 'Status Oleh', 'Tarikh Status'],
                ...$this->approvalHistory($search, $historyPage),
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function detail(string $id, string $mode, bool $write): ?array
    {
        $row = $this->rowById($id);
        if ($row === null) {
            return null;
        }

        $fields = [
            ['label' => 'No. Akaun Bank', 'value' => $this->value($row['no_akaun'] ?? null, '-')],
            ['label' => 'Nama Bank', 'value' => $this->value($row['bank_desc'] ?? $row['kod_bank'] ?? null, '-')],
            ['label' => 'Nama Pemilik Akaun Bank', 'value' => $this->value($row['nama_pemilik'] ?? null, '-')],
            ['label' => 'Alasan', 'value' => $this->value($row['alasan'] ?? null, '-')],
            ['label' => 'IC Pemilik Akaun Bank', 'value' => $this->value($row['ic_pemilik'] ?? null, '-')],
            ['label' => 'IC/KP Kakitangan', 'value' => $this->value($row['icno'] ?? null, '-')],
            ...$this->attachments([['label' => 'Lampiran', 'hash' => $row['attachment'] ?? null]]),
        ];

        if ($mode === 'approval') {
            $fields[] = ['label' => 'Verifier', 'value' => $this->titleCase($row['verify_by_name'] ?? null)];
            $fields[] = ['label' => 'Tarikh Verify', 'value' => $this->date($row['verify_status_dt'] ?? null)];
            $fields[] = ['label' => 'Catatan Pengesahan', 'value' => $this->value($row['verify_remarks'] ?? null, '-')];
        }

        if (!$write) {
            $fields[] = ['label' => 'Status', 'value' => $this->statusLabel($row)];
            $fields[] = ['label' => 'Catatan', 'value' => $this->value($mode === 'approval' ? ($row['approval_remarks'] ?? null) : ($row['verify_remarks'] ?? null), '-')];
        }

        return [
            'id' => $this->value($row['id'] ?? null, ''),
            'mode' => $mode,
            'write' => $write,
            'title' => $mode === 'approval' ? 'Maklumat Akaun Bank Untuk Diluluskan' : 'Maklumat Akaun Bank Untuk Disahkan',
            'backUrl' => $mode === 'approval'
                ? '/pengesahan-data-staf/akaun-bank/approval'
                : '/pengesahan-data-staf/akaun-bank/verification',
            'action' => $mode === 'approval'
                ? '/pengesahan-data-staf/akaun-bank/approve?id=' . rawurlencode($id)
                : '/pengesahan-data-staf/akaun-bank/verify?id=' . rawurlencode($id),
            'fields' => $fields,
        ];
    }

    /**
     * @return array{success: bool, message: string, errors: array<string, string>}
     */
    public function verify(string $id, string $action, string $remarks, string $actorIcno): array
    {
        $action = $action === 'accept' ? 'accept' : ($action === 'reject' ? 'reject' : '');
        $remarks = trim($remarks);
        if ($action === '') {
            return ['success' => false, 'message' => 'Tindakan pengesahan tidak sah.', 'errors' => ['general' => 'Tindakan pengesahan tidak sah.']];
        }

        if ($remarks === '') {
            return ['success' => false, 'message' => 'Catatan diperlukan.', 'errors' => ['remarks' => 'Catatan diperlukan.']];
        }

        $status = $action === 'accept' ? 1 : 2;
        $message = $action === 'accept'
            ? 'Maklumat akaun baru berjaya disahkan.'
            : 'Maklumat akaun baru telah dikembalikan.';

        try {
            $updated = $this->db->transaction(function () use ($id, $actorIcno, $remarks, $status): int {
                return $this->db->createCommand()->update(
                    self::APPLICATION_TABLE,
                    [
                        'verify_status' => $status,
                        'verify_status_by' => $actorIcno,
                        'verify_status_dt' => date('Y-m-d'),
                        'verify_remarks' => $remarks,
                    ],
                    ['id' => $id, 'verify_status' => null],
                )->execute();
            });
        } catch (\Throwable) {
            return ['success' => false, 'message' => 'Pengesahan tidak berjaya disimpan.', 'errors' => ['general' => 'Pengesahan tidak berjaya disimpan.']];
        }

        if ($updated < 1) {
            return ['success' => false, 'message' => 'Rekod ini telah diambil tindakan atau tidak dijumpai.', 'errors' => ['general' => 'Rekod ini telah diambil tindakan atau tidak dijumpai.']];
        }

        return ['success' => true, 'message' => $message, 'errors' => []];
    }

    /**
     * @return array{success: bool, message: string, errors: array<string, string>}
     */
    public function approve(string $id, string $action, string $remarks, string $actorIcno): array
    {
        $action = $action === 'approve' ? 'approve' : ($action === 'reject' ? 'reject' : '');
        $remarks = trim($remarks);
        if ($action === '') {
            return ['success' => false, 'message' => 'Tindakan kelulusan tidak sah.', 'errors' => ['general' => 'Tindakan kelulusan tidak sah.']];
        }

        if ($remarks === '') {
            return ['success' => false, 'message' => 'Catatan diperlukan.', 'errors' => ['remarks' => 'Catatan diperlukan.']];
        }

        $status = $action === 'approve' ? 1 : 2;
        $message = $action === 'approve'
            ? 'Maklumat akaun baru berjaya diluluskan.'
            : 'Maklumat akaun baru telah ditolak.';

        try {
            $updated = $this->db->transaction(function () use ($id, $actorIcno, $remarks, $status, $action): int {
                $row = $this->approvalCandidate($id);
                if ($row === null) {
                    return 0;
                }

                $updated = $this->db->createCommand()->update(
                    self::APPLICATION_TABLE,
                    [
                        'approval_status' => $status,
                        'approval_status_by' => $actorIcno,
                        'approval_status_dt' => date('Y-m-d'),
                        'approval_remarks' => $remarks,
                    ],
                    ['id' => $id, 'verify_status' => 1, 'approval_status' => null],
                )->execute();

                if ($updated > 0 && $action === 'approve') {
                    $this->db->createCommand()->insert(self::QUEUE_TABLE, [
                        'icno' => (string) $row['icno'],
                        'tbl_apt_id' => (int) $row['id'],
                        'tarikh_kuatkuasa' => $this->effectiveDate($row['application_dt'] ?? null),
                    ])->execute();
                }

                return $updated;
            });
        } catch (\Throwable) {
            return ['success' => false, 'message' => 'Kelulusan tidak berjaya disimpan.', 'errors' => ['general' => 'Kelulusan tidak berjaya disimpan.']];
        }

        if ($updated < 1) {
            return ['success' => false, 'message' => 'Rekod ini telah diambil tindakan atau tidak dijumpai.', 'errors' => ['general' => 'Rekod ini telah diambil tindakan atau tidak dijumpai.']];
        }

        return ['success' => true, 'message' => $message, 'errors' => []];
    }

    private function countWhere(string $where): int
    {
        try {
            return (int) $this->db->createCommand('SELECT COUNT(*) FROM ' . self::APPLICATION_TABLE . ' WHERE ' . $where)->queryScalar();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function pendingVerification(string $search, int $page): array
    {
        return $this->rows('verification-pending', $search, '/pengesahan-data-staf/akaun-bank/verify?id=', $page, 'pending_page');
    }

    /**
     * @return array<string, mixed>
     */
    private function verificationHistory(string $search, int $page): array
    {
        return $this->historyRows('verification-history', $search, '/pengesahan-data-staf/akaun-bank/verify-detail?id=', 'verify', $page, 'history_page');
    }

    /**
     * @return array<string, mixed>
     */
    private function pendingApproval(string $search, int $page): array
    {
        return $this->approvalRows('approval-pending', $search, '/pengesahan-data-staf/akaun-bank/approve?id=', $page, 'pending_page');
    }

    /**
     * @return array<string, mixed>
     */
    private function approvalHistory(string $search, int $page): array
    {
        return $this->historyRows('approval-history', $search, '/pengesahan-data-staf/akaun-bank/approve-detail?id=', 'approval', $page, 'history_page');
    }

    /**
     * @return array<string, mixed>
     */
    private function rows(string $scope, string $search, string $actionPrefix, int $page, string $pageParam): array
    {
        $pagination = $this->pagination($scope, $search, $page);

        return [
            'rows' => array_map(function (array $row) use ($actionPrefix): array {
            $id = $this->value($row['id'] ?? null, '');

            return [
                'id' => $id,
                'cells' => [
                    $this->value($row['icno'] ?? null, '-'),
                    $this->value($row['ic_pemilik'] ?? null, '-'),
                    $this->value($row['nama_pemilik'] ?? null, '-'),
                    $this->value($row['no_akaun'] ?? null, '-'),
                    $this->value($row['bank_desc'] ?? $row['kod_bank'] ?? null, '-'),
                    $this->date($row['application_dt'] ?? null),
                    $this->statusLabel($row),
                ],
                'actionUrl' => $id === '' ? '' : $actionPrefix . rawurlencode($id),
            ];
            }, $this->queryRows($scope, $search, self::PAGE_SIZE, $pagination['offset'])),
            'pageParam' => $pageParam,
            ...$pagination,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function approvalRows(string $scope, string $search, string $actionPrefix, int $page, string $pageParam): array
    {
        $pagination = $this->pagination($scope, $search, $page);

        return [
            'rows' => array_map(function (array $row) use ($actionPrefix): array {
            $id = $this->value($row['id'] ?? null, '');

            return [
                'id' => $id,
                'cells' => [
                    $this->value($row['icno'] ?? null, '-'),
                    $this->value($row['ic_pemilik'] ?? null, '-'),
                    $this->value($row['nama_pemilik'] ?? null, '-'),
                    $this->value($row['no_akaun'] ?? null, '-'),
                    $this->value($row['bank_desc'] ?? $row['kod_bank'] ?? null, '-'),
                    $this->statusLabel($row),
                ],
                'actionUrl' => $id === '' ? '' : $actionPrefix . rawurlencode($id),
            ];
            }, $this->queryRows($scope, $search, self::PAGE_SIZE, $pagination['offset'])),
            'pageParam' => $pageParam,
            ...$pagination,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function historyRows(string $scope, string $search, string $actionPrefix, string $kind, int $page, string $pageParam): array
    {
        $pagination = $this->pagination($scope, $search, $page);

        return [
            'rows' => array_map(function (array $row) use ($actionPrefix, $kind): array {
            $id = $this->value($row['id'] ?? null, '');
            $cells = [
                $this->value($row['nama_pemilik'] ?? null, '-'),
                $this->value($row['bank_desc'] ?? $row['kod_bank'] ?? null, '-'),
                $this->date($row['application_dt'] ?? null),
            ];

            if ($kind === 'approval') {
                $cells[] = $this->date($row['verify_status_dt'] ?? null);
            }

            $cells = [
                ...$cells,
                $this->statusLabel($row),
                $this->titleCase($kind === 'approval' ? ($row['approval_by_name'] ?? null) : ($row['verify_by_name'] ?? null)),
                $this->date($kind === 'approval' ? ($row['approval_status_dt'] ?? null) : ($row['verify_status_dt'] ?? null)),
            ];

            return [
                'id' => $id,
                'cells' => $cells,
                'actionUrl' => $id === '' ? '' : $actionPrefix . rawurlencode($id),
            ];
            }, $this->queryRows($scope, $search, self::PAGE_SIZE, $pagination['offset'])),
            'pageParam' => $pageParam,
            ...$pagination,
        ];
    }

    /**
     * @return array{total: int, page: int, pageCount: int, offset: int, pageSize: int}
     */
    private function pagination(string $scope, string $search, int $page): array
    {
        $total = $this->countRows($scope, $search);
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
    private function queryRows(string $scope, string $search, int $limit, int $offset): array
    {
        [$where, $params] = $this->whereAndParams($scope, $search);
        $limit = max(1, $limit);
        $offset = max(0, $offset);

        try {
            $rows = $this->db->createCommand(
                $this->selectSql($where) . ' ORDER BY application.application_dt DESC, application.id DESC LIMIT ' . $limit . ' OFFSET ' . $offset,
                $params,
            )->queryAll();
        } catch (\Throwable) {
            return [];
        }

        return array_values(array_filter($rows, 'is_array'));
    }

    private function countRows(string $scope, string $search): int
    {
        [$where, $params] = $this->whereAndParams($scope, $search);

        try {
            return (int) $this->db->createCommand('SELECT COUNT(*) FROM (' . $this->selectSql($where) . ') staff_data_count', $params)->queryScalar();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function whereAndParams(string $scope, string $search): array
    {
        $where = match ($scope) {
            'verification-pending' => 'application.verify_status IS NULL',
            'verification-history' => 'application.verify_status IN (1, 2)',
            'approval-pending' => 'application.verify_status = 1 AND application.approval_status IS NULL',
            'approval-history' => 'application.verify_status = 1 AND application.approval_status IN (1, 2)',
            default => '1 = 0',
        };
        $params = [];
        $search = trim($search);
        if ($search !== '') {
            $where .= ' AND (application.icno LIKE :search OR application.ic_pemilik LIKE :search OR application.nama_pemilik LIKE :search OR application.no_akaun LIKE :search OR staff.CONm LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        return [$where, $params];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function rowById(string $id): ?array
    {
        if ($id === '') {
            return null;
        }

        try {
            $row = $this->db->createCommand($this->selectSql('application.id = :id') . ' LIMIT 1', [':id' => $id])->queryOne();
        } catch (\Throwable) {
            return null;
        }

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function approvalCandidate(string $id): ?array
    {
        $row = $this->db->createCommand(
            'SELECT * FROM ' . self::APPLICATION_TABLE . ' WHERE id = :id AND verify_status = 1 AND approval_status IS NULL LIMIT 1',
            [':id' => $id],
        )->queryOne();

        return is_array($row) ? $row : null;
    }

    private function selectSql(string $where): string
    {
        return <<<SQL
            SELECT application.*, bank.bank_desc,
                   staff.CONm AS staff_name,
                   verifier.CONm AS verify_by_name,
                   approver.CONm AS approval_by_name
            FROM hronline.tbl_akaun_permohonan_tukar application
            LEFT JOIN hronline.tbl_bank_main bank ON bank.bank_code = application.kod_bank
            LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = application.icno
            LEFT JOIN hronline.tblprcobiodata verifier ON verifier.ICNO = application.verify_status_by
            LEFT JOIN hronline.tblprcobiodata approver ON approver.ICNO = application.approval_status_by
            WHERE {$where}
            SQL;
    }

    /**
     * @param list<array{label: string, hash: mixed}> $files
     * @return list<array{label: string, value: string, url: string, kind: string}>
     */
    private function attachments(array $files): array
    {
        $attachments = [];
        foreach ($files as $file) {
            $hash = $this->value($file['hash'] ?? null, '');
            $url = $this->fileService->viewUrl($hash);
            if ($url === '') {
                continue;
            }

            $attachments[] = [
                'label' => $this->value($file['label'] ?? null, 'Fail'),
                'value' => 'Lihat Fail',
                'url' => $url,
                'kind' => 'file',
            ];
        }

        return $attachments;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function statusLabel(array $row): string
    {
        $verifyStatus = $this->value($row['verify_status'] ?? null, '');
        $approvalStatus = $this->value($row['approval_status'] ?? null, '');

        if ($verifyStatus === '1') {
            return match ($approvalStatus) {
                '1' => 'Diluluskan',
                '2' => 'Ditolak',
                default => 'Menunggu Kelulusan',
            };
        }

        if ($verifyStatus === '2') {
            return 'Dikembalikan';
        }

        return 'Menunggu Pengesahan';
    }

    private function effectiveDate(mixed $applicationDate): string
    {
        try {
            $date = new \DateTime($this->value($applicationDate, date('Y-m-d')));
        } catch (\Throwable) {
            $date = new \DateTime();
        }

        $date->modify('+1 month');
        $date->modify('first day of this month');
        $base = $date->format('Y-m-d');
        $approval = new \DateTime();
        if ($approval->format('Y-m-d') > $base) {
            $approval->modify('+1 day');

            return $approval->format('Y-m-d');
        }

        return $base;
    }

    private function date(mixed $value): string
    {
        return $this->dateFormatter->display($value, '-');
    }

    private function titleCase(mixed $value): string
    {
        $value = $this->value($value, '');

        return $value === '' ? '-' : ucwords(strtolower($value));
    }

    private function value(mixed $value, string $fallback = ''): string
    {
        if ($value === null) {
            return $fallback;
        }

        $value = trim((string) $value);

        return $value === '' ? $fallback : $value;
    }
}
