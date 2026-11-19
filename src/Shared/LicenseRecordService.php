<?php

declare(strict_types=1);

namespace App\Shared;

use Psr\Http\Message\UploadedFileInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class LicenseRecordService
{
    private const TABLE = 'hronline.tblprlic';

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

    /**
     * @return array{name: string}|null
     */
    public function staffForIcno(string $icno): ?array
    {
        $staff = $this->staffRow($icno);
        if ($staff === null) {
            return null;
        }

        return ['name' => $this->titleCase($staff['CONm'] ?? null)];
    }

    /**
     * @return array<string, string>|null
     */
    public function detailForIcno(string $icno, string $id): ?array
    {
        $row = $this->rowForIcno($icno, $id);
        if ($row === null) {
            return null;
        }

        $file = $this->value($row['filename'] ?? null, '');

        return [
            'id' => $this->value($row['licId'] ?? null, ''),
            'licNo' => $this->value($row['LicNo'] ?? null, '-'),
            'name' => $this->value($row['Name'] ?? null, '-'),
            'type' => $this->value($row['LicType'] ?? null, '-'),
            'class' => $this->value($row['LicClass'] ?? null, '-'),
            'issuedDate' => $this->formatDate($row['FirstLicIssuedDt'] ?? null),
            'expiryDate' => $this->formatDate($row['LicExpiryDt'] ?? null),
            'fee' => 'RM ' . $this->value($row['LicRnwlFee'] ?? null, '0.00'),
            'file' => $this->fileLabel($file),
            'fileUrl' => $this->fileService->viewUrl($file),
        ];
    }

    /**
     * @return array<string, list<array{id: string, name: string}>>
     */
    public function formOptions(): array
    {
        return [
            'types' => $this->referenceData->licenseTypes(),
            'classes' => $this->referenceData->licenseClasses(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function defaultValues(string $icno): array
    {
        return [
            'id' => '',
            'icno' => $icno,
            'licNo' => '',
            'name' => '',
            'licCd' => '',
            'licClassCd' => '',
            'issuedDate' => '',
            'expiryDate' => '',
            'fee' => '',
            'filename' => '',
        ];
    }

    /**
     * @return array<string, string>|null
     */
    public function valuesForEdit(string $icno, string $id): ?array
    {
        $row = $this->rawRowForIcno($icno, $id);
        if ($row === null) {
            return null;
        }

        return [
            'id' => $this->value($row['licId'] ?? null, ''),
            'icno' => $icno,
            'licNo' => $this->value($row['LicNo'] ?? null, ''),
            'name' => $this->value($row['Name'] ?? null, ''),
            'licCd' => $this->value($row['LicCd'] ?? null, ''),
            'licClassCd' => $this->value($row['LicClassCd'] ?? null, ''),
            'issuedDate' => $this->dateValue($row['FirstLicIssuedDt'] ?? null),
            'expiryDate' => $this->dateValue($row['LicExpiryDt'] ?? null),
            'fee' => $this->value($row['LicRnwlFee'] ?? null, ''),
            'filename' => $this->value($row['filename'] ?? null, ''),
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, values: array<string, string>, errors: array<string, string>, id?: string}
     */
    public function save(string $icno, array $input, ?string $id, ?UploadedFileInterface $file, string $ipAddress): array
    {
        $values = $this->normalizeValues($icno, $input);
        $existing = null;
        if ($id !== null) {
            $existing = $this->rawRowForIcno($icno, $id);
            if ($existing === null) {
                return ['success' => false, 'values' => $values, 'errors' => ['general' => 'Rekod lesen tidak dijumpai.']];
            }
            $values['id'] = $id;
            $values['filename'] = $this->value($existing['filename'] ?? null, '');
        }

        $errors = $this->validateValues($values, $id, $file);
        if ($errors !== []) {
            return ['success' => false, 'values' => $values, 'errors' => $errors];
        }

        $filename = $values['filename'];
        if ($file !== null && $file->getError() !== UPLOAD_ERR_NO_FILE) {
            $upload = $this->fileService->uploadDocument(
                $file,
                'Maklumat Rekod Kakitangan/Lesen',
                $icno,
            );

            if ($upload['success'] !== true) {
                return [
                    'success' => false,
                    'values' => $values,
                    'errors' => ['file' => $upload['message'] ?? 'Muat naik dokumen gagal.'],
                ];
            }

            $filename = (string) $upload['hash'];
        }

        $staff = $this->staffRow($icno);
        $name = $values['name'] !== '' ? $values['name'] : $this->value($staff['CONm'] ?? null, '');
        $now = date('Y-m-d H:i:s');
        $record = [
            'ICNO' => $icno,
            'LicNo' => $values['licNo'],
            'Name' => $name,
            'LicCd' => $values['licCd'],
            'LicClassCd' => $values['licClassCd'],
            'FirstLicIssuedDt' => $values['issuedDate'],
            'LicExpiryDt' => $values['expiryDate'],
            'LicRnwlFee' => $values['fee'],
            'filename' => $filename,
        ];

        $savedId = $this->db->transaction(function () use ($icno, $id, $record, $existing, $now, $ipAddress): string {
            if ($id === null) {
                $this->db->createCommand()->insert(self::TABLE, $record)->execute();
                $savedId = $this->db->getLastInsertId();
                $this->insertStat($icno, $savedId, 0, $now);
                $this->insertUpdateLog($icno, $savedId, 0, $this->createChanges($record), $now, $ipAddress);
            } else {
                $this->db->createCommand()->update(self::TABLE, $record, ['licId' => $id, 'ICNO' => $icno])->execute();
                $savedId = $id;
                $changes = $this->updateChanges($existing ?? [], $record);
                if ($changes !== []) {
                    $this->upsertStat($icno, $savedId, 1, $now);
                    $this->insertUpdateLog($icno, $savedId, 1, $changes, $now, $ipAddress);
                }
            }

            $this->touchBiodata($icno, $now);

            return $savedId;
        });

        return ['success' => true, 'values' => $values, 'errors' => [], 'id' => $savedId];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function staffRow(string $icno): ?array
    {
        return $this->employees->byIcno($icno);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function rowForIcno(string $icno, string $id): ?array
    {
        $row = $this->db->createCommand(
            <<<SQL
            SELECT lic.*, type.LicType, class.LicClass
            FROM hronline.tblprlic lic
            LEFT JOIN hronline.license type ON type.LicCd = lic.LicCd
            LEFT JOIN hronline.licenseclass class ON class.LicClassCd = lic.LicClassCd
            WHERE lic.ICNO = :icno AND lic.licId = :id
            LIMIT 1
            SQL,
            [':icno' => $icno, ':id' => $id],
        )->queryOne();

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function rawRowForIcno(string $icno, string $id): ?array
    {
        $row = $this->db->createCommand(
            'SELECT * FROM hronline.tblprlic WHERE ICNO = :icno AND licId = :id LIMIT 1',
            [':icno' => $icno, ':id' => $id],
        )->queryOne();

        return is_array($row) ? $row : null;
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function optionsFromSql(string $sql, string $idColumn, string $nameColumn): array
    {
        $rows = $this->db->createCommand($sql)->queryAll();
        $options = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $id = $this->value($row[$idColumn] ?? null, '');
            $name = $this->value($row[$nameColumn] ?? null, '');
            if ($id !== '' && $name !== '') {
                $options[] = ['id' => $id, 'name' => $name];
            }
        }

        return $options;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    private function normalizeValues(string $icno, array $input): array
    {
        return [
            'id' => $this->formValue($input['id'] ?? null),
            'icno' => $icno,
            'licNo' => $this->formValue($input['licNo'] ?? null),
            'name' => $this->formValue($input['name'] ?? null),
            'licCd' => $this->formValue($input['licCd'] ?? null),
            'licClassCd' => $this->formValue($input['licClassCd'] ?? null),
            'issuedDate' => $this->formValue($input['issuedDate'] ?? null),
            'expiryDate' => $this->formValue($input['expiryDate'] ?? null),
            'fee' => $this->formValue($input['fee'] ?? null),
            'filename' => $this->formValue($input['filename'] ?? null),
        ];
    }

    /**
     * @param array<string, string> $values
     * @return array<string, string>
     */
    private function validateValues(array $values, ?string $id, ?UploadedFileInterface $file): array
    {
        $errors = [];
        foreach ([
            'licNo' => 'No. lesen diperlukan.',
            'licCd' => 'Jenis lesen diperlukan.',
            'licClassCd' => 'Kelas lesen diperlukan.',
            'issuedDate' => 'Tarikh dikeluarkan diperlukan.',
            'expiryDate' => 'Tarikh luput diperlukan.',
            'fee' => 'Yuran pembaharuan diperlukan.',
        ] as $field => $message) {
            if ($values[$field] === '') {
                $errors[$field] = $message;
            }
        }

        if ($values['licNo'] !== '' && strlen($values['licNo']) > 20) {
            $errors['licNo'] = 'No. lesen tidak boleh melebihi 20 aksara.';
        }

        if ($values['fee'] !== '' && !is_numeric($values['fee'])) {
            $errors['fee'] = 'Yuran pembaharuan hendaklah dalam nilai nombor.';
        }

        if ($values['issuedDate'] !== '' && !$this->isValidDate($values['issuedDate'])) {
            $errors['issuedDate'] = 'Tarikh dikeluarkan tidak sah.';
        }

        if ($values['expiryDate'] !== '' && !$this->isValidDate($values['expiryDate'])) {
            $errors['expiryDate'] = 'Tarikh luput tidak sah.';
        }

        if ($values['licNo'] !== '' && $this->licenseNumberExists($values['licNo'], $id)) {
            $errors['licNo'] = 'Nombor lesen sudah wujud.';
        }

        $fileError = $this->validateUploadedFile($file, $id === null, $values['filename']);
        if ($fileError !== null) {
            $errors['file'] = $fileError;
        }

        return $errors;
    }

    private function validateUploadedFile(?UploadedFileInterface $file, bool $required, string $existingFile): ?string
    {
        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return $required || $existingFile === '' || $existingFile === 'deleted'
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

    private function licenseNumberExists(string $licenseNo, ?string $exceptId): bool
    {
        $sql = 'SELECT 1 FROM hronline.tblprlic WHERE LicNo = :licNo';
        $params = [':licNo' => $licenseNo];
        if ($exceptId !== null) {
            $sql .= ' AND licId <> :id';
            $params[':id'] = $exceptId;
        }
        $sql .= ' LIMIT 1';

        return (bool) $this->db->createCommand($sql, $params)->queryScalar();
    }

    /**
     * @param array<string, string> $record
     * @return list<array{0: string, 1: string|null}>
     */
    private function createChanges(array $record): array
    {
        $changes = [];
        foreach ($record as $field => $value) {
            $changes[] = [$field, $value];
        }

        return $changes;
    }

    /**
     * @param array<string, mixed> $old
     * @param array<string, string> $new
     * @return list<array{0: string, 1: string, 2: string}>
     */
    private function updateChanges(array $old, array $new): array
    {
        $changes = [];
        foreach ($new as $field => $value) {
            if ((string) ($old[$field] ?? '') !== $value) {
                $changes[] = [$field, (string) ($old[$field] ?? ''), $value];
            }
        }

        return $changes;
    }

    /**
     * @param list<array<mixed>> $changes
     */
    private function insertUpdateLog(string $icno, string $id, int $activity, array $changes, string $now, string $ipAddress): void
    {
        match ($activity) {
            0 => $this->auditTrail->logCreate($icno, self::TABLE, $id, $changes, $ipAddress, 'lesen', $now),
            1 => $this->auditTrail->logUpdate($icno, self::TABLE, $id, $changes, $ipAddress, 'lesen', $now),
            2 => $this->auditTrail->logDelete($icno, self::TABLE, $id, $changes, $ipAddress, 'lesen', $now),
            default => null,
        };
    }

    private function insertStat(string $icno, string $id, int $status, string $now): void
    {
        $status === 0
            ? $this->recordStat->insertPending($icno, self::TABLE, $id, $now)
            : $this->recordStat->upsertUpdated($icno, self::TABLE, $id, $now);
    }

    private function upsertStat(string $icno, string $id, int $status, string $now): void
    {
        $this->insertStat($icno, $id, $status, $now);
    }

    private function touchBiodata(string $icno, string $now): void
    {
        $this->biodataTouch->touch($icno, $icno, $now);
    }

    private function isValidDate(string $value): bool
    {
        return $this->dateFormatter->isValidFormDate($value);
    }

    private function dateValue(mixed $value): string
    {
        return $this->dateFormatter->formValue($value);
    }

    private function formatDate(mixed $value): string
    {
        return $this->dateFormatter->display($value);
    }

    private function fileLabel(mixed $value): string
    {
        $value = $this->value($value, '');

        return $value === '' || $value === 'deleted' ? 'File not exist!' : $value;
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

    private function value(mixed $value, string $fallback = 'Tiada Rekod'): string
    {
        if ($value === null) {
            return $fallback;
        }

        $value = trim((string) $value);

        return $value === '' ? $fallback : $value;
    }
}
