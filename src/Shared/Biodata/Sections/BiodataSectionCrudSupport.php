<?php

declare(strict_types=1);

namespace App\Shared\Biodata\Sections;

use App\Shared\AuditTrailService;
use App\Shared\BiodataTouchService;
use App\Shared\EmployeeRepository;
use App\Shared\MediaHostFileService;
use App\Shared\RecordStatService;
use App\Shared\ReferenceDataService;
use App\Shared\StaffDateFormatter;
use Psr\Http\Message\UploadedFileInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class BiodataSectionCrudSupport
{
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

    public function form(array $config, string $key, string $icno, ?string $id = null): ?array
    {
        $staff = $this->employees->byIcno($icno);
        if ($staff === null) {
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

    public function save(array $config, string $icno, array $input, array $files, ?string $id, string $ipAddress): array
    {
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

    public function delete(array $config, string $icno, string $id, string $ipAddress): bool
    {
        if (($config['deletePath'] ?? '') === '') {
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
            if (($field['readonlyOnUpdate'] ?? false) && array_key_exists($name, $existing)) {
                $values[$name] = $this->value($existing[$name] ?? null, '');
                continue;
            }

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
