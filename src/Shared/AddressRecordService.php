<?php

declare(strict_types=1);

namespace App\Shared;

use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class AddressRecordService
{
    private const TABLE = 'hronline.tblpraddr';

    public function __construct(
        private ConnectionInterface $db,
        private AuditTrailService $auditTrail,
        private RecordStatService $recordStat,
        private BiodataTouchService $biodataTouch,
        private ReferenceDataService $referenceData,
        private EmployeeRepository $employees,
    ) {}

    /**
     * @return array{name: string, rows: list<array<string, string>>}|null
     */
    public function listForIcno(string $icno): ?array
    {
        $staff = $this->staffRow($icno);
        if ($staff === null) {
            return null;
        }

        $rows = $this->db->createCommand(
            <<<SQL
            SELECT addr.id, addr.AddrTypeCd, addr.Addr1, addr.Addr2, addr.Addr3,
                   addr.Postcode, addr.TelNo, type.AddrType,
                   country.Country, state.State, city.City
            FROM hronline.tblpraddr addr
            LEFT JOIN hronline.addresstype type ON type.AddrTypeCd = addr.AddrTypeCd
            LEFT JOIN hronline.country country ON country.CountryCd = addr.CountryCd
            LEFT JOIN hronline.state state ON state.StateCd = addr.StateCd
            LEFT JOIN hronline.city city ON city.CityCd = addr.CityCd
            WHERE addr.ICNO = :icno
            ORDER BY addr.AddrTypeCd, addr.id
            SQL,
            [':icno' => $icno],
        )->queryAll();

        $items = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $items[] = [
                'id' => $this->value($row['id'] ?? null, ''),
                'type' => $this->value($row['AddrType'] ?? null, '-'),
                'addr1' => $this->value($row['Addr1'] ?? null, '-'),
                'addr2' => $this->value($row['Addr2'] ?? null, '-'),
                'addr3' => $this->value($row['Addr3'] ?? null, '-'),
                'postcode' => $this->value($row['Postcode'] ?? null, '-'),
                'phone' => $this->value($row['TelNo'] ?? null, '-'),
                'country' => $this->value($row['Country'] ?? null, '-'),
                'state' => $this->value($row['State'] ?? null, '-'),
                'city' => $this->value($row['City'] ?? null, '-'),
            ];
        }

        return [
            'name' => $this->titleCase($staff['CONm'] ?? null),
            'rows' => $items,
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

        return [
            'id' => $this->value($row['id'] ?? null, ''),
            'type' => $this->value($row['AddrType'] ?? null, '-'),
            'addr1' => $this->value($row['Addr1'] ?? null, '-'),
            'addr2' => $this->value($row['Addr2'] ?? null, '-'),
            'addr3' => $this->value($row['Addr3'] ?? null, '-'),
            'postcode' => $this->value($row['Postcode'] ?? null, '-'),
            'phone' => $this->value($row['TelNo'] ?? null, '-'),
            'country' => $this->value($row['Country'] ?? null, '-'),
            'state' => $this->value($row['State'] ?? null, '-'),
            'city' => $this->value($row['City'] ?? null, '-'),
        ];
    }

    /**
     * @return array<string, list<array{id: string, name: string, country?: string, state?: string}>>
     */
    public function formOptions(): array
    {
        return [
            'types' => $this->optionsFromSql(
                'SELECT AddrTypeCd, AddrType FROM hronline.addresstype WHERE isActive = 1 ORDER BY AddrTypeCd',
                'AddrTypeCd',
                'AddrType',
            ),
            'countries' => $this->optionsFromSql(
                'SELECT CountryCd, Country FROM hronline.country WHERE isActive = 1 ORDER BY CountryCd, Country',
                'CountryCd',
                'Country',
            ),
            'states' => $this->locationOptionsFromSql(
                'SELECT StateCd, State, CountryCd FROM hronline.state WHERE isActive = 1 ORDER BY State',
                'StateCd',
                'State',
                'CountryCd',
                'country',
            ),
            'cities' => $this->locationOptionsFromSql(
                'SELECT CityCd, City, StateCd FROM hronline.city WHERE isActive = 1 ORDER BY City',
                'CityCd',
                'City',
                'StateCd',
                'state',
            ),
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
            'addrTypeCd' => '',
            'addr1' => '',
            'addr2' => '',
            'addr3' => '',
            'countryCd' => '',
            'stateCd' => '',
            'cityCd' => '',
            'postcode' => '',
            'telNo' => '',
        ];
    }

    /**
     * @return array<string, string>|null
     */
    public function valuesForEdit(string $icno, string $id): ?array
    {
        $row = $this->rowForIcno($icno, $id);
        if ($row === null) {
            return null;
        }

        return [
            'id' => $this->value($row['id'] ?? null, ''),
            'icno' => $icno,
            'addrTypeCd' => $this->value($row['AddrTypeCd'] ?? null, ''),
            'addr1' => $this->value($row['Addr1'] ?? null, ''),
            'addr2' => $this->value($row['Addr2'] ?? null, ''),
            'addr3' => $this->value($row['Addr3'] ?? null, ''),
            'countryCd' => $this->value($row['CountryCd'] ?? null, ''),
            'stateCd' => $this->value($row['StateCd'] ?? null, ''),
            'cityCd' => $this->value($row['CityCd'] ?? null, ''),
            'postcode' => $this->value($row['Postcode'] ?? null, ''),
            'telNo' => $this->value($row['TelNo'] ?? null, ''),
        ];
    }

    public function hasCurrentAddress(string $icno): bool
    {
        return (bool) $this->db->createCommand(
            'SELECT 1 FROM hronline.tblpraddr WHERE ICNO = :icno AND AddrTypeCd = :type LIMIT 1',
            [':icno' => $icno, ':type' => '01'],
        )->queryScalar();
    }

    /**
     * @return array{success: bool, values: array<string, string>, errors: array<string, string>, id?: string}
     */
    public function copyAddress(string $icno, string $fromType, string $toType, string $ipAddress): array
    {
        $row = $this->db->createCommand(
            'SELECT * FROM hronline.tblpraddr WHERE ICNO = :icno AND AddrTypeCd = :type LIMIT 1',
            [':icno' => $icno, ':type' => $fromType],
        )->queryOne();

        if (!is_array($row)) {
            return [
                'success' => false,
                'values' => $this->defaultValues($icno),
                'errors' => ['general' => 'Alamat rujukan tidak dijumpai.'],
            ];
        }

        $values = [
            'id' => '',
            'icno' => $icno,
            'addrTypeCd' => $toType,
            'addr1' => $this->value($row['Addr1'] ?? null, ''),
            'addr2' => $this->value($row['Addr2'] ?? null, ''),
            'addr3' => $this->value($row['Addr3'] ?? null, ''),
            'countryCd' => $this->value($row['CountryCd'] ?? null, ''),
            'stateCd' => $this->value($row['StateCd'] ?? null, ''),
            'cityCd' => $this->value($row['CityCd'] ?? null, ''),
            'postcode' => $this->value($row['Postcode'] ?? null, ''),
            'telNo' => $this->value($row['TelNo'] ?? null, ''),
        ];

        return $this->save($icno, $values, null, $ipAddress);
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, values: array<string, string>, errors: array<string, string>, id?: string}
     */
    public function save(string $icno, array $input, ?string $id, string $ipAddress): array
    {
        $values = $this->normalizeValues($icno, $input);
        if ($id !== null) {
            $values['id'] = $id;
            $existing = $this->rowForIcno($icno, $id);
            if ($existing === null) {
                return ['success' => false, 'values' => $values, 'errors' => ['general' => 'Rekod alamat tidak dijumpai.']];
            }
            $values['addrTypeCd'] = $this->value($existing['AddrTypeCd'] ?? null, '');
        }

        $errors = $this->validateValues($values, $id);
        if ($errors !== []) {
            return ['success' => false, 'values' => $values, 'errors' => $errors];
        }

        $now = date('Y-m-d H:i:s');
        $record = [
            'ICNO' => $icno,
            'AddrTypeCd' => $values['addrTypeCd'],
            'CountryCd' => $values['countryCd'],
            'StateCd' => $values['stateCd'],
            'CityCd' => $values['cityCd'],
            'Addr1' => $values['addr1'],
            'Addr2' => $values['addr2'],
            'Addr3' => $values['addr3'],
            'Postcode' => $values['postcode'],
            'TelNo' => $values['telNo'],
        ];

        $savedId = $this->db->transaction(function () use ($icno, $id, $record, $now, $ipAddress): string {
            if ($id === null) {
                $this->db->createCommand()->insert(self::TABLE, $record)->execute();
                $savedId = $this->db->getLastInsertId();
                $this->insertStat($icno, $savedId, 0, $now);
                $this->insertUpdateLog($icno, $savedId, 0, $this->createChanges($record), $now, $ipAddress);
            } else {
                $old = $this->rawRowForIcno($icno, $id);
                $this->db->createCommand()->update(self::TABLE, $record, ['id' => $id, 'ICNO' => $icno])->execute();
                $savedId = $id;
                $changes = $this->updateChanges($old ?? [], $record);
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

        return true;
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    public function statesForCountry(string $countryCode): array
    {
        return $this->optionsFromSql(
            'SELECT StateCd, State FROM hronline.state WHERE CountryCd = :code ORDER BY State',
            'StateCd',
            'State',
            [':code' => $countryCode],
        );
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    public function citiesForState(string $stateCode): array
    {
        return $this->optionsFromSql(
            'SELECT CityCd, City FROM hronline.city WHERE StateCd = :code ORDER BY City',
            'CityCd',
            'City',
            [':code' => $stateCode],
        );
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
            SELECT addr.*, type.AddrType, country.Country, state.State, city.City
            FROM hronline.tblpraddr addr
            LEFT JOIN hronline.addresstype type ON type.AddrTypeCd = addr.AddrTypeCd
            LEFT JOIN hronline.country country ON country.CountryCd = addr.CountryCd
            LEFT JOIN hronline.state state ON state.StateCd = addr.StateCd
            LEFT JOIN hronline.city city ON city.CityCd = addr.CityCd
            WHERE addr.ICNO = :icno AND addr.id = :id
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
            'SELECT * FROM hronline.tblpraddr WHERE ICNO = :icno AND id = :id LIMIT 1',
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
            'addrTypeCd' => $this->formValue($input['addrTypeCd'] ?? null),
            'addr1' => $this->formValue($input['addr1'] ?? null),
            'addr2' => $this->formValue($input['addr2'] ?? null),
            'addr3' => $this->formValue($input['addr3'] ?? null),
            'countryCd' => $this->formValue($input['countryCd'] ?? null),
            'stateCd' => $this->formValue($input['stateCd'] ?? null),
            'cityCd' => $this->formValue($input['cityCd'] ?? null),
            'postcode' => $this->formValue($input['postcode'] ?? null),
            'telNo' => $this->formValue($input['telNo'] ?? null),
        ];
    }

    /**
     * @param array<string, string> $values
     * @return array<string, string>
     */
    private function validateValues(array $values, ?string $id): array
    {
        $errors = [];
        foreach ([
            'addrTypeCd' => 'Jenis alamat diperlukan.',
            'addr1' => 'Alamat diperlukan.',
            'countryCd' => 'Negara diperlukan.',
            'stateCd' => 'Negeri diperlukan.',
            'cityCd' => 'Daerah diperlukan.',
            'postcode' => 'Poskod diperlukan.',
            'telNo' => 'No. telefon diperlukan.',
        ] as $field => $message) {
            if ($values[$field] === '') {
                $errors[$field] = $message;
            }
        }

        foreach (['addr1' => 100, 'addr2' => 100, 'addr3' => 100] as $field => $max) {
            if (strlen($values[$field]) > $max) {
                $errors[$field] = 'Tidak boleh melebihi ' . $max . ' aksara.';
            }
        }

        if (strlen($values['telNo']) > 14) {
            $errors['telNo'] = 'No. telefon tidak boleh melebihi 14 aksara.';
        }

        if ($values['addrTypeCd'] !== '' && $this->addressTypeExists($values['icno'], $values['addrTypeCd'], $id)) {
            $errors['addrTypeCd'] = 'Jenis alamat sudah wujud.';
        }

        return $errors;
    }

    private function addressTypeExists(string $icno, string $addressType, ?string $exceptId): bool
    {
        $sql = 'SELECT 1 FROM hronline.tblpraddr WHERE ICNO = :icno AND AddrTypeCd = :type';
        $params = [':icno' => $icno, ':type' => $addressType];

        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
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
     * @param array<string, string|null> $new
     * @return list<array{0: string, 1: string, 2: string|null}>
     */
    private function updateChanges(array $old, array $new): array
    {
        $changes = [];
        foreach ($new as $field => $value) {
            if ((string) ($old[$field] ?? '') !== (string) ($value ?? '')) {
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
            0 => $this->auditTrail->logCreate($icno, self::TABLE, $id, $changes, $ipAddress, 'alamat', $now),
            1 => $this->auditTrail->logUpdate($icno, self::TABLE, $id, $changes, $ipAddress, 'alamat', $now),
            2 => $this->auditTrail->logDelete($icno, self::TABLE, $id, $changes, $ipAddress, 'alamat', $now),
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

    /**
     * @param array<string, string> $params
     * @return list<array{id: string, name: string}>
     */
    private function optionsFromSql(string $sql, string $idColumn, string $nameColumn, array $params = []): array
    {
        return $this->referenceData->options($sql, $idColumn, $nameColumn, $params);
    }

    /**
     * @return list<array{id: string, name: string, country?: string, state?: string}>
     */
    private function locationOptionsFromSql(string $sql, string $idColumn, string $nameColumn, string $parentColumn, string $parentKey): array
    {
        $rows = $this->db->createCommand($sql)->queryAll();
        $options = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $id = $this->value($row[$idColumn] ?? null, '');
            $name = $this->value($row[$nameColumn] ?? null, '');
            $parent = $this->value($row[$parentColumn] ?? null, '');
            if ($id !== '' && $name !== '') {
                $options[] = ['id' => $id, 'name' => $name, $parentKey => $parent];
            }
        }

        return $options;
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
