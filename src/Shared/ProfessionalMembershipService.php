<?php

declare(strict_types=1);

namespace App\Shared;

use Psr\Http\Message\UploadedFileInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class ProfessionalMembershipService
{
    public function __construct(
        private ConnectionInterface $db,
        private MediaHostFileService $fileService,
        private StaffDateFormatter $dateFormatter,
        private AuditTrailService $auditTrail,
        private RecordStatService $recordStat,
        private ReferenceDataService $referenceData,
        private EmployeeRepository $employees,
    ) {}

    /**
     * @return array{
     *     icno: string,
     *     name: string,
     *     staffNo: string,
     *     rows: list<array<string, string>>
     * }|null
     */
    public function listForIcno(string $icno): ?array
    {
        $staff = $this->staffRow($icno);
        if ($staff === null) {
            return null;
        }

        $rows = $this->db->createCommand(
            <<<SQL
            SELECT
                assoc.profId,
                assoc.ProfBodyCd,
                assoc.ProfBodyOther,
                assoc.MembershipTypeCd,
                assoc.JoinDt,
                assoc.FeeAmt,
                assoc.Designation,
                assoc.ResignDt,
                assoc.ProfAssocStatus,
                assoc.ProfAssocLvl,
                assoc.membership_no,
                assoc.url,
                assoc.filename,
                body.ProfBody,
                membership.MembershipType,
                assoc_level.LvlNm
            FROM hronline.tblprprofassoc assoc
            LEFT JOIN hronline.professionalassociation body
                ON body.ProfBodyCd = assoc.ProfBodyCd
            LEFT JOIN hronline.membershiptype membership
                ON membership.MembershipTypeCd = assoc.MembershipTypeCd
            LEFT JOIN hronline.professional_association_level assoc_level
                ON assoc_level.id = assoc.ProfAssocLvl
            WHERE assoc.ICNO = :icno
            ORDER BY assoc.JoinDt DESC, assoc.profId DESC
            SQL,
            [':icno' => $icno],
        )->queryAll();

        $memberships = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $statusCode = $this->value($row['ProfAssocStatus'] ?? null, '');
            $memberships[] = [
                'id' => $this->value($row['profId'] ?? null, ''),
                'body' => $this->professionalBody($row),
                'level' => $this->value(
                    $row['LvlNm'] ?? null,
                    'Peringkat Kelab/Persatuan/Institusi/Kesatuan belum dikemaskini.',
                ),
                'membershipNo' => $this->value($row['membership_no'] ?? null, 'Sila Kemaskini'),
                'membershipType' => $this->value($row['MembershipType'] ?? null),
                'designation' => $this->value($row['Designation'] ?? null, '-'),
                'startDate' => $this->formatDate($row['JoinDt'] ?? null),
                'endDate' => $this->formatDate($row['ResignDt'] ?? null),
                'fee' => $this->fee($row['FeeAmt'] ?? null),
                'status' => $this->activeStatus($statusCode),
                'statusCode' => $statusCode,
                'url' => $this->value($row['url'] ?? null, ''),
                'fileUrl' => $this->fileService->viewUrl($this->value($row['filename'] ?? null, '')),
            ];
        }

        return [
            'icno' => $icno,
            'name' => $this->titleCase($staff['CONm'] ?? null),
            'staffNo' => $this->value($staff['COOldID'] ?? null),
            'rows' => $memberships,
        ];
    }

    /**
     * @return array<string, list<array{id: string, name: string}>>
     */
    public function formOptions(): array
    {
        return [
            'bodies' => $this->referenceData->professionalBodies(),
            'membershipTypes' => $this->referenceData->membershipTypes(),
            'levels' => $this->referenceData->professionalAssociationLevels(),
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, values: array<string, string>, errors: array<string, string>, id?: string}
     */
    public function defaultValues(): array
    {
        return [
            'profBodyCd' => '',
            'membershipTypeCd' => '',
            'designation' => '',
            'level' => '',
            'membershipNo' => '',
            'joinDate' => '',
            'resignDate' => '',
            'fee' => '',
            'url' => '',
            'status' => '0',
            'isMedicalBody' => '0',
            'filename' => '',
        ];
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
            'id' => $this->value($row['profId'] ?? null, ''),
            'body' => $this->professionalBody($row),
            'level' => $this->value($row['LvlNm'] ?? null, '-'),
            'membershipNo' => $this->value($row['membership_no'] ?? null, '-'),
            'membershipType' => $this->value($row['MembershipType'] ?? null, '-'),
            'designation' => $this->value($row['Designation'] ?? null, '-'),
            'startDate' => $this->formatDate($row['JoinDt'] ?? null),
            'endDate' => $this->formatDate($row['ResignDt'] ?? null),
            'fee' => $this->fee($row['FeeAmt'] ?? null),
            'url' => $this->value($row['url'] ?? null, '-'),
            'status' => $this->activeStatus($this->value($row['ProfAssocStatus'] ?? null, '')),
            'isMedicalBody' => ((string) ($row['isMedicalBody'] ?? '') === '1') ? 'Ya' : 'Tidak',
            'file' => $this->fileLabel($file),
            'fileUrl' => $this->fileService->viewUrl($file),
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
            'id' => $this->value($row['profId'] ?? null, ''),
            'profBodyCd' => $this->value($row['ProfBodyCd'] ?? null, ''),
            'membershipTypeCd' => $this->value($row['MembershipTypeCd'] ?? null, ''),
            'designation' => $this->value($row['Designation'] ?? null, ''),
            'level' => $this->value($row['ProfAssocLvl'] ?? null, ''),
            'membershipNo' => $this->value($row['membership_no'] ?? null, ''),
            'joinDate' => $this->dateValue($row['JoinDt'] ?? null),
            'resignDate' => $this->dateValue($row['ResignDt'] ?? null),
            'fee' => $this->value($row['FeeAmt'] ?? null, ''),
            'url' => $this->value($row['url'] ?? null, ''),
            'status' => (string) ((string) ($row['ProfAssocStatus'] ?? '') === '1' ? 1 : 0),
            'isMedicalBody' => (string) ((string) ($row['isMedicalBody'] ?? '') === '1' ? 1 : 0),
            'filename' => $this->value($row['filename'] ?? null, ''),
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, values: array<string, string>, errors: array<string, string>, id?: string}
     */
    public function saveForIcno(string $icno, array $input, ?string $id, ?UploadedFileInterface $file, string $ipAddress): array
    {
        $values = $this->normalizeFormValues($input);
        $existing = null;
        if ($id !== null) {
            $existing = $this->rawRowForIcno($icno, $id);
            if ($existing === null) {
                return ['success' => false, 'values' => $values, 'errors' => ['general' => 'Rekod keahlian tidak dijumpai.']];
            }
            $values['filename'] = $this->value($existing['filename'] ?? null, '');
        }

        $errors = $this->validateFormValues($values, $file, $id === null);

        if ($errors !== []) {
            return ['success' => false, 'values' => $values, 'errors' => $errors];
        }

        $filename = $values['filename'];
        if ($file !== null && $file->getError() !== UPLOAD_ERR_NO_FILE) {
            $upload = $this->fileService->uploadDocument(
                $file,
                'Maklumat Rekod Kakitangan/Badan Profesional',
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
        $profId = $this->db->transaction(function () use ($icno, $id, $values, $filename, $existing, $now, $ipAddress): string {
            $record = [
                'ICNO' => $icno,
                'ProfBodyCd' => $values['profBodyCd'],
                'MembershipTypeCd' => $values['membershipTypeCd'],
                'Designation' => $values['designation'],
                'ProfAssocLvl' => $values['level'],
                'membership_no' => $values['membershipNo'],
                'JoinDt' => $values['joinDate'],
                'ResignDt' => $values['resignDate'] !== '' ? $values['resignDate'] : null,
                'FeeAmt' => $values['fee'],
                'url' => $values['url'],
                'ProfAssocStatus' => $values['status'],
                'isMedicalBody' => $values['isMedicalBody'],
                'filename' => $filename,
            ];

            if ($id === null) {
                $this->db->createCommand()->insert('hronline.tblprprofassoc', $record)->execute();
                $profId = $this->db->getLastInsertId();
                $this->recordStat->insertPending($icno, 'hronline.tblprprofassoc', $profId, $now);
                $this->auditTrail->logCreate(
                    $icno,
                    'hronline.tblprprofassoc',
                    $profId,
                    $this->auditTrail->createChanges($record),
                    $ipAddress,
                    'badan-profesional/tambahbadanprofesional',
                    $now,
                );
            } else {
                $this->db->createCommand()->update('hronline.tblprprofassoc', $record, ['profId' => $id, 'ICNO' => $icno])->execute();
                $profId = $id;
                $changes = $this->auditTrail->updateChanges($existing ?? [], $record);
                if ($changes !== []) {
                    $this->recordStat->upsertUpdated($icno, 'hronline.tblprprofassoc', $profId, $now);
                    $this->auditTrail->logUpdate($icno, 'hronline.tblprprofassoc', $profId, $changes, $ipAddress, 'badan-profesional/update', $now);
                }
            }

            return $profId;
        });

        return ['success' => true, 'values' => $values, 'errors' => [], 'id' => $profId];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, values: array<string, string>, errors: array<string, string>, id?: string}
     */
    public function createForIcno(string $icno, array $input, ?UploadedFileInterface $file, string $ipAddress): array
    {
        return $this->saveForIcno($icno, $input, null, $file, $ipAddress);
    }

    public function delete(string $icno, string $id, string $ipAddress): bool
    {
        $old = $this->rawRowForIcno($icno, $id);
        if ($old === null) {
            return false;
        }

        $file = $this->value($old['filename'] ?? null, '');
        if ($file !== '' && $file !== 'deleted') {
            $delete = $this->fileService->deleteDocument($file, $icno);
            if ($delete['success'] !== true) {
                return false;
            }
        }

        $now = date('Y-m-d H:i:s');
        $this->db->transaction(function () use ($icno, $id, $old, $now, $ipAddress): void {
            $this->auditTrail->logDelete($icno, 'hronline.tblprprofassoc', $id, $this->auditTrail->createChanges($old), $ipAddress, 'badan-profesional/delete', $now);
            $this->recordStat->deleteForRecord($icno, 'hronline.tblprprofassoc', $id);
            $this->db->createCommand()->delete('hronline.tblprprofassoc', ['profId' => $id, 'ICNO' => $icno])->execute();
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
            'SELECT * FROM hronline.tblprprofassoc WHERE ICNO = :icno AND profId = :id LIMIT 1',
            [':icno' => $icno, ':id' => $id],
        )->queryOne();

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function rowForIcno(string $icno, string $id): ?array
    {
        $row = $this->db->createCommand(
            <<<SQL
            SELECT assoc.*, body.ProfBody, membership.MembershipType, assoc_level.LvlNm
            FROM hronline.tblprprofassoc assoc
            LEFT JOIN hronline.professionalassociation body ON body.ProfBodyCd = assoc.ProfBodyCd
            LEFT JOIN hronline.membershiptype membership ON membership.MembershipTypeCd = assoc.MembershipTypeCd
            LEFT JOIN hronline.professional_association_level assoc_level ON assoc_level.id = assoc.ProfAssocLvl
            WHERE assoc.ICNO = :icno AND assoc.profId = :id
            LIMIT 1
            SQL,
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
            if ($id === '' || $name === '') {
                continue;
            }

            $options[] = ['id' => $id, 'name' => $name];
        }

        return $options;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    private function normalizeFormValues(array $input): array
    {
        return [
            'profBodyCd' => $this->formValue($input['profBodyCd'] ?? null),
            'membershipTypeCd' => $this->formValue($input['membershipTypeCd'] ?? null),
            'designation' => $this->formValue($input['designation'] ?? null),
            'level' => $this->formValue($input['level'] ?? null),
            'membershipNo' => $this->formValue($input['membershipNo'] ?? null),
            'joinDate' => $this->formValue($input['joinDate'] ?? null),
            'resignDate' => $this->formValue($input['resignDate'] ?? null),
            'fee' => $this->formValue($input['fee'] ?? null),
            'url' => $this->formValue($input['url'] ?? null),
            'status' => (string) ((string) ($input['status'] ?? '0') === '1' ? 1 : 0),
            'isMedicalBody' => (string) ((string) ($input['isMedicalBody'] ?? '0') === '1' ? 1 : 0),
            'filename' => $this->formValue($input['filename'] ?? null),
        ];
    }

    /**
     * @param array<string, string> $values
     * @return array<string, string>
     */
    private function validateFormValues(array $values, ?UploadedFileInterface $file, bool $fileRequired): array
    {
        $errors = [];

        foreach ([
            'profBodyCd' => 'Nama organisasi diperlukan.',
            'membershipTypeCd' => 'Taraf keahlian diperlukan.',
            'designation' => 'Jawatan diperlukan.',
            'level' => 'Peringkat diperlukan.',
            'membershipNo' => 'No. keahlian diperlukan.',
            'joinDate' => 'Tarikh mula menyertai diperlukan.',
            'fee' => 'Yuran dikenakan diperlukan.',
            'url' => 'URL diperlukan.',
        ] as $field => $message) {
            if ($values[$field] === '') {
                $errors[$field] = $message;
            }
        }

        if ($values['fee'] !== '' && !is_numeric($values['fee'])) {
            $errors['fee'] = 'Yuran hendaklah dalam nilai nombor.';
        }

        if ($values['joinDate'] !== '' && !$this->isValidDate($values['joinDate'])) {
            $errors['joinDate'] = 'Tarikh mula menyertai tidak sah.';
        }

        if ($values['resignDate'] !== '' && !$this->isValidDate($values['resignDate'])) {
            $errors['resignDate'] = 'Tarikh tamat menyertai tidak sah.';
        }

        if (strlen($values['designation']) > 40) {
            $errors['designation'] = 'Jawatan tidak boleh melebihi 40 aksara.';
        }

        if (strlen($values['membershipNo']) > 50) {
            $errors['membershipNo'] = 'No. keahlian tidak boleh melebihi 50 aksara.';
        }

        $fileError = $this->validateUploadedFile($file, $fileRequired, $values['filename'] ?? '');
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
        if (!str_ends_with($filename, '.pdf')) {
            return 'Sila muat naik fail dalam format PDF.';
        }

        return null;
    }

    private function isValidDate(string $value): bool
    {
        return $this->dateFormatter->isValidFormDate($value);
    }

    private function dateValue(mixed $value): string
    {
        return $this->dateFormatter->formValue($value);
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

    private function activeStatus(string $statusCode): string
    {
        return !in_array($statusCode, ['', '0'], true) ? 'Aktif' : 'Tidak Aktif';
    }

    private function fee(mixed $value): string
    {
        $value = $this->value($value, '');
        if ($value === '' || $value === '0' || $value === '0.00') {
            return '-';
        }

        return 'RM ' . $value;
    }

    private function formatDate(mixed $value): string
    {
        return $this->dateFormatter->display($value);
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
