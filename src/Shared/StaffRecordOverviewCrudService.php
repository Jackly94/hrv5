<?php

declare(strict_types=1);

namespace App\Shared;

use Psr\Http\Message\UploadedFileInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class StaffRecordOverviewCrudService
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private const CONFIGS = [
        'pendidikan' => [
            'title' => 'Pendidikan',
            'backUrl' => '/pendidikan/view',
            'table' => 'hronline.tblpreduach',
            'idColumn' => 'id',
            'icnoColumn' => 'ICNO',
            'createPath' => '/pendidikan/tambahpendidikan',
            'updatePath' => '/pendidikan/update',
            'deletePath' => '/pendidikan/delete',
            'directory' => 'Maklumat Rekod Kakitangan/Pendidikan',
            'fields' => [
                ['name' => 'InstCd', 'label' => 'Institut', 'type' => 'select', 'options' => 'institutions', 'required' => true],
                ['name' => 'HighestEduLevelCd', 'label' => 'Tahap Pendidikan', 'type' => 'select', 'options' => 'educationLevels', 'required' => true],
                ['name' => 'MajorCd', 'label' => 'Major', 'type' => 'select', 'options' => 'majors', 'required' => false],
                ['name' => 'EduCertTitle', 'label' => 'Nama Sijil', 'type' => 'text', 'required' => true],
                ['name' => 'ConfermentDt', 'label' => 'Tarikh Dianugerahkan', 'type' => 'date', 'required' => true],
                ['name' => 'StartEduDt', 'label' => 'Tarikh Mula Pengajian', 'type' => 'date', 'required' => false],
                ['name' => 'AcrtdEduAch', 'label' => 'Status Pengiktirafan', 'type' => 'select', 'options' => 'yesNo', 'required' => false],
                ['name' => 'filename', 'label' => 'Fail', 'type' => 'file', 'required' => true],
            ],
        ],
        'passport' => [
            'title' => 'Paspot',
            'backUrl' => '/pasport-permit/view',
            'table' => 'hronline.tblprpassport',
            'idColumn' => 'id',
            'icnoColumn' => 'ICNO',
            'createPath' => '/pasport-permit/tambahpasport',
            'updatePath' => '/pasport-permit/updatepasport',
            'deletePath' => '/pasport-permit/deletepaspot',
            'directory' => 'Maklumat Rekod Kakitangan/pasport',
            'extraCreate' => ['isActive' => 1],
            'fields' => [
                ['name' => 'PassportNo', 'label' => 'Nombor Paspot', 'type' => 'text', 'required' => true],
                ['name' => 'PassportTypeCd', 'label' => 'Jenis Paspot', 'type' => 'select', 'options' => 'passportTypes', 'required' => true],
                ['name' => 'CountryCd', 'label' => 'Warganegara', 'type' => 'select', 'options' => 'countries', 'required' => true],
                ['name' => 'StateCd', 'label' => 'Tempat Lahir', 'type' => 'select', 'options' => 'states', 'required' => false],
                ['name' => 'IssuedDt', 'label' => 'Tarikh Dikeluarkan', 'type' => 'date', 'required' => true],
                ['name' => 'PassportExpiryDt', 'label' => 'Tarikh Luput', 'type' => 'date', 'required' => true],
                ['name' => 'filename', 'label' => 'Fail', 'type' => 'file', 'required' => false],
            ],
        ],
        'permit' => [
            'title' => 'Permit Kerja',
            'backUrl' => '/pasport-permit/view',
            'table' => 'hronline.tblprwrkpermit',
            'idColumn' => 'id',
            'icnoColumn' => 'ICNO',
            'createPath' => '/pasport-permit/tambahpermitkerja',
            'updatePath' => '/pasport-permit/updatepermitkerja',
            'deletePath' => '/pasport-permit/deletepermit',
            'directory' => 'Maklumat Rekod Kakitangan/permitkerja',
            'fields' => [
                ['name' => 'WrkPermitNo', 'label' => 'No. Permit Kerja', 'type' => 'text', 'required' => true],
                ['name' => 'ImigRefNo', 'label' => 'No. Rujukan Imigresen', 'type' => 'text', 'required' => false],
                ['name' => 'WrkPermitIssueDt', 'label' => 'Tarikh Dikeluarkan', 'type' => 'date', 'required' => true],
                ['name' => 'WrkPermitExpiryDt', 'label' => 'Tarikh Luput', 'type' => 'date', 'required' => true],
                ['name' => 'filename', 'label' => 'Fail', 'type' => 'file', 'required' => false],
            ],
        ],
        'account' => [
            'title' => 'Akaun',
            'backUrl' => '/akaun/senarai-akaun',
            'table' => 'hronline.tbl_akaun',
            'idColumn' => 'id',
            'icnoColumn' => 'icno',
            'createPath' => '/akaun/permohonan-penukaran-akaun-gaji',
            'updatePath' => '/akaun/permohonan-penukaran-akaun-gaji',
            'deletePath' => '',
            'directory' => 'Maklumat Rekod Kakitangan/akaun',
            'touchColumns' => ['update_date' => 'date', 'update_by' => 'icno'],
            'fields' => [
                ['name' => 'nama_pemilik', 'label' => 'Nama Pemilik Akaun', 'type' => 'text', 'required' => true],
                ['name' => 'ic_pemilik', 'label' => 'IC/Paspot Pemilik Akaun', 'type' => 'text', 'required' => true],
                ['name' => 'no_akaun', 'label' => 'No. Akaun', 'type' => 'text', 'required' => true],
                ['name' => 'kod_bank', 'label' => 'Bank', 'type' => 'select', 'options' => 'banks', 'required' => true],
                ['name' => 'status', 'label' => 'Status Akaun', 'type' => 'select', 'options' => 'activeInactive', 'required' => true],
                ['name' => 'remark', 'label' => 'Catatan', 'type' => 'textarea', 'required' => false],
                ['name' => 'attachment', 'label' => 'Lampiran Akaun', 'type' => 'file', 'required' => false],
            ],
        ],
        'family' => [
            'title' => 'Keluarga',
            'backUrl' => '/keluarga/view',
            'table' => 'hronline.tblprfmy',
            'idColumn' => 'id',
            'icnoColumn' => 'ICNO',
            'createPath' => '/keluarga/ums-nonums',
            'updatePath' => '/keluarga/update',
            'deletePath' => '/keluarga/delete',
            'directory' => 'Maklumat Rekod Kakitangan/keluarga/oku',
            'fields' => [
                ['name' => 'FamilyId', 'label' => 'No. Kad Pengenalan', 'type' => 'text', 'required' => true],
                ['name' => 'FmyNm', 'label' => 'Nama', 'type' => 'text', 'required' => true],
                ['name' => 'RelCd', 'label' => 'Hubungan', 'type' => 'select', 'options' => 'relationships', 'required' => true],
                ['name' => 'FmyDisabilityStatus', 'label' => 'Status OKU', 'type' => 'select', 'options' => 'yesNo', 'required' => false],
                ['name' => 'dis_filename_kad_oku', 'label' => 'Kad OKU', 'type' => 'file', 'required' => false],
                ['name' => 'dis_filename_sijil_lahir', 'label' => 'Sijil Lahir', 'type' => 'file', 'required' => false],
                ['name' => 'dis_filename_kad_temujanji', 'label' => 'Kad Temujanji', 'type' => 'file', 'required' => false],
                ['name' => 'dis_filename_pengesahan_dr', 'label' => 'Pengesahan Doktor', 'type' => 'file', 'required' => false],
            ],
        ],
        'expertise' => [
            'title' => 'Bidang Kepakaran',
            'backUrl' => '/bidangkepakaran/view',
            'table' => 'hronline.tblprcobidangkepakaran',
            'idColumn' => 'id',
            'icnoColumn' => 'ICNO',
            'createPath' => '/bidangkepakaran/tambahbidangkepakaran',
            'updatePath' => '/bidangkepakaran/update',
            'deletePath' => '/bidangkepakaran/delete',
            'directory' => 'Maklumat Rekod Kakitangan/Bidang Kepakaran',
            'fields' => [
                ['name' => 'klusterid', 'label' => 'Kluster Kepakaran', 'type' => 'select', 'options' => 'expertiseClusters', 'required' => true],
                ['name' => 'bidang', 'label' => 'Bidang Kepakaran', 'type' => 'textarea', 'required' => true],
                ['name' => 'filename', 'label' => 'Fail', 'type' => 'file', 'required' => false],
            ],
        ],
        'vaccine-status' => [
            'title' => 'Status Vaksinasi',
            'backUrl' => '/vaksinasi/index',
            'table' => 'hronline.tblstatusvaksinasi',
            'idColumn' => 'id',
            'icnoColumn' => 'icno',
            'createPath' => '/vaksinasi/view-st-vaksinasi',
            'updatePath' => '/vaksinasi/update-status-vaksinasi',
            'deletePath' => '',
            'directory' => 'Maklumat Rekod Kakitangan/vaksinasi',
            'fields' => [
                ['name' => 'status_vaksin', 'label' => 'Status Vaksin', 'type' => 'select', 'options' => 'yesNo', 'required' => true],
                ['name' => 'terima_dos1', 'label' => 'Dos Pertama', 'type' => 'select', 'options' => 'yesNo', 'required' => false],
                ['name' => 'terima_dos2', 'label' => 'Dos Kedua', 'type' => 'select', 'options' => 'yesNo', 'required' => false],
                ['name' => 'terima_penggalak', 'label' => 'Dos Penggalak', 'type' => 'select', 'options' => 'yesNo', 'required' => false],
                ['name' => 'catatan', 'label' => 'Catatan', 'type' => 'textarea', 'required' => false],
                ['name' => 'sijil_digital', 'label' => 'Sijil Digital', 'type' => 'file', 'required' => false],
                ['name' => 'lampiran', 'label' => 'Lampiran Sokongan', 'type' => 'file', 'required' => false],
            ],
        ],
        'vaccine-dose' => [
            'title' => 'Dos Vaksinasi',
            'backUrl' => '/vaksinasi/index',
            'table' => 'hronline.tblpenerima_vaksin',
            'idColumn' => 'id',
            'icnoColumn' => 'icno',
            'createPath' => '/vaksinasi/update-st-vaksinasi',
            'updatePath' => '/vaksinasi/update-st-vaksinasi',
            'deletePath' => '',
            'directory' => '',
            'fields' => [
                ['name' => 'bil_dos', 'label' => 'Bil. Dos', 'type' => 'number', 'required' => true],
                ['name' => 'tarikh_vaksin', 'label' => 'Tarikh Vaksin', 'type' => 'date', 'required' => true],
                ['name' => 'jenis_vaksin', 'label' => 'Jenis Vaksin', 'type' => 'select', 'options' => 'vaccineTypes', 'required' => true],
                ['name' => 'tempat_vaksin', 'label' => 'Tempat Vaksin', 'type' => 'text', 'required' => false],
                ['name' => 'batch_vaksin', 'label' => 'Batch Vaksin', 'type' => 'text', 'required' => false],
            ],
        ],
    ];

    public function __construct(
        private ConnectionInterface $db,
        private MediaHostFileService $fileService,
        private StaffDateFormatter $dateFormatter,
        private AuditTrailService $auditTrail,
        private RecordStatService $recordStat,
        private BiodataTouchService $biodataTouch,
        private ReferenceDataService $referenceData,
        private EmployeeRepository $employees,
    ) {}

    public function keyFromPath(string $path): ?string
    {
        return match ($path) {
            '/pendidikan/tambahpendidikan', '/pendidikan/update', '/pendidikan/delete' => 'pendidikan',
            '/pasport-permit/tambahpasport', '/pasport-permit/updatepasport', '/pasport-permit/deletepaspot' => 'passport',
            '/pasport-permit/tambahpermitkerja', '/pasport-permit/updatepermitkerja', '/pasport-permit/deletepermit' => 'permit',
            '/akaun/permohonan-penukaran-akaun-gaji' => 'account',
            '/keluarga/ums-nonums', '/keluarga/update', '/keluarga/delete' => 'family',
            '/bidangkepakaran/tambahbidangkepakaran', '/bidangkepakaran/update', '/bidangkepakaran/delete' => 'expertise',
            '/vaksinasi/view-st-vaksinasi', '/vaksinasi/update-status-vaksinasi' => 'vaccine-status',
            '/vaksinasi/update-st-vaksinasi' => 'vaccine-dose',
            default => null,
        };
    }

    public function config(string $key): ?array
    {
        return self::CONFIGS[$key] ?? null;
    }

    public function isUpdatePath(string $key, string $path, string $id): bool
    {
        $config = $this->config($key);

        return $config !== null && $id !== '' && $path === $config['updatePath'];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function form(string $key, string $icno, ?string $id = null): ?array
    {
        $config = $this->config($key);
        $staff = $this->employees->byIcno($icno);
        if ($config === null || $staff === null) {
            return null;
        }

        $values = $id === null || $id === ''
            ? $this->defaultValues($config)
            : $this->valuesForEdit($config, $icno, $id);

        if ($values === null) {
            return null;
        }

        return [
            'key' => $key,
            'title' => $config['title'],
            'name' => $this->titleCase($staff['CONm'] ?? null),
            'backUrl' => $config['backUrl'],
            'action' => ($id === null || $id === '' ? $config['createPath'] : $config['updatePath'] . '?id=' . rawurlencode($id)),
            'fields' => $config['fields'],
            'options' => $this->options(),
            'values' => $values,
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, UploadedFileInterface|null> $files
     * @return array{success: bool, values: array<string, string>, errors: array<string, string>, id?: string}
     */
    public function save(string $key, string $icno, array $input, array $files, ?string $id, string $ipAddress): array
    {
        $config = $this->config($key);
        if ($config === null) {
            return ['success' => false, 'values' => [], 'errors' => ['general' => 'Konfigurasi rekod tidak dijumpai.']];
        }

        $existing = $id === null || $id === '' ? null : $this->rawRowForIcno($config, $icno, $id);
        if ($id !== null && $id !== '' && $existing === null) {
            return ['success' => false, 'values' => $this->normalizeValues($config, $input, []), 'errors' => ['general' => 'Rekod tidak dijumpai.']];
        }

        $values = $this->normalizeValues($config, $input, $existing ?? []);
        $errors = $this->validate($config, $values, $files, $id === null || $id === '');
        if ($errors !== []) {
            return ['success' => false, 'values' => $values, 'errors' => $errors];
        }

        $record = $this->recordFromValues($config, $icno, $values);
        $uploadErrors = $this->applyUploads($config, $icno, $record, $files);
        if ($uploadErrors !== []) {
            return ['success' => false, 'values' => $values, 'errors' => $uploadErrors];
        }

        $now = date('Y-m-d H:i:s');
        $savedId = $this->db->transaction(function () use ($config, $icno, $id, $record, $existing, $now, $ipAddress): string {
            $table = $config['table'];
            $idColumn = $config['idColumn'];
            if (isset($config['touchColumns']) && is_array($config['touchColumns'])) {
                foreach ($config['touchColumns'] as $column => $source) {
                    $record[$column] = $source === 'date' ? date('Y-m-d') : $icno;
                }
            }

            if ($id === null || $id === '') {
                $record = [...$record, ...($config['extraCreate'] ?? [])];
                $this->db->createCommand()->insert($table, $record)->execute();
                $savedId = $this->db->getLastInsertId();
                $this->recordStat->insertPending($icno, $table, $savedId, $now);
                $this->auditTrail->logCreate($icno, $table, $savedId, $this->auditTrail->createChanges($record), $ipAddress, $config['createPath'], $now);
            } else {
                $this->db->createCommand()->update($table, $record, [$idColumn => $id, $config['icnoColumn'] => $icno])->execute();
                $savedId = $id;
                $changes = $this->auditTrail->updateChanges($existing ?? [], $record);
                if ($changes !== []) {
                    $this->recordStat->upsertUpdated($icno, $table, $savedId, $now);
                    $this->auditTrail->logUpdate($icno, $table, $savedId, $changes, $ipAddress, $config['updatePath'], $now);
                }
            }

            $this->biodataTouch->touch($icno, $icno, $now);

            return $savedId;
        });

        return ['success' => true, 'values' => $values, 'errors' => [], 'id' => $savedId];
    }

    public function delete(string $key, string $icno, string $id, string $ipAddress): bool
    {
        $config = $this->config($key);
        if ($config === null || ($config['deletePath'] ?? '') === '') {
            return false;
        }

        $old = $this->rawRowForIcno($config, $icno, $id);
        if ($old === null) {
            return false;
        }

        foreach ($this->fileFields($config) as $field) {
            $hash = $this->value($old[$field['name']] ?? null, '');
            if ($hash !== '' && $hash !== 'deleted') {
                $delete = $this->fileService->deleteDocument($hash, $icno);
                if ($delete['success'] !== true) {
                    return false;
                }
            }
        }

        $now = date('Y-m-d H:i:s');
        $this->db->transaction(function () use ($config, $icno, $id, $old, $now, $ipAddress): void {
            $this->auditTrail->logDelete($icno, $config['table'], $id, $this->auditTrail->createChanges($old), $ipAddress, $config['deletePath'], $now);
            $this->recordStat->deleteForRecord($icno, $config['table'], $id);
            $this->db->createCommand()->delete($config['table'], [$config['idColumn'] => $id, $config['icnoColumn'] => $icno])->execute();
            $this->biodataTouch->touch($icno, $icno, $now);
        });

        return true;
    }

    private function rawRowForIcno(array $config, string $icno, string $id): ?array
    {
        $row = $this->db->createCommand(
            "SELECT * FROM {$config['table']} WHERE {$config['icnoColumn']} = :icno AND {$config['idColumn']} = :id LIMIT 1",
            [':icno' => $icno, ':id' => $id],
        )->queryOne();

        return is_array($row) ? $row : null;
    }

    private function defaultValues(array $config): array
    {
        $values = [];
        foreach ($config['fields'] as $field) {
            $values[$field['name']] = '';
        }

        return $values;
    }

    private function valuesForEdit(array $config, string $icno, string $id): ?array
    {
        $row = $this->rawRowForIcno($config, $icno, $id);
        if ($row === null) {
            return null;
        }

        $values = [];
        foreach ($config['fields'] as $field) {
            $name = $field['name'];
            $values[$name] = ($field['type'] ?? '') === 'date'
                ? $this->dateFormatter->formValue($row[$name] ?? null)
                : $this->value($row[$name] ?? null, '');
        }

        return $values;
    }

    private function normalizeValues(array $config, array $input, array $existing): array
    {
        $values = [];
        foreach ($config['fields'] as $field) {
            $name = $field['name'];
            $type = $field['type'] ?? 'text';
            $values[$name] = $type === 'file'
                ? $this->value($existing[$name] ?? null, '')
                : $this->formValue($input[$name] ?? null);
        }

        return $values;
    }

    private function recordFromValues(array $config, string $icno, array $values): array
    {
        $record = [$config['icnoColumn'] => $icno];
        foreach ($config['fields'] as $field) {
            $name = $field['name'];
            $value = $values[$name] ?? '';
            $record[$name] = ($field['type'] ?? '') === 'date' && $value === '' ? null : $value;
        }

        return $record;
    }

    private function validate(array $config, array $values, array $files, bool $isCreate): array
    {
        $errors = [];
        foreach ($config['fields'] as $field) {
            $name = $field['name'];
            if (($field['required'] ?? false) && ($field['type'] ?? '') !== 'file' && ($values[$name] ?? '') === '') {
                $errors[$name] = $field['label'] . ' diperlukan.';
            }

            if (($field['type'] ?? '') === 'date' && ($values[$name] ?? '') !== '' && !$this->dateFormatter->isValidFormDate($values[$name])) {
                $errors[$name] = $field['label'] . ' tidak sah.';
            }

            if (($field['type'] ?? '') === 'file') {
                $file = $files[$name] ?? null;
                $fileError = $this->validateUploadedFile($file, (bool) ($field['required'] ?? false) && $isCreate, $values[$name] ?? '');
                if ($fileError !== null) {
                    $errors[$name] = $fileError;
                }
            }
        }

        return $errors;
    }

    private function applyUploads(array $config, string $icno, array &$record, array $files): array
    {
        $errors = [];
        foreach ($this->fileFields($config) as $field) {
            $name = $field['name'];
            $file = $files[$name] ?? null;
            if (!$file instanceof UploadedFileInterface || $file->getError() === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $upload = $this->fileService->uploadDocument($file, (string) ($config['directory'] ?? 'Maklumat Rekod Kakitangan'), $icno);
            if ($upload['success'] !== true) {
                $errors[$name] = $upload['message'] ?? 'Muat naik dokumen gagal.';
                continue;
            }

            $record[$name] = (string) $upload['hash'];
        }

        return $errors;
    }

    private function validateUploadedFile(?UploadedFileInterface $file, bool $required, string $existingFile): ?string
    {
        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return $required && ($existingFile === '' || $existingFile === 'deleted')
                ? 'Sila muat naik dokumen berkaitan.'
                : null;
        }

        if ($file->getError() !== UPLOAD_ERR_OK) {
            return 'Dokumen tidak berjaya dimuat naik.';
        }

        if (($file->getSize() ?? 0) > 6 * 1024 * 1024) {
            return 'Saiz dokumen tidak boleh melebihi 6.0 MB.';
        }

        $filename = strtolower((string) $file->getClientFilename());
        if (!preg_match('/\.(pdf|jpg|jpeg|png)$/', $filename)) {
            return 'Sila muat naik fail PDF, JPG, JPEG atau PNG.';
        }

        return null;
    }

    private function fileFields(array $config): array
    {
        return array_values(array_filter($config['fields'], static fn (array $field): bool => ($field['type'] ?? '') === 'file'));
    }

    private function options(): array
    {
        return [
            'yesNo' => [['id' => '1', 'name' => 'Ya'], ['id' => '0', 'name' => 'Tidak']],
            'activeInactive' => [['id' => '1', 'name' => 'Aktif'], ['id' => '0', 'name' => 'Tidak Aktif']],
            'institutions' => $this->referenceData->institutions(),
            'educationLevels' => $this->referenceData->educationLevels(),
            'majors' => $this->referenceData->majors(),
            'passportTypes' => $this->referenceData->passportTypes(),
            'countries' => $this->referenceData->countries(),
            'states' => $this->referenceData->options('SELECT StateCd, State FROM hronline.state ORDER BY State', 'StateCd', 'State'),
            'expertiseClusters' => $this->referenceData->expertiseClusters(),
            'relationships' => $this->referenceData->relationships(),
            'vaccineTypes' => $this->referenceData->vaccineTypes(),
            'banks' => $this->referenceData->banks(),
        ];
    }

    private function formValue(mixed $value): string
    {
        if (!is_scalar($value)) {
            return '';
        }

        return trim((string) $value);
    }

    private function titleCase(mixed $value): string
    {
        $value = $this->value($value, '');

        return $value === '' ? 'Tiada Rekod' : ucwords(strtolower($value));
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
