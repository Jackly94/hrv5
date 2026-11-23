<?php

declare(strict_types=1);

namespace App\Shared\Biodata\Sections;

use App\Shared\AuditTrailService;
use App\Shared\BiodataTouchService;
use App\Shared\EmployeeRepository;
use App\Shared\MediaHostFileService;
use App\Shared\RecordStatService;
use App\Shared\ReferenceDataService;
use Psr\Http\Message\UploadedFileInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class AkaunCrudService
{
    private const TABLE = 'hronline.tbl_akaun';

    /**
     * @var list<array<string, mixed>>
     */
    private const FIELDS = [
        ['name' => 'nama_pemilik', 'label' => 'Nama Pemilik Akaun', 'type' => 'text', 'required' => true],
        ['name' => 'ic_pemilik', 'label' => 'IC/Paspot Pemilik Akaun', 'type' => 'text', 'required' => true],
        ['name' => 'no_akaun', 'label' => 'No. Akaun', 'type' => 'text', 'required' => true],
        ['name' => 'kod_bank', 'label' => 'Bank', 'type' => 'select', 'options' => 'banks', 'required' => true],
        ['name' => 'status', 'label' => 'Status Akaun', 'type' => 'select', 'options' => 'activeInactive', 'required' => true],
        ['name' => 'remark', 'label' => 'Catatan', 'type' => 'textarea', 'required' => false],
        ['name' => 'attachment', 'label' => 'Lampiran Akaun', 'type' => 'file', 'required' => false],
    ];

    public function __construct(
        private ConnectionInterface $db,
        private MediaHostFileService $fileService,
        private AuditTrailService $auditTrail,
        private RecordStatService $recordStat,
        private BiodataTouchService $biodataTouch,
        private ReferenceDataService $referenceData,
        private EmployeeRepository $employees,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function form(string $icno, ?string $id = null): ?array
    {
        $staff = $this->employees->byIcno($icno);
        if ($staff === null) {
            return null;
        }

        $values = $id === null || $id === ''
            ? $this->defaultValues()
            : $this->valuesForEdit($icno, $id);

        if ($values === null) {
            return null;
        }

        return [
            'key' => 'account',
            'title' => 'Akaun',
            'name' => $this->titleCase($staff['CONm'] ?? null),
            'backUrl' => '/biodata/akaun/senarai-akaun',
            'action' => $id === null || $id === ''
                ? '/biodata/akaun/admin-tambah'
                : '/biodata/akaun/admin-kemaskini?id=' . rawurlencode($id),
            'fields' => self::FIELDS,
            'options' => $this->options(),
            'values' => $values,
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, UploadedFileInterface|null> $files
     * @return array{success: bool, values: array<string, string>, errors: array<string, string>, id?: string}
     */
    public function save(string $icno, array $input, array $files, ?string $id, string $ipAddress): array
    {
        $existing = $id === null || $id === '' ? null : $this->rawRowForIcno($icno, $id);
        if ($id !== null && $id !== '' && $existing === null) {
            return ['success' => false, 'values' => $this->normalizeValues($input, []), 'errors' => ['general' => 'Rekod akaun tidak dijumpai.']];
        }

        $values = $this->normalizeValues($input, $existing ?? []);
        $errors = $this->validate($values, $files);
        if ($errors !== []) {
            return ['success' => false, 'values' => $values, 'errors' => $errors];
        }

        $record = $this->recordFromValues($icno, $values);
        $uploadErrors = $this->applyUploads($icno, $record, $files);
        if ($uploadErrors !== []) {
            return ['success' => false, 'values' => $values, 'errors' => $uploadErrors];
        }

        $now = date('Y-m-d H:i:s');
        $savedId = $this->db->transaction(function () use ($icno, $id, $record, $existing, $now, $ipAddress): string {
            $record['update_date'] = date('Y-m-d');
            $record['update_by'] = $icno;

            if ($id === null || $id === '') {
                $this->db->createCommand()->insert(self::TABLE, $record)->execute();
                $savedId = (string) $this->db->getLastInsertId();
                $this->recordStat->insertPending($icno, self::TABLE, $savedId, $now);
                $this->auditTrail->logCreate($icno, self::TABLE, $savedId, $this->auditTrail->createChanges($record), $ipAddress, 'biodata/akaun/admin-tambah', $now);
            } else {
                $this->db->createCommand()->update(self::TABLE, $record, ['id' => $id, 'icno' => $icno])->execute();
                $savedId = $id;
                $changes = $this->auditTrail->updateChanges($existing ?? [], $record);
                if ($changes !== []) {
                    $this->recordStat->upsertUpdated($icno, self::TABLE, $savedId, $now);
                    $this->auditTrail->logUpdate($icno, self::TABLE, $savedId, $changes, $ipAddress, 'biodata/akaun/admin-kemaskini', $now);
                }
            }

            $this->biodataTouch->touch($icno, $icno, $now);

            return $savedId;
        });

        return ['success' => true, 'values' => $values, 'errors' => [], 'id' => $savedId];
    }

    /**
     * @return array<string, string>
     */
    private function defaultValues(): array
    {
        $values = [];
        foreach (self::FIELDS as $field) {
            $values[$field['name']] = '';
        }

        return $values;
    }

    /**
     * @return array<string, string>|null
     */
    private function valuesForEdit(string $icno, string $id): ?array
    {
        $row = $this->rawRowForIcno($icno, $id);
        if ($row === null) {
            return null;
        }

        $values = [];
        foreach (self::FIELDS as $field) {
            $name = $field['name'];
            $values[$name] = $this->value($row[$name] ?? null, '');
        }

        return $values;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function rawRowForIcno(string $icno, string $id): ?array
    {
        $row = $this->db->createCommand(
            'SELECT * FROM ' . self::TABLE . ' WHERE icno = :icno AND id = :id LIMIT 1',
            [':icno' => $icno, ':id' => $id],
        )->queryOne();

        return is_array($row) ? $row : null;
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $existing
     * @return array<string, string>
     */
    private function normalizeValues(array $input, array $existing): array
    {
        $values = [];
        foreach (self::FIELDS as $field) {
            $name = $field['name'];
            $values[$name] = ($field['type'] ?? '') === 'file'
                ? $this->value($existing[$name] ?? null, '')
                : $this->formValue($input[$name] ?? null);
        }

        return $values;
    }

    /**
     * @param array<string, string> $values
     * @param array<string, UploadedFileInterface|null> $files
     * @return array<string, string>
     */
    private function validate(array $values, array $files): array
    {
        $errors = [];
        foreach (self::FIELDS as $field) {
            $name = $field['name'];
            if (($field['required'] ?? false) && ($field['type'] ?? '') !== 'file' && ($values[$name] ?? '') === '') {
                $errors[$name] = $field['label'] . ' diperlukan.';
            }

            if (($field['type'] ?? '') === 'file') {
                $fileError = $this->validateUploadedFile($files[$name] ?? null);
                if ($fileError !== null) {
                    $errors[$name] = $fileError;
                }
            }
        }

        return $errors;
    }

    /**
     * @param array<string, string> $values
     * @return array<string, mixed>
     */
    private function recordFromValues(string $icno, array $values): array
    {
        $record = ['icno' => $icno];
        foreach (self::FIELDS as $field) {
            $name = $field['name'];
            $record[$name] = $values[$name] ?? '';
        }

        return $record;
    }

    /**
     * @param array<string, mixed> $record
     * @param array<string, UploadedFileInterface|null> $files
     * @return array<string, string>
     */
    private function applyUploads(string $icno, array &$record, array $files): array
    {
        $file = $files['attachment'] ?? null;
        if (!$file instanceof UploadedFileInterface || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return [];
        }

        $upload = $this->fileService->uploadDocument($file, 'Maklumat Rekod Kakitangan/akaun', $icno);
        if ($upload['success'] !== true) {
            return ['attachment' => $upload['message'] ?? 'Muat naik dokumen gagal.'];
        }

        $record['attachment'] = (string) $upload['hash'];

        return [];
    }

    private function validateUploadedFile(?UploadedFileInterface $file): ?string
    {
        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
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

    /**
     * @return array<string, list<array{id: string, name: string}>>
     */
    private function options(): array
    {
        return [
            'activeInactive' => [['id' => '1', 'name' => 'Aktif'], ['id' => '0', 'name' => 'Tidak Aktif']],
            'banks' => $this->referenceData->banks(),
        ];
    }

    private function formValue(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
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
