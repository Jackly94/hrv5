<?php

declare(strict_types=1);

namespace App\Shared;

use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class StaffRecordSectionCrudService
{
    /**
     * @var array<string, array{
     *     title: string,
     *     backUrl: string,
     *     table: string,
     *     idColumn: string,
     *     ownerColumn: string,
     *     createPath: string,
     *     updatePath: string,
     *     fields: list<array<string, mixed>>
     * }>
     */
    private const CONFIGS = [
        'bahasa' => [
            'title' => 'Bahasa',
            'backUrl' => '/bahasa/view',
            'table' => 'hronline.tblprlangskill',
            'idColumn' => 'id',
            'ownerColumn' => 'ICNO',
            'createPath' => '/bahasa/tambahbahasa',
            'updatePath' => '/bahasa/update',
            'fields' => [
                ['name' => 'LangCd', 'label' => 'Nama Bahasa', 'type' => 'select', 'required' => true, 'options' => 'languages'],
                ['name' => 'LangSkillOral', 'label' => 'Kemahiran Lisan', 'type' => 'select', 'required' => false, 'options' => 'languageProficiencies'],
                ['name' => 'LangSkillWritten', 'label' => 'Kemahiran Menulis', 'type' => 'select', 'required' => false, 'options' => 'languageProficiencies'],
                ['name' => 'LangSkillCert', 'label' => 'Sijil Kemahiran', 'type' => 'select', 'required' => false, 'options' => 'yesNo'],
            ],
        ],
        'anugerah' => [
            'title' => 'Anugerah',
            'backUrl' => '/anugerah/view',
            'table' => 'hronline.tblprawd',
            'idColumn' => 'awdId',
            'ownerColumn' => 'ICNO',
            'createPath' => '/anugerah/tambahanugerah',
            'updatePath' => '/anugerah/update',
            'fields' => [
                ['name' => 'AwdCatCd', 'label' => 'Kategori', 'type' => 'select', 'required' => true, 'options' => 'awardCategories'],
                ['name' => 'AwdCd', 'label' => 'Nama Anugerah', 'type' => 'select', 'required' => true, 'options' => 'awards'],
                ['name' => 'TitleCd', 'label' => 'Gelaran', 'type' => 'select', 'required' => false, 'options' => 'titles'],
                ['name' => 'CfdByCd', 'label' => 'Dianugerahkan Oleh', 'type' => 'select', 'required' => false, 'options' => 'conferredBy'],
                ['name' => 'CountryCd', 'label' => 'Negara', 'type' => 'select', 'required' => false, 'options' => 'countries'],
                ['name' => 'StateCd', 'label' => 'Negeri', 'type' => 'select', 'required' => false, 'options' => 'states'],
                ['name' => 'AwdCfdDt', 'label' => 'Tarikh Dianugerahkan', 'type' => 'date', 'required' => false],
                ['name' => 'AwdReason', 'label' => 'Sebab Anugerah', 'type' => 'textarea', 'required' => false],
                ['name' => 'AwdStatus', 'label' => 'Status Anugerah', 'type' => 'select', 'required' => false, 'options' => 'activeInactive'],
            ],
        ],
        'pengalamankerja' => [
            'title' => 'Pengalaman Kerja',
            'backUrl' => '/pengalamankerja/view',
            'table' => 'hronline.tblprprevemp',
            'idColumn' => 'id',
            'ownerColumn' => 'ICNO',
            'createPath' => '/pengalamankerja/tambahpengalamankerja',
            'updatePath' => '/pengalamankerja/update',
            'fields' => [
                ['name' => 'OrgNm', 'label' => 'Nama Majikan', 'type' => 'text', 'required' => true, 'maxlength' => 80],
                ['name' => 'OccSectorCd', 'label' => 'Sektor', 'type' => 'select', 'required' => false, 'options' => 'occupationSectors'],
                ['name' => 'CorpBodyTypeCd', 'label' => 'Jenis Majikan', 'type' => 'select', 'required' => false, 'options' => 'corporateBodies'],
                ['name' => 'Position', 'label' => 'Jawatan', 'type' => 'text', 'required' => false, 'maxlength' => 50],
                ['name' => 'PrevEmpRemarks', 'label' => 'Keterangan Tugas', 'type' => 'textarea', 'required' => false],
                ['name' => 'PrevEmpStartDt', 'label' => 'Tarikh Mula', 'type' => 'date', 'required' => false],
                ['name' => 'PrevEmpEndDt', 'label' => 'Tarikh Berhenti', 'type' => 'date', 'required' => false],
            ],
        ],
        'sejarahperubatan' => [
            'title' => 'Sejarah Perubatan',
            'backUrl' => '/sejarahperubatan/view',
            'table' => 'hronline.tblprmedhis',
            'idColumn' => 'id',
            'ownerColumn' => 'ICNO',
            'createPath' => '/sejarahperubatan/tambahsejarahperubatan',
            'updatePath' => '/sejarahperubatan/update',
            'fields' => [
                ['name' => 'IllnessCd', 'label' => 'Nama Penyakit', 'type' => 'select', 'required' => true, 'options' => 'illnesses'],
                ['name' => 'IllnessOther', 'label' => 'Nama Penyakit Lain', 'type' => 'text', 'required' => false, 'maxlength' => 200],
                ['name' => 'Year', 'label' => 'Tahun', 'type' => 'text', 'required' => false, 'maxlength' => 4],
                ['name' => 'MedTreatment', 'label' => 'Rawatan', 'type' => 'text', 'required' => false, 'maxlength' => 60],
                ['name' => 'TreatmentStartDt', 'label' => 'Tarikh Mula Rawatan', 'type' => 'date', 'required' => false],
                ['name' => 'TreatmentEndDt', 'label' => 'Tarikh Akhir Rawatan', 'type' => 'date', 'required' => false],
            ],
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

    public function keyFromPath(string $path): ?string
    {
        return match ($path) {
            '/bahasa/tambahbahasa', '/bahasa/update', '/bahasa/delete' => 'bahasa',
            '/anugerah/tambahanugerah', '/anugerah/update', '/anugerah/delete' => 'anugerah',
            '/pengalamankerja/tambahpengalamankerja', '/pengalamankerja/update', '/pengalamankerja/delete' => 'pengalamankerja',
            '/sejarahperubatan/tambahsejarahperubatan', '/sejarahperubatan/update', '/sejarahperubatan/delete' => 'sejarahperubatan',
            default => null,
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    public function form(string $key, string $icno, ?string $id): ?array
    {
        $config = self::CONFIGS[$key] ?? null;
        $staff = $this->employees->byIcno($icno);
        if ($config === null || $staff === null) {
            return null;
        }

        $values = $id === null ? $this->defaultValues($config) : $this->valuesForEdit($config, $icno, $id);
        if ($values === null) {
            return null;
        }

        return [
            ...$config,
            'key' => $key,
            'name' => $this->titleCase($staff['CONm'] ?? null),
            'values' => $values,
            'fields' => $this->fields($config['fields']),
            'action' => $id === null ? $config['createPath'] : $config['updatePath'] . '?id=' . rawurlencode($id),
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, values: array<string, string>, errors: array<string, string>, id?: string}
     */
    public function save(string $key, string $icno, array $input, ?string $id, string $ipAddress): array
    {
        $config = self::CONFIGS[$key] ?? null;
        if ($config === null) {
            return ['success' => false, 'values' => [], 'errors' => ['general' => 'Modul tidak dijumpai.']];
        }

        $existing = null;
        if ($id !== null) {
            $existing = $this->rawRow($config, $icno, $id);
            if ($existing === null) {
                return ['success' => false, 'values' => $this->normalizeValues($config, $input), 'errors' => ['general' => 'Rekod tidak dijumpai.']];
            }
        }

        $values = $this->normalizeValues($config, $input);
        $errors = $this->validateValues($config, $values);
        if ($errors !== []) {
            return ['success' => false, 'values' => $values, 'errors' => $errors];
        }

        $record = [$config['ownerColumn'] => $icno, ...$this->recordValues($config, $values)];
        $now = date('Y-m-d H:i:s');
        $savedId = $this->db->transaction(function () use ($config, $icno, $id, $record, $existing, $now, $ipAddress, $key): string {
            if ($id === null) {
                $this->db->createCommand()->insert($config['table'], $record)->execute();
                $savedId = $this->db->getLastInsertId();
                $this->recordStat->insertPending($icno, $config['table'], $savedId, $now);
                $this->auditTrail->logCreate($icno, $config['table'], $savedId, $this->auditTrail->createChanges($record), $ipAddress, $config['createPath'], $now);
            } else {
                $this->db->createCommand()->update(
                    $config['table'],
                    $record,
                    [$config['idColumn'] => $id, $config['ownerColumn'] => $icno],
                )->execute();
                $savedId = $id;
                $changes = $this->auditTrail->updateChanges($existing ?? [], $record);
                if ($changes !== []) {
                    $this->recordStat->upsertUpdated($icno, $config['table'], $savedId, $now);
                    $this->auditTrail->logUpdate($icno, $config['table'], $savedId, $changes, $ipAddress, $config['updatePath'], $now);
                }
            }

            $this->biodataTouch->touch($icno, $icno, $now);

            return $savedId;
        });

        return ['success' => true, 'values' => $values, 'errors' => [], 'id' => $savedId];
    }

    public function delete(string $key, string $icno, string $id, string $ipAddress): bool
    {
        $config = self::CONFIGS[$key] ?? null;
        if ($config === null) {
            return false;
        }

        $old = $this->rawRow($config, $icno, $id);
        if ($old === null) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $this->db->transaction(function () use ($config, $icno, $id, $old, $now, $ipAddress): void {
            $this->auditTrail->logDelete($icno, $config['table'], $id, $this->auditTrail->createChanges($old), $ipAddress, $config['updatePath'], $now);
            $this->recordStat->deleteForRecord($icno, $config['table'], $id);
            $this->db->createCommand()->delete($config['table'], [
                $config['idColumn'] => $id,
                $config['ownerColumn'] => $icno,
            ])->execute();
            $this->biodataTouch->touch($icno, $icno, $now);
        });

        return true;
    }

    public function backUrl(string $key): string
    {
        return self::CONFIGS[$key]['backUrl'] ?? '/biodata/userview';
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, string>
     */
    private function defaultValues(array $config): array
    {
        $values = ['id' => ''];
        foreach ($config['fields'] as $field) {
            $values[(string) $field['name']] = '';
        }

        return $values;
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, string>|null
     */
    private function valuesForEdit(array $config, string $icno, string $id): ?array
    {
        $row = $this->rawRow($config, $icno, $id);
        if ($row === null) {
            return null;
        }

        $values = ['id' => $this->value($row[$config['idColumn']] ?? null, '')];
        foreach ($config['fields'] as $field) {
            $name = (string) $field['name'];
            $values[$name] = ($field['type'] ?? '') === 'date'
                ? $this->dateFormatter->formValue($row[$name] ?? null)
                : $this->value($row[$name] ?? null, '');
        }

        return $values;
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>|null
     */
    private function rawRow(array $config, string $icno, string $id): ?array
    {
        $row = $this->db->createCommand(
            sprintf(
                'SELECT * FROM %s WHERE %s = :icno AND %s = :id LIMIT 1',
                $config['table'],
                $config['ownerColumn'],
                $config['idColumn'],
            ),
            [':icno' => $icno, ':id' => $id],
        )->queryOne();

        return is_array($row) ? $row : null;
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    private function normalizeValues(array $config, array $input): array
    {
        $values = ['id' => $this->formValue($input['id'] ?? null)];
        foreach ($config['fields'] as $field) {
            $values[(string) $field['name']] = $this->formValue($input[(string) $field['name']] ?? null);
        }

        return $values;
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, string> $values
     * @return array<string, string>
     */
    private function validateValues(array $config, array $values): array
    {
        $errors = [];
        foreach ($config['fields'] as $field) {
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
     * @param array<string, mixed> $config
     * @param array<string, string> $values
     * @return array<string, mixed>
     */
    private function recordValues(array $config, array $values): array
    {
        $record = [];
        foreach ($config['fields'] as $field) {
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
            'languages' => $this->referenceData->options(
                'SELECT LangCd, Lang FROM hronline.languagenm WHERE isActive = 1 ORDER BY Lang',
                'LangCd',
                'Lang',
            ),
            'languageProficiencies' => $this->referenceData->options(
                'SELECT LangProficiencyCd, LangProficiency FROM hronline.langproficiency WHERE isActive = 1 ORDER BY LangProficiencyCd',
                'LangProficiencyCd',
                'LangProficiency',
            ),
            'awardCategories' => $this->referenceData->options(
                'SELECT AwdCatCd, AwdCat FROM hronline.awardcategory WHERE isActive = 1 ORDER BY AwdCat',
                'AwdCatCd',
                'AwdCat',
            ),
            'awards' => $this->referenceData->options(
                'SELECT AwdCd, Awd FROM hronline.award WHERE isActive = 1 ORDER BY Awd',
                'AwdCd',
                'Awd',
            ),
            'titles' => $this->referenceData->options(
                'SELECT TitleCd, Title FROM hronline.title WHERE isActive = 1 ORDER BY Title',
                'TitleCd',
                'Title',
            ),
            'conferredBy' => $this->referenceData->options(
                'SELECT CfdByCd, CfdBy FROM hronline.conferredby WHERE isActive = 1 ORDER BY CfdBy',
                'CfdByCd',
                'CfdBy',
            ),
            'countries' => $this->referenceData->countries(),
            'states' => $this->referenceData->options(
                'SELECT StateCd, State FROM hronline.state WHERE isActive = 1 ORDER BY State',
                'StateCd',
                'State',
            ),
            'occupationSectors' => $this->referenceData->options(
                'SELECT OccSectorCd, OccSector FROM hronline.occupationsector ORDER BY OccSector',
                'OccSectorCd',
                'OccSector',
            ),
            'corporateBodies' => $this->referenceData->options(
                'SELECT CorpBodyTypeCd, CorpBodyType FROM hronline.corporatebody ORDER BY CorpBodyType',
                'CorpBodyTypeCd',
                'CorpBodyType',
            ),
            'illnesses' => $this->referenceData->options(
                'SELECT IllnessCd, Illness FROM hronline.illness ORDER BY Illness',
                'IllnessCd',
                'Illness',
            ),
            'yesNo' => [
                ['id' => '1', 'name' => 'Ada'],
                ['id' => '0', 'name' => 'Tiada'],
            ],
            'activeInactive' => [
                ['id' => '1', 'name' => 'Aktif'],
                ['id' => '0', 'name' => 'Tidak Aktif'],
            ],
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
