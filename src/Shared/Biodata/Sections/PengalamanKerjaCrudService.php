<?php

declare(strict_types=1);

namespace App\Shared\Biodata\Sections;

use App\Shared\AuditTrailService;
use App\Shared\BiodataTouchService;
use App\Shared\EmployeeRepository;
use App\Shared\RecordStatService;
use App\Shared\ReferenceDataService;
use App\Shared\StaffDateFormatter;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class PengalamanKerjaCrudService
{
    /** @var array<string, mixed> */
    private const CONFIG = [
        'key' => 'pengalamankerja',
        'title' => 'Pengalaman Kerja',
        'backUrl' => '/biodata/pengalamankerja/view',
        'table' => 'hronline.tblprprevemp',
        'idColumn' => 'id',
        'ownerColumn' => 'ICNO',
        'createPath' => '/biodata/pengalamankerja/tambahpengalamankerja',
        'updatePath' => '/biodata/pengalamankerja/update',
        'fields' => [ 
            ['name' => 'OrgNm', 'label' => 'Nama Majikan', 'type' => 'text', 'required' => true, 'maxlength' => 80],
            ['name' => 'OccSectorCd', 'label' => 'Sektor', 'type' => 'select', 'required' => false, 'options' => 'occupationSectors'],
            ['name' => 'CorpBodyTypeCd', 'label' => 'Jenis Majikan', 'type' => 'select', 'required' => false, 'options' => 'corporateBodies'],
            ['name' => 'Position', 'label' => 'Jawatan', 'type' => 'text', 'required' => false, 'maxlength' => 50],
            ['name' => 'PrevEmpRemarks', 'label' => 'Keterangan Tugas', 'type' => 'textarea', 'required' => false],
            ['name' => 'PrevEmpStartDt', 'label' => 'Tarikh Mula', 'type' => 'date', 'required' => false],
            ['name' => 'PrevEmpEndDt', 'label' => 'Tarikh Berhenti', 'type' => 'date', 'required' => false],
        ],
    ];

    public function __construct(
        private ConnectionInterface $db,
        private StaffDateFormatter $dateFormatter,
        private AuditTrailService $auditTrail,
        private RecordStatService $recordStat,
        private BiodataTouchService $biodataTouch,
        private ReferenceDataService $referenceData,
        private EmployeeRepository $employees,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function form(string $icno, ?string $id): ?array
    {
        $staff = $this->employees->byIcno($icno);
        if ($staff === null) {
            return null;
        }

        $values = $id === null ? $this->defaultValues() : $this->valuesForEdit($icno, $id);
        if ($values === null) {
            return null;
        }

        return [
            ...self::CONFIG,
            'name' => $this->titleCase($staff['CONm'] ?? null),
            'values' => $values,
            'fields' => $this->fields(self::CONFIG['fields']),
            'action' => $id === null ? self::CONFIG['createPath'] : self::CONFIG['updatePath'] . '?id=' . rawurlencode($id),
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, values: array<string, string>, errors: array<string, string>, id?: string}
     */
    public function save(string $icno, array $input, ?string $id, string $ipAddress): array
    {
        $existing = null;
        if ($id !== null) {
            $existing = $this->rawRow($icno, $id);
            if ($existing === null) {
                return ['success' => false, 'values' => $this->normalizeValues($input), 'errors' => ['general' => 'Rekod tidak dijumpai.']];
            }
        }

        $values = $this->normalizeValues($input);
        $errors = $this->validateValues($values);
        if ($errors !== []) {
            return ['success' => false, 'values' => $values, 'errors' => $errors];
        }

        $record = [self::CONFIG['ownerColumn'] => $icno, ...$this->recordValues($values)];
        $now = date('Y-m-d H:i:s');
        $savedId = $this->db->transaction(function () use ($icno, $id, $record, $existing, $now, $ipAddress): string {
            if ($id === null) {
                $this->db->createCommand()->insert(self::CONFIG['table'], $record)->execute();
                $savedId = $this->db->getLastInsertId();
                $this->recordStat->insertPending($icno, self::CONFIG['table'], $savedId, $now);
                $this->auditTrail->logCreate($icno, self::CONFIG['table'], $savedId, $this->auditTrail->createChanges($record), $ipAddress, self::CONFIG['createPath'], $now);
            } else {
                $this->db->createCommand()->update(
                    self::CONFIG['table'],
                    $record,
                    [self::CONFIG['idColumn'] => $id, self::CONFIG['ownerColumn'] => $icno],
                )->execute();
                $savedId = $id;
                $changes = $this->auditTrail->updateChanges($existing ?? [], $record);
                if ($changes !== []) {
                    $this->recordStat->upsertUpdated($icno, self::CONFIG['table'], $savedId, $now);
                    $this->auditTrail->logUpdate($icno, self::CONFIG['table'], $savedId, $changes, $ipAddress, self::CONFIG['updatePath'], $now);
                }
            }

            $this->biodataTouch->touch($icno, $icno, $now);

            return $savedId;
        });

        return ['success' => true, 'values' => $values, 'errors' => [], 'id' => $savedId];
    }

    public function delete(string $icno, string $id, string $ipAddress): bool
    {
        $old = $this->rawRow($icno, $id);
        if ($old === null) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $this->db->transaction(function () use ($icno, $id, $old, $now, $ipAddress): void {
            $this->auditTrail->logDelete($icno, self::CONFIG['table'], $id, $this->auditTrail->createChanges($old), $ipAddress, self::CONFIG['updatePath'], $now);
            $this->recordStat->deleteForRecord($icno, self::CONFIG['table'], $id);
            $this->db->createCommand()->delete(self::CONFIG['table'], [
                self::CONFIG['idColumn'] => $id,
                self::CONFIG['ownerColumn'] => $icno,
            ])->execute();
            $this->biodataTouch->touch($icno, $icno, $now);
        });

        return true;
    }

    public function backUrl(): string
    {
        return self::CONFIG['backUrl'];
    }

    /**
     * @return array<string, string>
     */
    private function defaultValues(): array
    {
        $values = ['id' => ''];
        foreach (self::CONFIG['fields'] as $field) {
            $values[(string) $field['name']] = '';
        }

        return $values;
    }

    /**
     * @return array<string, string>|null
     */
    private function valuesForEdit(string $icno, string $id): ?array
    {
        $row = $this->rawRow($icno, $id);
        if ($row === null) {
            return null;
        }

        $values = ['id' => $this->value($row[self::CONFIG['idColumn']] ?? null, '')];
        foreach (self::CONFIG['fields'] as $field) {
            $name = (string) $field['name'];
            $values[$name] = ($field['type'] ?? '') === 'date'
                ? $this->dateFormatter->formValue($row[$name] ?? null)
                : $this->value($row[$name] ?? null, '');
        }

        return $values;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function rawRow(string $icno, string $id): ?array
    {
        $row = $this->db->createCommand(
            sprintf('SELECT * FROM %s WHERE %s = :icno AND %s = :id LIMIT 1', self::CONFIG['table'], self::CONFIG['ownerColumn'], self::CONFIG['idColumn']),
            [':icno' => $icno, ':id' => $id],
        )->queryOne();

        return is_array($row) ? $row : null;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    private function normalizeValues(array $input): array
    {
        $values = ['id' => $this->formValue($input['id'] ?? null)];
        foreach (self::CONFIG['fields'] as $field) {
            $values[(string) $field['name']] = $this->formValue($input[(string) $field['name']] ?? null);
        }

        return $values;
    }

    /**
     * @param array<string, string> $values
     * @return array<string, string>
     */
    private function validateValues(array $values): array
    {
        $errors = [];
        foreach (self::CONFIG['fields'] as $field) {
            $name = (string) $field['name'];
            if (($field['required'] ?? false) === true && ($values[$name] ?? '') === '') {
                $errors[$name] = $field['label'] . ' diperlukan.';
            }

            if (($field['type'] ?? '') === 'date' && ($values[$name] ?? '') !== '' && !$this->dateFormatter->isValidFormDate($values[$name])) {
                $errors[$name] = $field['label'] . ' tidak sah.';
            }

            if (isset($field['maxlength']) && ($values[$name] ?? '') !== '' && strlen($values[$name]) > (int) $field['maxlength']) {
                $errors[$name] = $field['label'] . ' tidak boleh melebihi ' . (int) $field['maxlength'] . ' aksara.';
            }
        }

        return $errors;
    }

    /**
     * @param array<string, string> $values
     * @return array<string, mixed>
     */
    private function recordValues(array $values): array
    {
        $record = [];
        foreach (self::CONFIG['fields'] as $field) {
            $name = (string) $field['name'];
            $record[$name] = $values[$name] === '' ? null : $values[$name];
        }

        return $record;
    }

    /**
     * @param list<array<string, mixed>> $fields
     * @return list<array<string, mixed>>
     */
    private function fields(array $fields): array
    {
        return array_map(function (array $field): array {
            if (($field['type'] ?? '') === 'select') {
                $field['optionsList'] = $this->options((string) ($field['options'] ?? ''));
            }

            return $field;
        }, $fields);
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function options(string $key): array
    {
        return match ($key) {
            'occupationSectors' => $this->referenceData->options('SELECT OccSectorCd, OccSector FROM hronline.occupationsector ORDER BY OccSector', 'OccSectorCd', 'OccSector'),
            'corporateBodies' => $this->referenceData->options('SELECT CorpBodyTypeCd, CorpBodyType FROM hronline.corporatebody ORDER BY CorpBodyType', 'CorpBodyTypeCd', 'CorpBodyType'),
            default => [],
        };
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

    private function value(mixed $value, string $fallback = 'Tiada Rekod'): string
    {
        if ($value === null) {
            return $fallback;
        }

        $value = trim((string) $value);

        return $value === '' ? $fallback : $value;
    }
}