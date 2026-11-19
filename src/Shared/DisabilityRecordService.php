<?php

declare(strict_types=1);

namespace App\Shared;

use Psr\Http\Message\UploadedFileInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class DisabilityRecordService
{
    private const TABLE = 'hronline.tblprdisability';

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

        return $staff === null ? null : ['name' => $this->titleCase($staff['CONm'] ?? null)];
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
            'id' => $this->value($row['id'] ?? null, ''),
            'socialWelfareNo' => $this->value($row['SocialWelfareNo'] ?? null, '-'),
            'doctorReportNo' => $this->value($row['DrRptNo'] ?? null, '-'),
            'type' => $this->value($row['DisabilityType'] ?? null, '-'),
            'cause' => $this->value($row['DisabilityCause'] ?? null, '-'),
            'disabilityDate' => $this->formatDate($row['DisabilityDt'] ?? null, 'Tidak Berkaitan'),
            'healDate' => $this->formatDate($row['HealDt'] ?? null, 'Tidak Berkaitan'),
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
            'types' => $this->referenceData->disabilityTypes(),
            'causes' => $this->referenceData->disabilityCauses(),
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
            'socialWelfareNo' => '',
            'doctorReportNo' => '',
            'disabilityTypeCd' => '',
            'disabilityCauseCd' => '',
            'disabilityDate' => '',
            'healDate' => '',
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
            'id' => $this->value($row['id'] ?? null, ''),
            'icno' => $icno,
            'socialWelfareNo' => $this->value($row['SocialWelfareNo'] ?? null, ''),
            'doctorReportNo' => $this->value($row['DrRptNo'] ?? null, ''),
            'disabilityTypeCd' => $this->value($row['DisabilityTypeCd'] ?? null, ''),
            'disabilityCauseCd' => $this->value($row['DisabilityCauseCd'] ?? null, ''),
            'disabilityDate' => $this->dateValue($row['DisabilityDt'] ?? null),
            'healDate' => $this->dateValue($row['HealDt'] ?? null),
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
                return ['success' => false, 'values' => $values, 'errors' => ['general' => 'Rekod kecacatan tidak dijumpai.']];
            }
            $values['id'] = $id;
            $values['filename'] = $this->value($existing['filename'] ?? null, '');
        }

        $errors = $this->validateValues($icno, $values, $id, $file);
        if ($errors !== []) {
            return ['success' => false, 'values' => $values, 'errors' => $errors];
        }

        $filename = $values['filename'];
        if ($file !== null && $file->getError() !== UPLOAD_ERR_NO_FILE) {
            $upload = $this->fileService->uploadDocument(
                $file,
                'Maklumat Rekod Kakitangan/Kecacatan',
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

        $now = date('Y-m-d H:i:s');
        $record = [
            'ICNO' => $icno,
            'SocialWelfareNo' => $values['socialWelfareNo'],
            'DrRptNo' => $values['doctorReportNo'],
            'DisabilityTypeCd' => $values['disabilityTypeCd'],
            'DisabilityCauseCd' => $values['disabilityCauseCd'],
            'DisabilityDt' => $values['disabilityDate'] !== '' ? $values['disabilityDate'] : null,
            'HealDt' => $values['healDate'] !== '' ? $values['healDate'] : null,
            'filename' => $filename,
        ];

        $savedId = $this->db->transaction(function () use ($icno, $id, $record, $existing, $now, $ipAddress): string {
            if ($id === null) {
                $this->db->createCommand()->insert(self::TABLE, $record)->execute();
                $savedId = $this->db->getLastInsertId();
                $this->insertStat($icno, $savedId, 0, $now);
                $this->insertUpdateLog($icno, $savedId, 0, $this->createChanges($record), $now, $ipAddress);
            } else {
                $this->db->createCommand()->update(self::TABLE, $record, ['id' => $id, 'ICNO' => $icno])->execute();
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

    public function delete(string $icno, string $id, string $ipAddress): bool
    {
        $old = $this->rawRowForIcno($icno, $id);
        if ($old === null) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $this->db->transaction(function () use ($icno, $id, $old, $now, $ipAddress): void {
            $this->insertUpdateLog($icno, $id, 2, $this->createChanges($old), $now, $ipAddress);
            $this->recordStat->deleteForRecord($icno, self::TABLE, $id);
            $this->db->createCommand()->delete(self::TABLE, ['id' => $id, 'ICNO' => $icno])->execute();
            $this->touchBiodata($icno, $now);
        });

        $filename = $this->value($old['filename'] ?? null, '');
        if ($filename !== '' && $filename !== 'deleted') {
            $this->fileService->deleteDocument($filename, $icno);
        }

        return true;
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
            SELECT disability.*, type.DisabilityType, cause.DisabilityCause
            FROM hronline.tblprdisability disability
            LEFT JOIN hronline.disabilitytype type ON type.DisabilityTypeCd = disability.DisabilityTypeCd
            LEFT JOIN hronline.disabilitycause cause ON cause.DisabilityCauseCd = disability.DisabilityCauseCd
            WHERE disability.ICNO = :icno AND disability.id = :id
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
            'SELECT * FROM hronline.tblprdisability WHERE ICNO = :icno AND id = :id LIMIT 1',
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
            'socialWelfareNo' => $this->formValue($input['socialWelfareNo'] ?? null),
            'doctorReportNo' => $this->formValue($input['doctorReportNo'] ?? null),
            'disabilityTypeCd' => $this->formValue($input['disabilityTypeCd'] ?? null),
            'disabilityCauseCd' => $this->formValue($input['disabilityCauseCd'] ?? null),
            'disabilityDate' => $this->formValue($input['disabilityDate'] ?? null),
            'healDate' => $this->formValue($input['healDate'] ?? null),
            'filename' => $this->formValue($input['filename'] ?? null),
        ];
    }

    /**
     * @param array<string, string> $values
     * @return array<string, string>
     */
    private function validateValues(string $icno, array $values, ?string $id, ?UploadedFileInterface $file): array
    {
        $errors = [];
        foreach ([
            'socialWelfareNo' => 'No. fail kebajikan diperlukan.',
            'doctorReportNo' => 'No. laporan doktor diperlukan.',
            'disabilityTypeCd' => 'Jenis kecacatan diperlukan.',
            'disabilityCauseCd' => 'Punca kecacatan diperlukan.',
        ] as $field => $message) {
            if ($values[$field] === '') {
                $errors[$field] = $message;
            }
        }

        foreach (['socialWelfareNo' => 'No. fail kebajikan', 'doctorReportNo' => 'No. laporan doktor'] as $field => $label) {
            if ($values[$field] !== '' && strlen($values[$field]) > 20) {
                $errors[$field] = $label . ' tidak boleh melebihi 20 aksara.';
            }
        }

        foreach (['disabilityDate' => 'Tarikh kecacatan', 'healDate' => 'Tarikh sembuh'] as $field => $label) {
            if ($values[$field] !== '' && !$this->isValidDate($values[$field])) {
                $errors[$field] = $label . ' tidak sah.';
            }
        }

        if (
            $values['disabilityTypeCd'] !== ''
            && $this->disabilityTypeExists($icno, $values['disabilityTypeCd'], $id)
        ) {
            $errors['disabilityTypeCd'] = 'Jenis kecacatan ini sudah wujud untuk staf ini.';
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
        if (!preg_match('/\.pdf$/', $filename)) {
            return 'Sila muat naik fail PDF.';
        }

        return null;
    }

    private function disabilityTypeExists(string $icno, string $typeCode, ?string $exceptId): bool
    {
        $sql = 'SELECT 1 FROM hronline.tblprdisability WHERE ICNO = :icno AND DisabilityTypeCd = :typeCode';
        $params = [':icno' => $icno, ':typeCode' => $typeCode];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params[':id'] = $exceptId;
        }
        $sql .= ' LIMIT 1';

        return (bool) $this->db->createCommand($sql, $params)->queryScalar();
    }

    /**
     * @param array<string, mixed> $record
     * @return list<array{0: string, 1: mixed}>
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
     * @param array<string, mixed> $new
     * @return list<array{0: string, 1: string, 2: mixed}>
     */
    private function updateChanges(array $old, array $new): array
    {
        $changes = [];
        foreach ($new as $field => $value) {
            if ((string) ($old[$field] ?? '') !== (string) $value) {
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
            0 => $this->auditTrail->logCreate($icno, self::TABLE, $id, $changes, $ipAddress, 'kecacatan', $now),
            1 => $this->auditTrail->logUpdate($icno, self::TABLE, $id, $changes, $ipAddress, 'kecacatan', $now),
            2 => $this->auditTrail->logDelete($icno, self::TABLE, $id, $changes, $ipAddress, 'kecacatan', $now),
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

    private function formatDate(mixed $value, string $fallback = '-'): string
    {
        return $this->dateFormatter->display($value, $fallback);
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
