<?php

declare(strict_types=1);

namespace App\Shared\GambarProfile;

use App\Shared\EmployeeRepository;
use App\Shared\MediaHostFileService;
use App\Shared\StaffDateFormatter;
use Psr\Http\Message\UploadedFileInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class ProfilePictureService
{
    private const TABLE = 'keselamatan.utils_tbl_gambar';
    private const UPLOAD_DIRECTORY = 'Maklumat Rekod Kakitangan/gambar_profile';
    private const UPLOAD_CATEGORY = '01';
    private const MAX_IMAGE_SIZE = 2 * 1024 * 1024;
    private const ADMIN_PAGE_SIZE = 15;
    private const STAFF_SEARCH_PAGE_SIZE = 15;
    private const LOG_TABLE = 'keselamatan.utils_tbl_gambar_logs';

    public function __construct(
        private ConnectionInterface $db,
        private EmployeeRepository $employees,
        private MediaHostFileService $fileService,
        private BkumsProfilePhotoService $bkumsPhotoService,
        private StaffDateFormatter $dateFormatter,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function requestList(string $icno): ?array
    {
        $staff = $this->employees->byIcno($icno);
        if ($staff === null) {
            return null;
        }

        $rows = $this->requestRows($icno);

        return [
            'title' => 'Permohonan Kemaskini Gambar Profil HROnline',
            'name' => $this->employees->displayName($staff['CONm'] ?? null),
            'backUrl' => '/biodata/userview',
            'addUrl' => '/gambar-profile/gambar',
            'addLabel' => 'Mohon Baru',
            'info' => 'Gambar profil perlu dalam format JPEG dan tidak melebihi 2MB.',
            'columns' => ['Nama', 'Tarikh Permohonan', 'Status', 'Tarikh Status'],
            'rows' => array_map(fn (array $row): array => $this->listRow($row), $rows),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function uploadForm(string $icno): ?array
    {
        $staff = $this->employees->byIcno($icno);
        if ($staff === null) {
            return null;
        }

        return [
            'title' => 'Gambar Profil HROnline',
            'pageTitle' => 'Kemaskini Gambar Profil',
            'sectionTitle' => 'Muat Naik Gambar Profil',
            'submitLabel' => 'Hantar',
            'name' => $this->employees->displayName($staff['CONm'] ?? null),
            'backUrl' => '/gambar-profile/semakan',
            'action' => '/gambar-profile/gambar',
            'currentPhoto' => $this->bkumsPhotoUrl($icno),
            'guide' => [
                'title' => 'Garis Panduan & Peraturan Kemaskini Gambar Profil',
                'description' => 'Sila rujuk panduan di bawah sebelum mengemaskini gambar profil anda. Gambar yang tidak mengikut piawaian panduan berkemungkinan akan ditolak.',
                'url' => '/files/PanduanMuatNaikGambarStaff.pdf',
                'label' => 'Panduan',
            ],
            'fields' => [
                [
                    'name' => 'imageFile',
                    'label' => 'Muat Naik Gambar',
                    'type' => 'file',
                    'required' => true,
                    'help' => 'JPEG sahaja. Saiz maksimum 2MB.',
                    'accept' => 'image/jpeg,.jpeg,.jpg',
                ],
            ],
            'values' => ['imageFile' => ''],
        ];
    }

    /**
     * @return array{success: bool, errors: array<string, string>}
     */
    public function submitRequest(string $icno, ?UploadedFileInterface $file): array
    {
        $staff = $this->employees->byIcno($icno);
        if ($staff === null) {
            return ['success' => false, 'errors' => ['general' => 'Rekod staf tidak dijumpai.']];
        }

        if ($this->hasPendingRequest($icno)) {
            return ['success' => false, 'errors' => ['general' => 'Anda masih mempunyai permohonan berstatus PERMOHONAN BARU.']];
        }

        $fileError = $this->validateImageFile($file);
        if ($fileError !== null) {
            return ['success' => false, 'errors' => ['imageFile' => $fileError]];
        }

        \assert($file instanceof UploadedFileInterface);
        $upload = $this->fileService->uploadDocument($file, self::UPLOAD_DIRECTORY, $icno, self::UPLOAD_CATEGORY);
        if ($upload['success'] !== true) {
            return ['success' => false, 'errors' => ['imageFile' => $upload['message'] ?? 'Muat naik gambar gagal.']];
        }

        try {
            $this->db->createCommand()->insert(self::TABLE, [
                'ICNO' => $icno,
                'entry_dt' => date('Y-m-d H:i:s'),
                'gambar' => (string) $upload['hash'],
                'year' => date('Y'),
                'status_label' => 1,
                'curr_process' => 1,
                'type' => 1,
                'status' => null,
            ])->execute();
        } catch (\Throwable) {
            return ['success' => false, 'errors' => ['general' => 'Permohonan kemaskini gambar tidak berjaya dihantar.']];
        }

        return ['success' => true, 'errors' => []];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function detail(string $id, string $icno): ?array
    {
        if ($id === '') {
            return null;
        }

        $staff = $this->employees->byIcno($icno);
        if ($staff === null) {
            return null;
        }

        $row = $this->requestRecord($id, $icno);
        if ($row === null) {
            return null;
        }

        $imageUrl = $this->fileService->viewUrl($this->value($row['gambar'] ?? null, ''));

        return [
            'title' => 'Permohonan Kemaskini Gambar Profil',
            'name' => $this->employees->displayName($staff['CONm'] ?? null),
            'backUrl' => '/gambar-profile/semakan',
            'currentPhoto' => $this->bkumsPhotoUrl($icno),
            'requestedPhoto' => $imageUrl,
            'fields' => [
                ['label' => 'Nama', 'value' => $this->employees->displayName($staff['CONm'] ?? null)],
                ['label' => 'No. Pekerja', 'value' => $this->employees->staffNumber($staff)],
                ['label' => 'No. Kad Pengenalan', 'value' => $icno],
                ['label' => 'Emel', 'value' => $this->employees->value($staff['COEmail'] ?? null)],
                ['label' => 'Jabatan', 'value' => $this->employees->value($staff['department_name'] ?? null)],
                ['label' => 'Tarikh Permohonan', 'value' => $this->date($row['entry_dt'] ?? null)],
                ['label' => 'Status', 'value' => $this->statusLabel($row), 'kind' => 'status'],
                ['label' => 'Tarikh Status', 'value' => $this->date($row['app_dt'] ?? null)],
                ['label' => 'Catatan', 'value' => $this->value($row['catatan'] ?? null, '-')],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function adminPendingList(string $search = '', int $page = 1): array
    {
        return $this->adminList(
            'Senarai Permohonan Penukaran Gambar Profil',
            'Permohonan Baru',
            '/gambar-profile/senarai',
            $this->adminRows('pending', $search, $page),
            $search,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function adminHistoryList(string $search = '', int $page = 1): array
    {
        return $this->adminList(
            'Rekod Kemaskini Gambar Profil HROnline',
            'Rekod Permohonan',
            '/gambar-profile/rekod-permohonan',
            $this->adminRows('history', $search, $page),
            $search,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function adminReview(string $id): ?array
    {
        if ($id === '') {
            return null;
        }

        $row = $this->adminRecord($id);
        if ($row === null) {
            return null;
        }

        $icno = $this->value($row['ICNO'] ?? null, '');
        $staffName = $this->employees->displayName($row['staff_name'] ?? null);
        $requestedPhoto = $this->fileService->viewUrl($this->value($row['gambar'] ?? null, ''));
        $isPending = $this->value($row['status'] ?? null, '') === '';

        return [
            'title' => $isPending ? 'Semakan Gambar Profil' : 'Rekod Semakan Gambar Profil',
            'id' => $this->value($row['id'] ?? null, ''),
            'name' => $staffName,
            'backUrl' => $isPending ? '/gambar-profile/senarai' : '/gambar-profile/rekod-permohonan',
            'action' => '/gambar-profile/semakan-gambar?id=' . rawurlencode($this->value($row['id'] ?? null, '')),
            'isPending' => $isPending,
            'currentPhoto' => $this->bkumsPhotoUrl($icno),
            'requestedPhoto' => $requestedPhoto,
            'downloadUrl' => $requestedPhoto,
            'fields' => [
                ['label' => 'Nama', 'value' => $staffName],
                ['label' => 'No. Pekerja', 'value' => $this->value($row['staff_number'] ?? null, 'Tiada Rekod')],
                ['label' => 'No. Kad Pengenalan', 'value' => $icno],
                ['label' => 'Emel', 'value' => $this->value($row['staff_email'] ?? null, 'Tiada Rekod')],
                ['label' => 'Jabatan', 'value' => $this->value($row['department_name'] ?? null, 'Tiada Rekod')],
                ['label' => 'Tarikh Permohonan', 'value' => $this->date($row['entry_dt'] ?? null)],
                ['label' => 'Status', 'value' => $this->statusLabel($row), 'kind' => 'status'],
                ['label' => 'Tarikh Status', 'value' => $this->date($row['app_dt'] ?? null)],
                ['label' => 'Status Oleh', 'value' => $this->employees->displayName($row['admin_name'] ?? null)],
                ['label' => 'Catatan', 'value' => $this->value($row['catatan'] ?? null, '-')],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, message: string, redirect: string}
     */
    public function adminDecision(string $id, string $adminIcno, array $input): array
    {
        $status = is_scalar($input['app_status'] ?? null) ? trim((string) $input['app_status']) : '';
        $remarks = is_scalar($input['catatan'] ?? null) ? trim((string) $input['catatan']) : '';

        if ($id === '') {
            return ['success' => false, 'message' => 'Rekod permohonan tidak sah.', 'redirect' => '/gambar-profile/senarai'];
        }

        if (!in_array($status, ['DILULUSKAN', 'TIDAK DILULUSKAN'], true)) {
            return ['success' => false, 'message' => 'Sila pilih status kelulusan.', 'redirect' => $this->adminReviewUrl($id)];
        }

        if ($status === 'TIDAK DILULUSKAN' && $remarks === '') {
            return ['success' => false, 'message' => 'Catatan diperlukan untuk permohonan yang ditolak.', 'redirect' => $this->adminReviewUrl($id)];
        }

        $pending = $this->pendingAdminRecord($id);
        if ($pending === null) {
            return ['success' => false, 'message' => 'Permohonan ini telah selesai atau tidak dijumpai.', 'redirect' => '/gambar-profile/senarai'];
        }

        return $status === 'TIDAK DILULUSKAN'
            ? $this->rejectRequest($id, $adminIcno, $remarks)
            : $this->approveRequest($id, $adminIcno, $remarks);
    }

    /**
     * @return array<string, mixed>
     */
    public function counterStaffSearch(string $search = '', int $page = 1): array
    {
        $result = $this->staffSearchRows($search, $page);

        return [
            'title' => 'Carian Kakitangan',
            'sectionTitle' => 'Senarai Kakitangan',
            'currentUrl' => '/gambar-profile/cari-kakitangan',
            'historyUrl' => '/gambar-profile/admin-senarai-penukaran',
            'searchAction' => '/gambar-profile/cari-kakitangan',
            'search' => trim($search),
            'total' => $result['total'],
            'page' => $result['page'],
            'pageCount' => $result['pageCount'],
            'pageSize' => self::STAFF_SEARCH_PAGE_SIZE,
            'offset' => $result['offset'],
            'columns' => ['Nama', 'No. Pekerja', 'No. Kad Pengenalan', 'Jabatan'],
            'rows' => array_map(fn (array $row): array => $this->staffSearchListRow($row), $result['rows']),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function counterUploadForm(string $staffIcno): ?array
    {
        $staff = $this->employees->byIcno($staffIcno);
        if ($staff === null) {
            return null;
        }

        $name = $this->employees->displayName($staff['CONm'] ?? null);

        return [
            'title' => 'Gambar Profil HROnline',
            'pageTitle' => 'Muat Naik Gambar Kakitangan',
            'sectionTitle' => 'Muat Naik Gambar Profil',
            'submitLabel' => 'Hantar',
            'name' => $name,
            'backUrl' => '/gambar-profile/cari-kakitangan',
            'action' => '/gambar-profile/muatnaik-gambar-kakitangan?id=' . rawurlencode($staffIcno),
            'currentPhoto' => $this->bkumsPhotoUrl($staffIcno),
            'guide' => [
                'title' => 'Garis Panduan & Peraturan Kemaskini Gambar Profil',
                'description' => 'Sila rujuk panduan di bawah sebelum memuat naik gambar profil kakitangan.',
                'url' => '/files/PanduanMuatNaikGambarStaff.pdf',
                'label' => 'Panduan',
            ],
            'staffFields' => [
                ['label' => 'Nama', 'value' => $name],
                ['label' => 'No. Pekerja', 'value' => $this->employees->staffNumber($staff)],
                ['label' => 'No. Kad Pengenalan', 'value' => $staffIcno],
                ['label' => 'Emel', 'value' => $this->employees->value($staff['COEmail'] ?? null)],
                ['label' => 'Jabatan', 'value' => $this->employees->value($staff['department_name'] ?? null)],
            ],
            'fields' => [
                [
                    'name' => 'imageFile',
                    'label' => 'Muat Naik Gambar',
                    'type' => 'file',
                    'required' => true,
                    'help' => 'JPEG sahaja. Saiz maksimum 2MB.',
                    'accept' => 'image/jpeg,.jpeg,.jpg',
                ],
            ],
            'values' => ['imageFile' => ''],
        ];
    }

    /**
     * @return array{success: bool, errors: array<string, string>, redirect?: string}
     */
    public function submitCounterUpload(
        string $staffIcno,
        string $adminIcno,
        ?UploadedFileInterface $file,
        string $ipAddress,
    ): array {
        $staff = $this->employees->byIcno($staffIcno);
        if ($staff === null) {
            return ['success' => false, 'errors' => ['general' => 'Rekod staf tidak dijumpai.']];
        }

        $readinessError = $this->bkumsPhotoService->publishReadinessError();
        if ($readinessError !== null) {
            return ['success' => false, 'errors' => ['general' => $readinessError]];
        }

        if ($this->hasCurrentBkumsPhoto($staffIcno)) {
            return ['success' => false, 'errors' => ['general' => 'Staf ini telah mempunyai gambar profil BKUMS. Sila gunakan aliran permohonan kemaskini gambar.']];
        }

        $fileError = $this->validateImageFile($file);
        if ($fileError !== null) {
            return ['success' => false, 'errors' => ['imageFile' => $fileError]];
        }

        \assert($file instanceof UploadedFileInterface);
        $upload = $this->fileService->uploadDocument($file, self::UPLOAD_DIRECTORY, $staffIcno, self::UPLOAD_CATEGORY);
        if ($upload['success'] !== true) {
            return ['success' => false, 'errors' => ['imageFile' => $upload['message'] ?? 'Muat naik gambar gagal.']];
        }

        $hash = (string) $upload['hash'];

        try {
            $id = (string) $this->db->transaction(function () use ($staffIcno, $adminIcno, $hash): string {
                $now = date('Y-m-d H:i:s');
                $this->db->createCommand()->insert(self::TABLE, [
                    'ICNO' => $staffIcno,
                    'entry_dt' => $now,
                    'gambar' => $hash,
                    'app_by' => $adminIcno,
                    'app_dt' => $now,
                    'app_status' => 'DILULUSKAN',
                    'year' => date('Y'),
                    'updated_by' => $adminIcno,
                    'updated_dt' => $now,
                    'last_updated' => $now,
                    'status_label' => 5,
                    'curr_process' => 0,
                    'status' => 1,
                    'type' => 2,
                    'admin_icno' => $adminIcno,
                    'admin_dt' => $now,
                    'catatan' => 'Muat naik gambar kaunter.',
                ])->execute();

                $id = (string) $this->db->createCommand('SELECT LAST_INSERT_ID()')->queryScalar();
                if ($id === '' || $id === '0') {
                    throw new \RuntimeException('Rekod muat naik gambar tidak berjaya disimpan.');
                }

                $publish = $this->bkumsPhotoService->publishMediaHostPhoto($hash, $staffIcno);
                if ($publish['success'] !== true) {
                    throw new \RuntimeException($publish['message'] !== '' ? $publish['message'] : 'Penerbitan gambar ke BKUMS tidak berjaya.');
                }

                return $id;
            });
        } catch (\Throwable $exception) {
            $message = $exception->getMessage();

            return [
                'success' => false,
                'errors' => ['general' => $message !== '' ? $message : 'Muat naik gambar kakitangan tidak berjaya.'],
            ];
        }

        try {
            $this->insertAuditLog($adminIcno, $id, $ipAddress, date('Y-m-d H:i:s'));
        } catch (\Throwable) {
        }

        return [
            'success' => true,
            'errors' => [],
            'redirect' => '/gambar-profile/admin-view-penukaran?id=' . rawurlencode($id),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function counterHistoryList(string $search = '', int $page = 1): array
    {
        $result = $this->counterRows($search, $page);

        return [
            'title' => 'Rekod Muat Naik Gambar HROnline',
            'sectionTitle' => 'Rekod Muat Naik',
            'currentUrl' => '/gambar-profile/admin-senarai-penukaran',
            'searchUrl' => '/gambar-profile/cari-kakitangan',
            'searchAction' => '/gambar-profile/admin-senarai-penukaran',
            'search' => $search,
            'total' => $result['total'],
            'page' => $result['page'],
            'pageCount' => $result['pageCount'],
            'pageSize' => self::ADMIN_PAGE_SIZE,
            'offset' => $result['offset'],
            'columns' => ['Nama', 'Tarikh Muat Naik', 'Status', 'Tarikh Status', 'Dimuat Naik Oleh'],
            'rows' => array_map(fn (array $row): array => $this->counterListRow($row), $result['rows']),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function counterDetail(string $id): ?array
    {
        if ($id === '') {
            return null;
        }

        $row = $this->counterRecord($id);
        if ($row === null) {
            return null;
        }

        $icno = $this->value($row['ICNO'] ?? null, '');
        $staffName = $this->employees->displayName($row['staff_name'] ?? null);
        $uploadedPhoto = $this->fileService->viewUrl($this->value($row['gambar'] ?? null, ''));

        return [
            'title' => 'Rekod Muat Naik Gambar Kakitangan',
            'id' => $this->value($row['id'] ?? null, ''),
            'name' => $staffName,
            'backUrl' => '/gambar-profile/admin-senarai-penukaran',
            'currentPhoto' => $this->bkumsPhotoUrl($icno),
            'uploadedPhoto' => $uploadedPhoto,
            'downloadUrl' => $uploadedPhoto,
            'fields' => [
                ['label' => 'Nama', 'value' => $staffName],
                ['label' => 'No. Pekerja', 'value' => $this->value($row['staff_number'] ?? null, 'Tiada Rekod')],
                ['label' => 'No. Kad Pengenalan', 'value' => $icno],
                ['label' => 'Emel', 'value' => $this->value($row['staff_email'] ?? null, 'Tiada Rekod')],
                ['label' => 'Jabatan', 'value' => $this->value($row['department_name'] ?? null, 'Tiada Rekod')],
                ['label' => 'Tarikh Muat Naik', 'value' => $this->date($row['entry_dt'] ?? null)],
                ['label' => 'Status', 'value' => $this->statusLabel($row), 'kind' => 'status'],
                ['label' => 'Tarikh Status', 'value' => $this->date($row['app_dt'] ?? null)],
                ['label' => 'Dimuat Naik Oleh', 'value' => $this->employees->displayName($row['admin_name'] ?? null)],
                ['label' => 'Catatan', 'value' => $this->value($row['catatan'] ?? null, '-')],
            ],
        ];
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total: int, page: int, pageCount: int, offset: int}
     */
    private function staffSearchRows(string $search, int $page): array
    {
        $search = trim($search);
        if ($search === '') {
            return ['rows' => [], 'total' => 0, 'page' => 1, 'pageCount' => 1, 'offset' => 0];
        }

        $params = [
            ':searchName' => '%' . $search . '%',
            ':searchIcno' => '%' . $search . '%',
            ':searchStaffNumber' => '%' . $search . '%',
        ];

        try {
            $total = (int) $this->db->createCommand(
                <<<SQL
                SELECT COUNT(*)
                FROM hronline.tblprcobiodata staff
                LEFT JOIN hronline.department dept ON dept.id = staff.DeptId
                WHERE staff.Status = '1'
                  AND (
                        staff.CONm LIKE :searchName
                        OR staff.ICNO LIKE :searchIcno
                        OR staff.COOldID LIKE :searchStaffNumber
                      )
                SQL,
                $params,
            )->queryScalar();
            $pageCount = max(1, (int) ceil($total / self::STAFF_SEARCH_PAGE_SIZE));
            $page = min(max(1, $page), $pageCount);
            $offset = ($page - 1) * self::STAFF_SEARCH_PAGE_SIZE;
            $limit = self::STAFF_SEARCH_PAGE_SIZE;
            $rows = $this->db->createCommand(
                <<<SQL
                SELECT staff.ICNO,
                       staff.CONm AS staff_name,
                       staff.COOldID AS staff_number,
                       dept.fullname AS department_name
                FROM hronline.tblprcobiodata staff
                LEFT JOIN hronline.department dept ON dept.id = staff.DeptId
                WHERE staff.Status = '1'
                  AND (
                        staff.CONm LIKE :searchName
                        OR staff.ICNO LIKE :searchIcno
                        OR staff.COOldID LIKE :searchStaffNumber
                      )
                ORDER BY staff.CONm ASC
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
    private function staffSearchListRow(array $row): array
    {
        $icno = $this->value($row['ICNO'] ?? null, '');

        return [
            'id' => $icno,
            'cells' => [
                $this->employees->displayName($row['staff_name'] ?? null),
                $this->value($row['staff_number'] ?? null, 'Tiada Rekod'),
                $icno,
                $this->value($row['department_name'] ?? null, 'Tiada Rekod'),
            ],
            'actionLabel' => 'Muat Naik',
            'actionIcon' => 'upload',
            'viewUrl' => $icno === '' ? '' : '/gambar-profile/muatnaik-gambar-kakitangan?id=' . rawurlencode($icno),
        ];
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total: int, page: int, pageCount: int, offset: int}
     */
    private function counterRows(string $search, int $page): array
    {
        $params = [];
        $search = trim($search);
        $searchCondition = '';
        if ($search !== '') {
            $params[':searchName'] = '%' . $search . '%';
            $params[':searchIcno'] = '%' . $search . '%';
            $params[':searchStaffNumber'] = '%' . $search . '%';
            $params[':searchAdminName'] = '%' . $search . '%';
            $searchCondition = <<<SQL
              AND (
                    staff.CONm LIKE :searchName
                    OR request.ICNO LIKE :searchIcno
                    OR staff.COOldID LIKE :searchStaffNumber
                    OR admin.CONm LIKE :searchAdminName
                  )
            SQL;
        }

        try {
            $total = (int) $this->db->createCommand(
                <<<SQL
                SELECT COUNT(*)
                FROM keselamatan.utils_tbl_gambar request
                LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = request.ICNO
                LEFT JOIN hronline.tblprcobiodata admin ON admin.ICNO = request.app_by
                WHERE request.type = 2
                  {$searchCondition}
                SQL,
                $params,
            )->queryScalar();
            $pageCount = max(1, (int) ceil($total / self::ADMIN_PAGE_SIZE));
            $page = min(max(1, $page), $pageCount);
            $offset = ($page - 1) * self::ADMIN_PAGE_SIZE;
            $limit = self::ADMIN_PAGE_SIZE;
            $rows = $this->db->createCommand(
                <<<SQL
                SELECT request.*,
                       staff.CONm AS staff_name,
                       staff.COOldID AS staff_number,
                       admin.CONm AS admin_name
                FROM keselamatan.utils_tbl_gambar request
                LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = request.ICNO
                LEFT JOIN hronline.tblprcobiodata admin ON admin.ICNO = request.app_by
                WHERE request.type = 2
                  {$searchCondition}
                ORDER BY request.entry_dt DESC, request.id DESC
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
    private function counterListRow(array $row): array
    {
        $id = $this->value($row['id'] ?? null, '');
        $status = $this->statusLabel($row);

        return [
            'id' => $id,
            'cells' => [
                $this->employees->displayName($row['staff_name'] ?? null),
                $this->date($row['entry_dt'] ?? null),
                $status,
                $this->date($row['app_dt'] ?? null),
                $this->employees->displayName($row['admin_name'] ?? null),
            ],
            'status' => $status,
            'actionLabel' => 'Lihat',
            'actionIcon' => 'eye',
            'viewUrl' => $id === '' ? '' : '/gambar-profile/admin-view-penukaran?id=' . rawurlencode($id),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function counterRecord(string $id): ?array
    {
        try {
            $row = $this->db->createCommand(
                <<<SQL
                SELECT request.*,
                       staff.CONm AS staff_name,
                       staff.COOldID AS staff_number,
                       staff.COEmail AS staff_email,
                       dept.fullname AS department_name,
                       admin.CONm AS admin_name
                FROM keselamatan.utils_tbl_gambar request
                LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = request.ICNO
                LEFT JOIN hronline.department dept ON dept.id = staff.DeptId
                LEFT JOIN hronline.tblprcobiodata admin ON admin.ICNO = request.app_by
                WHERE request.id = :id
                  AND request.type = 2
                LIMIT 1
                SQL,
                [':id' => $id],
            )->queryOne();
        } catch (\Throwable) {
            return null;
        }

        return is_array($row) ? $row : null;
    }

    private function hasCurrentBkumsPhoto(string $icno): bool
    {
        $handle = @fopen($this->bkumsPhotoUrl($icno), 'rb');
        if ($handle === false) {
            return false;
        }

        fclose($handle);

        return true;
    }

    private function insertAuditLog(string $adminIcno, string $requestId, string $ipAddress, string $now): void
    {
        $this->db->createCommand()->insert(self::LOG_TABLE, [
            'usern' => $adminIcno,
            'COUpadteDate' => $now,
            'COUpdateIP' => $ipAddress,
            'COUpdateComp' => $ipAddress,
            'COUpdateCompUser' => $adminIcno,
            'idval' => $requestId,
        ])->execute();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function requestRows(string $icno): array
    {
        try {
            $rows = $this->db->createCommand(
                <<<SQL
                SELECT request.*, staff.CONm AS staff_name
                FROM keselamatan.utils_tbl_gambar request
                LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = request.ICNO
                WHERE request.ICNO = :icno
                  AND request.type = 1
                ORDER BY request.entry_dt DESC, request.id DESC
                SQL,
                [':icno' => $icno],
            )->queryAll();
        } catch (\Throwable) {
            return [];
        }

        return array_values(array_filter($rows, 'is_array'));
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function listRow(array $row): array
    {
        $id = $this->value($row['id'] ?? null, '');

        return [
            'id' => $id,
            'cells' => [
                $this->employees->displayName($row['staff_name'] ?? null),
                $this->date($row['entry_dt'] ?? null),
                $this->statusLabel($row),
                $this->date($row['app_dt'] ?? null),
            ],
            'viewUrl' => $id === '' ? '' : '/gambar-profile/lihat-permohonan?id=' . rawurlencode($id),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function requestRecord(string $id, string $icno): ?array
    {
        try {
            $row = $this->db->createCommand(
                <<<SQL
                SELECT *
                FROM keselamatan.utils_tbl_gambar
                WHERE id = :id
                  AND ICNO = :icno
                  AND type = 1
                LIMIT 1
                SQL,
                [':id' => $id, ':icno' => $icno],
            )->queryOne();
        } catch (\Throwable) {
            return null;
        }

        return is_array($row) ? $row : null;
    }

    /**
     * @param array{rows: list<array<string, mixed>>, total: int, page: int, pageCount: int, offset: int} $result
     * @return array<string, mixed>
     */
    private function adminList(string $title, string $sectionTitle, string $currentUrl, array $result, string $search): array
    {
        return [
            'title' => $title,
            'sectionTitle' => $sectionTitle,
            'currentUrl' => $currentUrl,
            'pendingUrl' => '/gambar-profile/senarai',
            'historyUrl' => '/gambar-profile/rekod-permohonan',
            'searchAction' => $currentUrl,
            'search' => $search,
            'total' => $result['total'],
            'page' => $result['page'],
            'pageCount' => $result['pageCount'],
            'pageSize' => self::ADMIN_PAGE_SIZE,
            'offset' => $result['offset'],
            'columns' => ['Nama', 'Tarikh Mohon', 'Status', 'Tarikh Status', 'Status Oleh'],
            'rows' => array_map(fn (array $row): array => $this->adminListRow($row), $result['rows']),
        ];
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total: int, page: int, pageCount: int, offset: int}
     */
    private function adminRows(string $kind, string $search, int $page): array
    {
        $statusCondition = $kind === 'history'
            ? 'request.status IN (1, 2)'
            : 'request.status IS NULL';
        $params = [];
        $search = trim($search);
        $searchCondition = '';
        if ($search !== '') {
            $params[':searchName'] = '%' . $search . '%';
            $params[':searchIcno'] = '%' . $search . '%';
            $params[':searchStaffNumber'] = '%' . $search . '%';
            $params[':searchAdminName'] = '%' . $search . '%';
            $searchCondition = <<<SQL
              AND (
                    staff.CONm LIKE :searchName
                    OR request.ICNO LIKE :searchIcno
                    OR staff.COOldID LIKE :searchStaffNumber
                    OR admin.CONm LIKE :searchAdminName
                  )
            SQL;
        }

        try {
            $total = (int) $this->db->createCommand(
                <<<SQL
                SELECT COUNT(*)
                FROM keselamatan.utils_tbl_gambar request
                LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = request.ICNO
                LEFT JOIN hronline.tblprcobiodata admin ON admin.ICNO = request.app_by
                WHERE request.type = 1
                  AND {$statusCondition}
                  {$searchCondition}
                SQL,
                $params,
            )->queryScalar();
            $pageCount = max(1, (int) ceil($total / self::ADMIN_PAGE_SIZE));
            $page = min(max(1, $page), $pageCount);
            $offset = ($page - 1) * self::ADMIN_PAGE_SIZE;
            $limit = self::ADMIN_PAGE_SIZE;
            $rows = $this->db->createCommand(
                <<<SQL
                SELECT request.*,
                       staff.CONm AS staff_name,
                       staff.COOldID AS staff_number,
                       admin.CONm AS admin_name
                FROM keselamatan.utils_tbl_gambar request
                LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = request.ICNO
                LEFT JOIN hronline.tblprcobiodata admin ON admin.ICNO = request.app_by
                WHERE request.type = 1
                  AND {$statusCondition}
                  {$searchCondition}
                ORDER BY request.entry_dt DESC, request.id DESC
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
        $status = $this->statusLabel($row);

        return [
            'id' => $id,
            'cells' => [
                $this->employees->displayName($row['staff_name'] ?? null),
                $this->date($row['entry_dt'] ?? null),
                $status,
                $this->date($row['app_dt'] ?? null),
                $this->employees->displayName($row['admin_name'] ?? null),
            ],
            'status' => $status,
            'actionLabel' => $this->value($row['status'] ?? null, '') === '' ? 'Semak' : 'Lihat',
            'actionIcon' => $this->value($row['status'] ?? null, '') === '' ? 'square-pen' : 'eye',
            'viewUrl' => $id === '' ? '' : '/gambar-profile/semakan-gambar?id=' . rawurlencode($id),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function adminRecord(string $id): ?array
    {
        try {
            $row = $this->db->createCommand(
                <<<SQL
                SELECT request.*,
                       staff.CONm AS staff_name,
                       staff.COOldID AS staff_number,
                       staff.COEmail AS staff_email,
                       dept.fullname AS department_name,
                       admin.CONm AS admin_name
                FROM keselamatan.utils_tbl_gambar request
                LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = request.ICNO
                LEFT JOIN hronline.department dept ON dept.id = staff.DeptId
                LEFT JOIN hronline.tblprcobiodata admin ON admin.ICNO = request.app_by
                WHERE request.id = :id
                  AND request.type = 1
                LIMIT 1
                SQL,
                [':id' => $id],
            )->queryOne();
        } catch (\Throwable) {
            return null;
        }

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function pendingAdminRecord(string $id): ?array
    {
        try {
            $row = $this->db->createCommand(
                <<<SQL
                SELECT *
                FROM keselamatan.utils_tbl_gambar
                WHERE id = :id
                  AND type = 1
                  AND status IS NULL
                LIMIT 1
                SQL,
                [':id' => $id],
            )->queryOne();
        } catch (\Throwable) {
            return null;
        }

        return is_array($row) ? $row : null;
    }

    /**
     * @return array{success: bool, message: string, redirect: string}
     */
    private function rejectRequest(string $id, string $adminIcno, string $remarks): array
    {
        try {
            $updated = (int) $this->db->transaction(function () use ($id, $adminIcno, $remarks): int {
                return $this->db->createCommand()->update(self::TABLE, [
                    'app_by' => $adminIcno,
                    'app_dt' => date('Y-m-d H:i:s'),
                    'status_label' => 3,
                    'curr_process' => 0,
                    'status' => 2,
                    'catatan' => $remarks,
                ], [
                    'id' => $id,
                    'type' => 1,
                    'status' => null,
                ])->execute();
            });
        } catch (\Throwable) {
            return ['success' => false, 'message' => 'Permohonan tidak berjaya ditolak.', 'redirect' => $this->adminReviewUrl($id)];
        }

        if ($updated !== 1) {
            return ['success' => false, 'message' => 'Permohonan ini telah selesai atau tidak dijumpai.', 'redirect' => '/gambar-profile/senarai'];
        }

        return ['success' => true, 'message' => 'Permohonan penukaran gambar profil telah ditolak.', 'redirect' => '/gambar-profile/senarai'];
    }

    /**
     * @return array{success: bool, message: string, redirect: string}
     */
    private function approveRequest(string $id, string $adminIcno, string $remarks): array
    {
        try {
            $result = $this->db->transaction(function () use ($id, $adminIcno, $remarks): array {
                $row = $this->db->createCommand(
                    <<<SQL
                    SELECT *
                    FROM keselamatan.utils_tbl_gambar
                    WHERE id = :id
                      AND type = 1
                      AND status IS NULL
                    LIMIT 1
                    FOR UPDATE
                    SQL,
                    [':id' => $id],
                )->queryOne();

                if (!is_array($row)) {
                    return ['success' => false, 'message' => 'Permohonan ini telah selesai atau tidak dijumpai.'];
                }

                $staffIcno = $this->value($row['ICNO'] ?? null, '');
                $newHash = $this->value($row['gambar'] ?? null, '');
                $backupHash = null;

                $readinessError = $this->bkumsPhotoService->publishReadinessError();
                if ($readinessError !== null) {
                    return ['success' => false, 'message' => $readinessError];
                }

                $backup = $this->bkumsPhotoService->backupCurrentPhoto($staffIcno);
                if ($backup['success'] === true && isset($backup['hash'])) {
                    $backupHash = (string) $backup['hash'];
                }

                $publish = $this->bkumsPhotoService->publishMediaHostPhoto($newHash, $staffIcno);
                if ($publish['success'] !== true) {
                    return [
                        'success' => false,
                        'message' => $publish['message'] !== '' ? $publish['message'] : 'Penerbitan gambar ke BKUMS tidak berjaya.',
                    ];
                }

                $fields = [
                    'app_by' => $adminIcno,
                    'app_dt' => date('Y-m-d H:i:s'),
                    'status' => 1,
                    'status_label' => 4,
                    'curr_process' => 0,
                    'catatan' => $remarks,
                ];
                if ($backupHash !== null && $backupHash !== '') {
                    $fields['gambar_lama'] = $backupHash;
                }

                $updated = (int) $this->db->createCommand()->update(self::TABLE, $fields, [
                    'id' => $id,
                    'type' => 1,
                    'status' => null,
                ])->execute();

                if ($updated !== 1) {
                    return ['success' => false, 'message' => 'Permohonan ini telah selesai atau tidak dijumpai.'];
                }

                return ['success' => true, 'message' => 'Permohonan penukaran gambar profil telah diluluskan.'];
            });
        } catch (\Throwable) {
            return ['success' => false, 'message' => 'Permohonan tidak berjaya diluluskan.', 'redirect' => $this->adminReviewUrl($id)];
        }

        return [
            'success' => (bool) ($result['success'] ?? false),
            'message' => is_scalar($result['message'] ?? null) ? (string) $result['message'] : 'Permohonan tidak berjaya diluluskan.',
            'redirect' => ($result['success'] ?? false) === true ? '/gambar-profile/senarai' : $this->adminReviewUrl($id),
        ];
    }

    private function adminReviewUrl(string $id): string
    {
        return '/gambar-profile/semakan-gambar?id=' . rawurlencode($id);
    }

    private function hasPendingRequest(string $icno): bool
    {
        try {
            return (int) $this->db->createCommand(
                'SELECT COUNT(*) FROM ' . self::TABLE . ' WHERE ICNO = :icno AND status IS NULL',
                [':icno' => $icno],
            )->queryScalar() > 0;
        } catch (\Throwable) {
            return true;
        }
    }

    private function validateImageFile(?UploadedFileInterface $file): ?string
    {
        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return 'Gambar profil diperlukan.';
        }

        if ($file->getError() !== UPLOAD_ERR_OK) {
            return 'Gambar profil tidak berjaya dimuat naik.';
        }

        if (($file->getSize() ?? 0) > self::MAX_IMAGE_SIZE) {
            return 'Saiz gambar tidak boleh melebihi 2MB.';
        }

        $filename = strtolower((string) $file->getClientFilename());
        if (!preg_match('/\.jpe?g$/', $filename)) {
            return 'Format fail tidak terpakai. Hanya fail JPEG yang disokong.';
        }

        $mediaType = strtolower((string) $file->getClientMediaType());
        if ($mediaType !== '' && !in_array($mediaType, ['image/jpeg', 'image/pjpeg'], true)) {
            return 'Format fail tidak terpakai. Hanya fail JPEG yang disokong.';
        }

        return $this->validateReadableImage($file);
    }

    private function validateReadableImage(UploadedFileInterface $file): ?string
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'profile_photo_check_');
        if ($tempPath === false) {
            return 'Fail sementara tidak dapat disediakan.';
        }

        try {
            $stream = $file->getStream();
            if ($stream->isSeekable()) {
                $stream->rewind();
            }

            file_put_contents($tempPath, $stream->getContents());

            if ($stream->isSeekable()) {
                $stream->rewind();
            }

            $info = @getimagesize($tempPath);
            if ($info === false || ($info['mime'] ?? '') !== 'image/jpeg') {
                return 'Fail JPEG tidak sah atau tidak boleh dibaca.';
            }
        } finally {
            if (is_file($tempPath)) {
                unlink($tempPath);
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function statusLabel(array $row): string
    {
        $status = $this->value($row['status'] ?? null, '');
        if ($status === '1') {
            return 'Diluluskan';
        }

        if ($status === '2') {
            return 'Ditolak';
        }

        return 'Permohonan Baru';
    }

    private function bkumsPhotoUrl(string $icno): string
    {
        return 'https://bkums.ums.edu.my/public/picstf/' . strtoupper(sha1($icno)) . '.jpeg';
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
}
