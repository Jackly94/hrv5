<?php

declare(strict_types=1);

namespace App\Shared;

use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class BiodataUpdateService
{
    private const TABLE = 'hronline.tblprcobiodata';

    /**
     * @var list<array<string, mixed>>
     */
    private const FIELDS = [
        ['name' => 'ICNO', 'label' => 'No. KP / Paspot', 'type' => 'static', 'scope' => 'all'],
        ['name' => 'COOldID', 'label' => 'UMSPER', 'type' => 'static', 'scope' => 'all'],
        ['name' => 'TitleCd', 'label' => 'Gelaran', 'type' => 'select', 'options' => 'titles', 'required' => true, 'scope' => 'all'],
        ['name' => 'CONm', 'label' => 'Nama', 'type' => 'text', 'required' => true, 'scope' => 'admin'],
        ['name' => 'CONm', 'label' => 'Nama', 'type' => 'static', 'scope' => 'self'],
        ['name' => 'ReligionCd', 'label' => 'Agama', 'type' => 'select', 'options' => 'religions', 'required' => true, 'scope' => 'all'],
        ['name' => 'RaceCd', 'label' => 'Bangsa', 'type' => 'select', 'options' => 'races', 'required' => true, 'scope' => 'all'],
        ['name' => 'EthnicCd', 'label' => 'Etnik', 'type' => 'select', 'options' => 'ethnicities', 'required' => true, 'scope' => 'all'],
        ['name' => 'ArmyPoliceCd', 'label' => 'Status Uniform', 'type' => 'select', 'options' => 'uniformStatuses', 'required' => true, 'scope' => 'all'],
        ['name' => 'BloodTypeCd', 'label' => 'Jenis Darah', 'type' => 'select', 'options' => 'bloodTypes', 'required' => true, 'scope' => 'all'],
        ['name' => 'GenderCd', 'label' => 'Jantina', 'type' => 'select', 'options' => 'genders', 'required' => true, 'scope' => 'all'],
        ['name' => 'MrtlStatusCd', 'label' => 'Status Perkahwinan', 'type' => 'select', 'options' => 'maritalStatuses', 'required' => true, 'scope' => 'all'],
        ['name' => 'COBirthCertNo', 'label' => 'No. Sijil Lahir', 'type' => 'text', 'required' => true, 'scope' => 'all'],
        ['name' => 'COBirthDt', 'label' => 'Tarikh Lahir', 'type' => 'date', 'required' => true, 'scope' => 'all'],
        ['name' => 'COBirthCountryCd', 'label' => 'Negara Kelahiran', 'type' => 'select', 'options' => 'countries', 'required' => true, 'scope' => 'all'],
        ['name' => 'COBirthPlaceCd', 'label' => 'Tempat Kelahiran', 'type' => 'select', 'options' => 'states', 'required' => true, 'scope' => 'all'],
        ['name' => 'NegaraAsalCd', 'label' => 'Negara Asal Staf', 'type' => 'select', 'options' => 'countries', 'required' => true, 'scope' => 'all'],
        ['name' => 'NegeriAsalCd', 'label' => 'Negeri Asal Staf', 'type' => 'select', 'options' => 'states', 'required' => true, 'scope' => 'all'],
        ['name' => 'NegeriAsalIbu', 'label' => 'Negeri Asal Ibu', 'type' => 'select', 'options' => 'states', 'required' => true, 'scope' => 'all'],
        ['name' => 'NegeriAsalBapa', 'label' => 'Negeri Asal Bapa', 'type' => 'select', 'options' => 'states', 'required' => true, 'scope' => 'all'],
        ['name' => 'NatCd', 'label' => 'Warganegara', 'type' => 'select', 'options' => 'countries', 'required' => true, 'scope' => 'all'],
        ['name' => 'NatStatusCd', 'label' => 'Status Warganegara', 'type' => 'select', 'options' => 'nationalityStatuses', 'required' => true, 'scope' => 'admin'],
        ['name' => 'NatStatusCd', 'label' => 'Status Warganegara', 'type' => 'static', 'scope' => 'self'],
        ['name' => 'COBumiStatus', 'label' => 'Status Bumiputera', 'type' => 'checkbox', 'scope' => 'all'],
        ['name' => 'isSabahan', 'label' => 'Status Sabah', 'type' => 'select', 'options' => 'sabahStatuses', 'required' => true, 'scope' => 'all'],
        ['name' => 'COEmail', 'label' => 'E-mel Rasmi (@ums.edu.my)', 'type' => 'email', 'required' => true, 'scope' => 'admin'],
        ['name' => 'COEmail', 'label' => 'E-mel Rasmi (@ums.edu.my)', 'type' => 'static', 'scope' => 'self'],
        ['name' => 'COEmail2', 'label' => 'E-mel Peribadi', 'type' => 'email', 'scope' => 'all'],
        ['name' => 'COHPhoneNo', 'label' => 'No. Telefon Bimbit', 'type' => 'text', 'required' => true, 'scope' => 'all'],
        ['name' => 'COHPhoneNo2', 'label' => 'No. Telefon Bimbit 2', 'type' => 'text', 'scope' => 'all'],
        ['name' => 'COHPhoneStatus', 'label' => 'Status Telefon Bimbit', 'type' => 'checkbox', 'scope' => 'all'],
        ['name' => 'COOffTelNo', 'label' => 'No. Telefon Pejabat', 'type' => 'text', 'scope' => 'all'],
        ['name' => 'COOffTelNoExtn', 'label' => 'No. Sambungan 1', 'type' => 'text', 'scope' => 'all'],
        ['name' => 'COOffTelNoExtn2', 'label' => 'No. Sambungan 2', 'type' => 'text', 'scope' => 'all'],
        ['name' => 'COOUCTelNo', 'label' => 'No. Sambungan UC', 'type' => 'text', 'scope' => 'all'],
        ['name' => 'mySejahteraId', 'label' => 'ID MySejahtera', 'type' => 'text', 'scope' => 'all'],
        ['name' => 'KodProgram', 'label' => 'Program Pengajaran', 'type' => 'select', 'options' => 'programs', 'scope' => 'admin'],
        ['name' => 'jawatanTadbir', 'label' => 'Jawatan Pentadbiran', 'type' => 'select', 'options' => 'administrativePositions', 'scope' => 'admin'],
        ['name' => 'gredJawatan', 'label' => 'Gred Jawatan Semasa', 'type' => 'select', 'options' => 'jobGrades', 'scope' => 'admin'],
        ['name' => 'gredJawatan_hakiki', 'label' => 'Gred Jawatan Hakiki', 'type' => 'select', 'options' => 'jobGrades', 'scope' => 'admin'],
        ['name' => 'DeptId_hakiki', 'label' => 'Jabatan Hakiki', 'type' => 'select', 'options' => 'departments', 'scope' => 'admin'],
    ];

    public function __construct(
        private ConnectionInterface $db,
        private StaffDateFormatter $dateFormatter,
        private ReferenceDataService $referenceData,
        private AuditTrailService $auditTrail,
        private RecordStatService $recordStat,
        private BiodataTouchService $biodataTouch,
        private AuthorizationService $authorization,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function form(string $loggedInIcno, string $targetIcno, bool $adminMode): ?array
    {
        if (!$this->canAccess($loggedInIcno, $targetIcno, $adminMode)) {
            return null;
        }

        $row = $this->row($targetIcno);
        if ($row === null) {
            return null;
        }

        return [
            'title' => 'Kemaskini Biodata',
            'name' => $this->titleCase($row['CONm'] ?? null),
            'backUrl' => $adminMode ? '/biodata/adminview?id=' . rawurlencode($targetIcno) : '/biodata/lihatbiodata',
            'action' => $adminMode ? '/biodata/kemaskinikakitangan?id=' . rawurlencode($targetIcno) : '/biodata/kemaskini',
            'fields' => $this->fields($adminMode),
            'options' => $this->options(),
            'values' => $this->values($row, $adminMode),
            'adminMode' => $adminMode,
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, values: array<string, string>, errors: array<string, string>}
     */
    public function save(string $loggedInIcno, string $targetIcno, bool $adminMode, array $input, string $ipAddress): array
    {
        if (!$this->canAccess($loggedInIcno, $targetIcno, $adminMode)) {
            return ['success' => false, 'values' => [], 'errors' => ['general' => 'Akses tidak dibenarkan.']];
        }

        $old = $this->row($targetIcno);
        if ($old === null) {
            return ['success' => false, 'values' => [], 'errors' => ['general' => 'Rekod biodata tidak dijumpai.']];
        }

        $fields = $this->fields($adminMode);
        $values = $this->normalizeValues($fields, $input, $old);
        $errors = $this->validate($fields, $values, $targetIcno);
        if ($errors !== []) {
            return ['success' => false, 'values' => $values, 'errors' => $errors];
        }

        $record = $this->recordFromValues($fields, $values);
        if (($record['NatStatusCd'] ?? (string) ($old['NatStatusCd'] ?? '')) !== '1') {
            $record['COBumiStatus'] = '0';
        }

        $changes = $this->auditTrail->updateChanges($old, $record);
        if ($changes === []) {
            return ['success' => true, 'values' => $values, 'errors' => []];
        }

        $now = date('Y-m-d H:i:s');
        $action = $adminMode ? 'biodata/kemaskinikakitangan' : 'biodata/kemaskini';
        $this->db->transaction(function () use ($targetIcno, $loggedInIcno, $record, $changes, $ipAddress, $action, $now): void {
            $this->db->createCommand()->update(self::TABLE, $record, ['ICNO' => $targetIcno])->execute();
            $this->recordStat->upsertUpdated($targetIcno, self::TABLE, $targetIcno, $now);
            $this->auditTrail->logUpdate($targetIcno, self::TABLE, $targetIcno, $changes, $ipAddress, $action, $now);
            $this->biodataTouch->touch($targetIcno, $loggedInIcno, $now);
        });

        return ['success' => true, 'values' => $values, 'errors' => []];
    }

    private function canAccess(string $loggedInIcno, string $targetIcno, bool $adminMode): bool
    {
        if ($loggedInIcno === '' || $targetIcno === '') {
            return false;
        }

        return $adminMode
            ? $this->authorization->canAccessStaffRecord($loggedInIcno, $targetIcno)
            : $this->authorization->canAccessOwnRecord($loggedInIcno, $targetIcno);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function row(string $icno): ?array
    {
        $row = $this->db->createCommand(
            'SELECT * FROM ' . self::TABLE . ' WHERE ICNO = :icno LIMIT 1',
            [':icno' => $icno],
        )->queryOne();

        return is_array($row) ? $row : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fields(bool $adminMode): array
    {
        $scope = $adminMode ? 'admin' : 'self';

        return array_values(array_filter(
            self::FIELDS,
            static fn (array $field): bool => ($field['scope'] ?? 'all') === 'all' || ($field['scope'] ?? '') === $scope,
        ));
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, string>
     */
    private function values(array $row, bool $adminMode): array
    {
        $values = [];
        foreach ($this->fields($adminMode) as $field) {
            $name = (string) $field['name'];
            $values[$name] = ($field['type'] ?? '') === 'date'
                ? $this->dateFormatter->formValue($row[$name] ?? null)
                : $this->value($row[$name] ?? null, '');
        }

        return $values;
    }

    /**
     * @param list<array<string, mixed>> $fields
     * @param array<string, mixed> $input
     * @param array<string, mixed> $old
     * @return array<string, string>
     */
    private function normalizeValues(array $fields, array $input, array $old): array
    {
        $values = [];
        foreach ($fields as $field) {
            $name = (string) $field['name'];
            $type = (string) ($field['type'] ?? 'text');
            if ($type === 'static') {
                $values[$name] = $this->value($old[$name] ?? null, '');
                continue;
            }

            if ($type === 'checkbox') {
                $values[$name] = isset($input[$name]) && (string) $input[$name] === '1' ? '1' : '0';
                continue;
            }

            $values[$name] = $this->formValue($input[$name] ?? null);
        }

        return $values;
    }

    /**
     * @param list<array<string, mixed>> $fields
     * @param array<string, string> $values
     * @return array<string, string>
     */
    private function validate(array $fields, array $values, string $targetIcno): array
    {
        $errors = [];
        foreach ($fields as $field) {
            $name = (string) $field['name'];
            $type = (string) ($field['type'] ?? 'text');
            if ($type === 'static') {
                continue;
            }

            if (($field['required'] ?? false) && ($values[$name] ?? '') === '') {
                $errors[$name] = $field['label'] . ' diperlukan.';
            }

            if ($type === 'date' && ($values[$name] ?? '') !== '' && !$this->dateFormatter->isValidFormDate($values[$name])) {
                $errors[$name] = $field['label'] . ' tidak sah.';
            }

            if ($type === 'email' && ($values[$name] ?? '') !== '' && filter_var($values[$name], FILTER_VALIDATE_EMAIL) === false) {
                $errors[$name] = 'Format emel tidak sah.';
            }

            if (in_array($name, ['COHPhoneNo', 'COHPhoneNo2', 'COOffTelNo'], true) && strlen($values[$name] ?? '') > 14) {
                $errors[$name] = $field['label'] . ' tidak boleh melebihi 14 aksara.';
            }

            if (in_array($name, ['COOffTelNoExtn', 'COOffTelNoExtn2', 'COOUCTelNo'], true) && strlen($values[$name] ?? '') > 6) {
                $errors[$name] = $field['label'] . ' tidak boleh melebihi 6 aksara.';
            }
        }

        if (($values['COEmail'] ?? '') !== '' && $this->emailExistsForOtherStaff($values['COEmail'], $targetIcno)) {
            $errors['COEmail'] = 'Emel rasmi sudah wujud. Sila masukan emel yang lain.';
        }

        return $errors;
    }

    /**
     * @param list<array<string, mixed>> $fields
     * @param array<string, string> $values
     * @return array<string, string|null>
     */
    private function recordFromValues(array $fields, array $values): array
    {
        $record = [];
        foreach ($fields as $field) {
            $type = (string) ($field['type'] ?? 'text');
            if ($type === 'static') {
                continue;
            }

            $name = (string) $field['name'];
            $value = $values[$name] ?? '';
            $record[$name] = $type === 'date' && $value === '' ? null : $value;
        }

        return $record;
    }

    private function emailExistsForOtherStaff(string $email, string $targetIcno): bool
    {
        return (bool) $this->db->createCommand(
            'SELECT 1 FROM ' . self::TABLE . ' WHERE COEmail = :email AND ICNO <> :icno LIMIT 1',
            [':email' => $email, ':icno' => $targetIcno],
        )->queryScalar();
    }

    /**
     * @return array<string, list<array{id: string, name: string}>>
     */
    private function options(): array
    {
        return [
            'titles' => $this->referenceData->titles(),
            'religions' => $this->referenceData->religions(),
            'races' => $this->referenceData->races(),
            'ethnicities' => $this->referenceData->ethnicities(),
            'uniformStatuses' => $this->referenceData->uniformStatuses(),
            'bloodTypes' => $this->referenceData->bloodTypes(),
            'genders' => $this->referenceData->genders(),
            'maritalStatuses' => $this->referenceData->maritalStatuses(),
            'countries' => $this->referenceData->countries(),
            'states' => $this->referenceData->options('SELECT StateCd, State FROM hronline.state ORDER BY State', 'StateCd', 'State'),
            'nationalityStatuses' => $this->referenceData->nationalityStatuses(),
            'sabahStatuses' => [['id' => '1', 'name' => 'Sabah'], ['id' => '0', 'name' => 'Bukan Sabah']],
            'programs' => $this->referenceData->programs(),
            'administrativePositions' => $this->referenceData->administrativePositions(),
            'jobGrades' => $this->referenceData->jobGrades(),
            'departments' => $this->referenceData->departments(),
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
