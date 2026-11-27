<?php

declare(strict_types=1);

namespace App\Shared;

use DateTimeImmutable;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class LantikanKhasService
{
    private const DEFAULT_PASSWORD_HASH = '0f8ce4a6a430a9e48cae55ea9c0f84fd';

    public function __construct(
        private ConnectionInterface $db,
        private StaffDateFormatter $dateFormatter,
        private LantikanReferenceService $references,
        private ActiveDirectoryApiClient $activeDirectory,
    ) {}

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, values: array<string, string>, errors: array<string, string>, proposed: list<string>, plan?: array<string, mixed>}
     */
    public function dryRun(array $input): array
    {
        $result = $this->prepare($input);
        $proposed = [
            'Dry-run only: no Khas appointment records were inserted.',
            'Would reserve UMSPERPKS with an advisory lock before live insert.',
            'Would insert core hronline.* Khas appointment records in one transaction.',
            'Would call AD create, IT/admin notifications, access-management follow-up, and allocation handoff only after commit when enabled.',
            'STARS/security/medical side effects remain disabled until owner approval.',
        ];

        if (!$this->activeDirectory->enabled()) {
            $proposed[] = 'AD email existence check skipped because AD API is disabled locally.';
        }

        $result['proposed'] = $proposed;

        return $result;
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, values: array<string, string>, errors: array<string, string>, proposed: list<string>, plan?: array<string, mixed>}
     */
    private function prepare(array $input): array
    {
        $fields = $this->references->appointmentFields('khas');
        $values = $this->normalize($fields, $input);
        $errors = $this->validate($fields, $values);

        $result = [
            'success' => $errors === [],
            'values' => $values,
            'errors' => $errors,
            'proposed' => [],
        ];

        if ($errors === []) {
            $result['plan'] = $this->insertPlan($values);
        }

        return $result;
    }

    /**
     * @param list<array{name: string, label: string, type: string, options?: string, required: bool}> $fields
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    private function normalize(array $fields, array $input): array
    {
        $values = [];
        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'];
            $values[$name] = $type === 'checkbox'
                ? (isset($input[$name]) && (string) $input[$name] === '1' ? '1' : '0')
                : trim((string) ($input[$name] ?? ''));
        }

        $values['COEmail'] = strtolower($values['COEmail'] ?? '');
        $values['CONm'] = strtoupper($values['CONm'] ?? '');
        $values['gredJawatan_hakiki'] = $values['gredJawatan'] ?? '';
        $values['KodProgram'] = '';

        return $values;
    }

    /**
     * @param list<array{name: string, label: string, type: string, options?: string, required: bool}> $fields
     * @param array<string, string> $values
     * @return array<string, string>
     */
    private function validate(array $fields, array $values): array
    {
        $errors = [];
        $options = $this->references->options();

        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'];
            $value = $values[$name] ?? '';

            if (($field['required'] ?? false) && $value === '') {
                $errors[$name] = $field['label'] . ' diperlukan.';
                continue;
            }

            if ($type === 'email' && $value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                $errors[$name] = 'Format emel tidak sah.';
            }

            if ($type === 'date' && $value !== '' && !$this->dateFormatter->isValidFormDate($value)) {
                $errors[$name] = $field['label'] . ' tidak sah.';
            }

            if ($type === 'select' && $value !== '' && isset($field['options'])) {
                $allowed = array_column($options[$field['options']] ?? [], 'id');
                if ($allowed !== [] && !in_array($value, array_map('strval', $allowed), true)) {
                    $errors[$name] = $field['label'] . ' tidak sah.';
                }
            }
        }

        if ((string) ($values['statLantikan'] ?? '') !== '2') {
            $errors['statLantikan'] = 'Status Lantikan Khas hanya membenarkan kod 2.';
        }

        $this->validateEmail($values, $errors);
        $this->validateUniqueness($values, $errors);
        $this->validateDateRange($values, $errors);

        return $errors;
    }

    /**
     * @param array<string, string> $values
     * @param array<string, string> $errors
     */
    private function validateEmail(array $values, array &$errors): void
    {
        $email = $values['COEmail'] ?? '';
        if ($email === '' || isset($errors['COEmail'])) {
            return;
        }

        $parts = explode('@', $email);
        if (count($parts) !== 2 || $parts[1] !== 'ums.edu.my') {
            $errors['COEmail'] = 'Emel rasmi tidak mengandungi @ums.edu.my. Sila masukkan email rasmi yang baru.';
            return;
        }

        if (strlen($parts[0]) > 20) {
            $errors['COEmail'] = 'Emel melebihi 20 abjad. Sila ubah secara manual.';
            return;
        }

        if (strlen($email) > 41) {
            $errors['COEmail'] = 'Email too long.';
            return;
        }

        if ($this->activeDirectory->enabled()) {
            $emailCheck = $this->activeDirectory->find($email, 'EMAIL');
            if (!$emailCheck['success']) {
                $errors['COEmail'] = $emailCheck['message'];
            } elseif (($emailCheck['data']['exists'] ?? false) === true) {
                $errors['COEmail'] = 'Emel rasmi sedang digunakan. Sila masukkan email rasmi yang baru.';
            }
        }
    }

    /**
     * @param array<string, string> $values
     * @param array<string, string> $errors
     */
    private function validateUniqueness(array $values, array &$errors): void
    {
        $icno = $values['ICNO'] ?? '';
        if ($icno !== '' && (bool) $this->db->createCommand(
            'SELECT 1 FROM hronline.tblprcobiodata WHERE ICNO = :icno LIMIT 1',
            [':icno' => $icno],
        )->queryScalar()) {
            $errors['ICNO'] = 'No. KP / Paspot sudah wujud.';
        }

        $email = $values['COEmail'] ?? '';
        if ($email !== '' && (bool) $this->db->createCommand(
            'SELECT 1 FROM hronline.tblprcobiodata WHERE COEmail = :email LIMIT 1',
            [':email' => $email],
        )->queryScalar()) {
            $errors['COEmail'] = 'Emel rasmi sudah wujud. Sila masukan Emel yang lain.';
        }
    }

    /**
     * @param array<string, string> $values
     * @param array<string, string> $errors
     */
    private function validateDateRange(array $values, array &$errors): void
    {
        $start = $this->date($values['startDateLantik'] ?? '');
        $end = $this->date($values['endDateLantik'] ?? '');
        if ($start !== null && $end !== null && $end < $start) {
            $errors['endDateLantik'] = 'Tarikh Tamat Lantikan tidak boleh lebih awal daripada Tarikh Mula Lantikan.';
        }
    }

    /**
     * @param array<string, string> $values
     * @return array<string, mixed>
     */
    private function insertPlan(array $values, string $createdBy = 'YII3-LANTIKAN-KHAS', ?string $now = null): array
    {
        $umsper = $this->nextUmsperPks($values['startDateLantik']);
        $derived = $this->derivedValues($values, $umsper);
        $vcIcno = $this->vcIcno();
        $now ??= date('Y-m-d H:i:s');

        return [
            'summary' => [
                'ICNO' => $values['ICNO'],
                'COOldID' => $umsper['COOldID'],
                'COOldIDNo' => $umsper['COOldIDNo'],
                'COOldIDDt' => $umsper['COOldIDDt'],
                'LiveReservation' => '0',
                'ConfirmStatusCd' => '8',
                'PsnStatusCd' => '04',
                'PsnAthyCd' => '99',
                'SalMoveMth' => $derived['SalMoveMth'],
                'ServTypeCd' => 'SSM',
                'UmsperSource' => 'hronline.umsperpks',
            ],
            'coreTables' => [
                'hronline.tblprcobiodata' => [
                    'ICNO' => $values['ICNO'],
                    'HighestEduLevelCd' => $values['HighestEduLevelCd'],
                    'ReligionCd' => $values['ReligionCd'],
                    'RaceCd' => $values['RaceCd'],
                    'EthnicCd' => $values['EthnicCd'],
                    'ArmyPoliceCd' => $values['ArmyPoliceCd'],
                    'BloodTypeCd' => $values['BloodTypeCd'],
                    'MrtlStatusCd' => $values['MrtlStatusCd'],
                    'TitleCd' => $values['TitleCd'],
                    'ConfermentDt' => $values['ConfermentDt'],
                    'GenderCd' => $values['GenderCd'],
                    'COBirthPlaceCd' => $values['COBirthPlaceCd'],
                    'COBirthCountryCd' => $values['COBirthCountryCd'],
                    'NegaraAsalCd' => $values['NegaraAsalCd'],
                    'NegeriAsalCd' => $values['NegeriAsalCd'],
                    'NegeriAsalIbu' => $values['NegeriAsalIbu'],
                    'NegeriAsalBapa' => $values['NegeriAsalBapa'],
                    'NatCd' => $values['NatCd'],
                    'NatStatusCd' => $values['NatStatusCd'],
                    'CONm' => $values['CONm'],
                    'COEmail' => $values['COEmail'],
                    'COEmail2' => $values['COEmail2'],
                    'COBumiStatus' => $values['COBumiStatus'],
                    'COOldID' => $umsper['COOldID'],
                    'COBirthCertNo' => $values['COBirthCertNo'],
                    'COBirthDt' => $values['COBirthDt'],
                    'COHPhoneNo' => $values['COHPhoneNo'],
                    'COHPhoneNo2' => $values['COHPhoneNo2'] !== '' ? $values['COHPhoneNo2'] : null,
                    'COOUCTelNo' => $derived['COOUCTelNo'],
                    'COOPass' => self::DEFAULT_PASSWORD_HASH,
                    'COHPhoneStatus' => $values['COHPhoneStatus'],
                    'DeptId' => $values['DeptId'],
                    'DeptId_hakiki' => $values['DeptId'],
                    'campus_id' => $values['campus_id'],
                    'statLantikan' => $values['statLantikan'],
                    'startDateLantik' => $values['startDateLantik'],
                    'endDateLantik' => $values['endDateLantik'],
                    'Status' => '1',
                    'startDateStatus' => $values['startDateLantik'],
                    'noWaran' => $values['noWaran'] !== '' ? $values['noWaran'] : null,
                    'gredJawatan' => $values['gredJawatan'],
                    'gredJawatan_hakiki' => $values['gredJawatan'],
                    'statSandangan' => $values['statSandangan'],
                    'startDateSandangan' => $values['startDateLantik'],
                    'gredJawatan_2' => $values['gredJawatan'],
                    'ApmtTypeCd' => $values['ApmtTypeCd'],
                    'last_update' => $now,
                    'last_updater' => $createdBy,
                    'campus_id_hakiki' => $values['campus_id'],
                    'kemaskini_terakhir' => $now,
                ],
                'hronline.umsperpks' => [
                    'ICNO' => $values['ICNO'],
                    'JobId' => $values['gredJawatan'],
                    'DeptId' => $values['DeptId'],
                    'campus_id' => $values['campus_id'],
                    'COOldID' => $umsper['COOldID'],
                    'StartDate' => $values['startDateLantik'],
                    'COOldIDDt' => $umsper['COOldIDDt'],
                    'COOldIDNo' => $umsper['COOldIDNo'],
                ],
                'hronline.tblrscosandangan' => ['ICNO' => $values['ICNO'], 'gredjawatan' => $values['gredJawatan'], 'sandangan_id' => $values['statSandangan'], 'ApmtTypeCd' => $values['ApmtTypeCd'], 'start_date' => $values['startDateLantik']],
                'hronline.tblrscoapmtstatus' => ['ICNO' => $values['ICNO'], 'ApmtStatusCd' => $values['statLantikan'], 'ApmtStatusStDt' => $values['startDateLantik'], 'ApmtStatusEndDt' => $values['endDateLantik']],
                'hronline.tblrscoservstatus' => ['ICNO' => $values['ICNO'], 'ServStatusCd' => '1', 'ServStatusDtl' => '1', 'ServStatusStDt' => $values['startDateLantik']],
                'hronline.tblpenempatan' => ['ICNO' => $values['ICNO'], 'date_start' => $values['startDateLantik'], 'date_update' => $now, 'dept_id' => $values['DeptId'], 'campus_id' => $values['campus_id'], 'reason_id' => '1', 'remark' => 'Penempatan asal', 'letter_order_refno' => '-', 'date_letter_order' => $values['startDateLantik'], 'letter_refno' => 'Penempatan asal', 'update_by' => $createdBy],
                'hronline.tblrscoaptathy' => ['ICNO' => $values['ICNO'], 'AptAthyCd' => '387', 'ICNOHeadServ' => $vcIcno, 'AptAthyStDt' => $values['startDateLantik']],
                'hronline.tblrscoservload' => ['ICNO' => $values['ICNO'], 'ServLoadCd' => '1', 'ServLoadStDt' => $values['startDateLantik']],
                'hronline.tblrscoconfirmstatus' => ['ICNO' => $values['ICNO'], 'ConfirmStatusCd' => '8', 'ConfirmStatusStDt' => $values['startDateLantik']],
                'hronline.tblrscopsnstatus' => ['ICNO' => $values['ICNO'], 'PsnStatusCd' => '04', 'PsnStatusNo' => '', 'PsnIncomeTaxNo' => '', 'PsnEpfNo' => '', 'PsnStatusStDt' => $values['startDateLantik']],
                'hronline.tblrscopsnathy' => ['ICNO' => $values['ICNO'], 'PsnAthyCd' => '99', 'PsnAthyStDt' => $values['startDateLantik']],
                'hronline.tblrscosaltype' => ['ICNO' => $values['ICNO'], 'SalTypeCd' => '01', 'SalStatus' => '1', 'SalTypeStDt' => $values['startDateLantik']],
                'hronline.tblrscoservtype' => ['ICNO' => $values['ICNO'], 'ServTypeStDt' => $values['startDateLantik'], 'ServTypeCd' => 'SSM'],
                'hronline.tblrscosalmovemth' => ['ICNO' => $values['ICNO'], 'SalMoveMth' => $derived['SalMoveMth'], 'SalMoveMthType' => '1', 'SalMoveMthStDt' => $values['startDateLantik']],
                'hronline.tblrscofileno' => ['ICNO' => $values['ICNO'], 'COFileNo' => $umsper['COOldID'], 'COFileNoEftvDt' => $values['startDateLantik']],
            ],
            'postCommit' => [
                'Active Directory create user',
                'IT/admin notifications',
                'Access-management email request follow-up',
                'Allocation profile handoff',
            ],
            'blockedSideEffects' => $this->blockedSideEffects($values),
        ];
    }

    /**
     * @return array{COOldID: string, COOldIDDt: string, COOldIDNo: string}
     */
    private function nextUmsperPks(string $startDate): array
    {
        $last = $this->db->createCommand(
            'SELECT COOldIDNo FROM hronline.umsperpks ORDER BY COOldIDNo DESC LIMIT 1',
        )->queryScalar();
        $next = sprintf('%05s', ((int) $last) + 1);
        $date = $this->date($startDate) ?? new DateTimeImmutable();
        $datePart = $date->format('ymd');

        return [
            'COOldID' => $datePart . '-' . $next . '(S)',
            'COOldIDDt' => $datePart,
            'COOldIDNo' => $next,
        ];
    }

    /**
     * @param array<string, string> $values
     * @param array{COOldID: string, COOldIDDt: string, COOldIDNo: string} $umsper
     * @return array<string, string>
     */
    private function derivedValues(array $values, array $umsper): array
    {
        $start = $this->date($values['startDateLantik']) ?? new DateTimeImmutable();
        $month = (int) $start->format('m');

        return [
            'COOUCTelNo' => '1' . $umsper['COOldIDNo'],
            'SalMoveMth' => $month < 4 ? '01' : ($month < 7 ? '04' : ($month < 10 ? '07' : '10')),
        ];
    }

    private function vcIcno(): string
    {
        $icno = $this->db->createCommand(
            "SELECT ICNO FROM hronline.tblprcobiodata WHERE Status != '6' AND gredJawatan = 2 LIMIT 1",
        )->queryScalar();

        return is_scalar($icno) && trim((string) $icno) !== '' ? (string) $icno : '-';
    }

    /**
     * @param array<string, string> $values
     * @return list<string>
     */
    private function blockedSideEffects(array $values): array
    {
        if (($values['ApmtTypeCd'] ?? '') !== '2') {
            return [];
        }

        $effects = [
            'attendance.tbl_wp pending STARS owner approval',
            'hrm.myhealth_tblmaxtuntutan pending clinic/medical owner approval',
        ];
        $grade = $this->db->createCommand(
            'SELECT gred FROM hronline.gredjawatan WHERE id = :id LIMIT 1',
            [':id' => $values['gredJawatan'] ?? ''],
        )->queryScalar();
        if (is_scalar($grade) && in_array((string) $grade, ['KP11', 'KP14', 'KP16', 'KP18', 'KP19', 'KP22', 'KP27', 'KP29', 'KP32'], true)) {
            $effects[] = 'keselamatan.tbl_staff_keselamatan pending security owner approval';
        }

        return $effects;
    }

    private function date(string $value): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);

        return $date instanceof DateTimeImmutable && $date->format('Y-m-d') === $value ? $date : null;
    }
}
