<?php

declare(strict_types=1);

namespace App\Shared;

use Psr\Http\Message\UploadedFileInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class ProfessionalCertificateService
{
    private const TABLE = 'hronline.tblprprofesionalcert';

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
     * @return array<string, list<array{id: string, name: string}>>
     */
    public function formOptions(): array
    {
        return [
            'types' => $this->referenceData->clinicalCertificateTypes(),
            'fields' => $this->referenceData->professionalCertificateFields(),
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
            'title' => '',
            'type' => '',
            'otherType' => '',
            'field' => '',
            'dateAwarded' => '',
            'certificateNo' => '',
            'startDate' => '',
            'endDate' => '',
            'awardedBy' => '',
            'proof' => '',
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
            'title' => $this->value($row['title'] ?? null, ''),
            'type' => $this->value($row['type'] ?? null, ''),
            'otherType' => $this->value($row['other_type'] ?? null, ''),
            'field' => $this->value($row['field'] ?? null, ''),
            'dateAwarded' => $this->dateValue($row['dateAwd'] ?? null),
            'certificateNo' => $this->value($row['certNo'] ?? null, ''),
            'startDate' => $this->dateValue($row['startDt'] ?? null),
            'endDate' => $this->dateValue($row['endDt'] ?? null),
            'awardedBy' => $this->value($row['awardBy'] ?? null, ''),
            'proof' => $this->value($row['proof'] ?? null, ''),
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
                return ['success' => false, 'values' => $values, 'errors' => ['general' => 'Rekod kelayakan profesional tidak dijumpai.']];
            }
            $values['id'] = $id;
            $values['proof'] = $this->value($existing['proof'] ?? null, '');
        }

        $errors = $this->validateValues($values, $id, $file);
        if ($errors !== []) {
            return ['success' => false, 'values' => $values, 'errors' => $errors];
        }

        $proof = $values['proof'];
        if ($file !== null && $file->getError() !== UPLOAD_ERR_NO_FILE) {
            $upload = $this->fileService->uploadDocument(
                $file,
                'Maklumat Rekod Kakitangan/kelayakanperubatan',
                $icno,
            );

            if ($upload['success'] !== true) {
                return [
                    'success' => false,
                    'values' => $values,
                    'errors' => ['file' => $upload['message'] ?? 'Muat naik dokumen gagal.'],
                ];
            }

            $proof = (string) $upload['hash'];
        }

        $now = date('Y-m-d H:i:s');
        $record = [
            'icno' => $icno,
            'title' => $values['title'],
            'type' => $values['type'],
            'other_type' => $values['otherType'],
            'dateAwd' => $values['dateAwarded'],
            'certNo' => $values['certificateNo'],
            'startDt' => $values['startDate'],
            'endDt' => $values['endDate'],
            'awardBy' => $values['awardedBy'],
            'proof' => $proof,
            'field' => $values['field'],
        ];

        $savedId = $this->db->transaction(function () use ($icno, $id, $record, $existing, $now, $ipAddress): string {
            if ($id === null) {
                $createRecord = [...$record, 'isVerified' => 0];
                $this->db->createCommand()->insert(self::TABLE, $createRecord)->execute();
                $savedId = $this->db->getLastInsertId();
                $this->recordStat->insertPending($icno, self::TABLE, $savedId, $now);
                $this->auditTrail->logCreate(
                    $icno,
                    self::TABLE,
                    $savedId,
                    $this->auditTrail->createChanges($createRecord),
                    $ipAddress,
                    'kelayakan-profesional/add-cl-cert',
                    $now,
                );
            } else {
                $this->db->createCommand()->update(self::TABLE, $record, ['id' => $id, 'icno' => $icno])->execute();
                $savedId = $id;
                $changes = $this->auditTrail->updateChanges($existing ?? [], $record);
                if ($changes !== []) {
                    $this->recordStat->upsertUpdated($icno, self::TABLE, $savedId, $now);
                    $this->auditTrail->logUpdate(
                        $icno,
                        self::TABLE,
                        $savedId,
                        $changes,
                        $ipAddress,
                        'kelayakan-profesional/update-cc',
                        $now,
                    );
                }
            }

            $this->biodataTouch->touch($icno, $icno, $now);

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

        $proof = $this->value($old['proof'] ?? null, '');
        if ($proof !== '' && $proof !== 'deleted') {
            $delete = $this->fileService->deleteDocument($proof, $icno);
            if ($delete['success'] !== true) {
                return false;
            }
        }

        $now = date('Y-m-d H:i:s');
        $this->db->transaction(function () use ($icno, $id, $old, $now, $ipAddress): void {
            $this->auditTrail->logDelete(
                $icno,
                self::TABLE,
                $id,
                $this->auditTrail->createChanges($old),
                $ipAddress,
                'kelayakan-profesional/delete-cc',
                $now,
            );
            $this->recordStat->deleteForRecord($icno, self::TABLE, $id);
            $this->db->createCommand()->delete(self::TABLE, ['id' => $id, 'icno' => $icno])->execute();
            $this->biodataTouch->touch($icno, $icno, $now);
        });

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
    private function rawRowForIcno(string $icno, string $id): ?array
    {
        $row = $this->db->createCommand(
            'SELECT * FROM hronline.tblprprofesionalcert WHERE icno = :icno AND id = :id LIMIT 1',
            [':icno' => $icno, ':id' => $id],
        )->queryOne();

        return is_array($row) ? $row : null;
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
            'title' => $this->formValue($input['title'] ?? null),
            'type' => $this->formValue($input['type'] ?? null),
            'otherType' => $this->formValue($input['otherType'] ?? null),
            'field' => $this->formValue($input['field'] ?? null),
            'dateAwarded' => $this->formValue($input['dateAwarded'] ?? null),
            'certificateNo' => $this->formValue($input['certificateNo'] ?? null),
            'startDate' => $this->formValue($input['startDate'] ?? null),
            'endDate' => $this->formValue($input['endDate'] ?? null),
            'awardedBy' => $this->formValue($input['awardedBy'] ?? null),
            'proof' => $this->formValue($input['proof'] ?? null),
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
            'title' => 'Tajuk sijil diperlukan.',
            'type' => 'Jenis sijil diperlukan.',
            'field' => 'Bidang diperlukan.',
            'dateAwarded' => 'Tarikh dianugerahkan diperlukan.',
            'startDate' => 'Tarikh mula diperlukan.',
            'endDate' => 'Tarikh tamat diperlukan.',
            'awardedBy' => 'Penganugerah diperlukan.',
        ] as $field => $message) {
            if ($values[$field] === '') {
                $errors[$field] = $message;
            }
        }

        if ($values['type'] === '999' && $values['otherType'] === '') {
            $errors['otherType'] = 'Jenis lain-lain diperlukan.';
        }

        foreach ([
            'dateAwarded' => 'Tarikh dianugerahkan tidak sah.',
            'startDate' => 'Tarikh mula tidak sah.',
            'endDate' => 'Tarikh tamat tidak sah.',
        ] as $field => $message) {
            if ($values[$field] !== '' && !$this->dateFormatter->isValidFormDate($values[$field])) {
                $errors[$field] = $message;
            }
        }

        $fileError = $this->validateUploadedFile($file, $id === null, $values['proof']);
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

    private function dateValue(mixed $value): string
    {
        return $this->dateFormatter->formValue($value);
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
