<?php

declare(strict_types=1);

namespace App\Shared;

use DateTimeImmutable;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class LantikanStafBaruService
{
    private const DEFAULT_PASSWORD_HASH = '0f8ce4a6a430a9e48cae55ea9c0f84fd';

    /**
     * @var list<string>
     */
    private const SECURITY_GRADES = ['KP11', 'KP14', 'KP16', 'KP18', 'KP19', 'KP22', 'KP27', 'KP29', 'KP32'];

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
        $fields = $this->references->appointmentFields('staf-baru');
        $values = $this->normalize($fields, $input);
        $errors = $this->validate($fields, $values);
        $proposed = [
            'Dry-run only: no appointment records were inserted.',
            'Would reserve UMSPER with an advisory lock before live insert.',
            'Would insert core hronline.* appointment records in one transaction.',
            'Would call AD create and IdP only after commit when live submit is enabled.',
            'STARS/security/medical side effects remain disabled until owner approval.',
        ];

        if (!$this->activeDirectory->enabled()) {
            $proposed[] = 'AD email existence check skipped because AD API is disabled locally.';
        } elseif (!isset($errors['COEmail']) && ($values['COEmail'] ?? '') !== '') {
            $emailCheck = $this->activeDirectory->find($values['COEmail'], 'EMAIL');
            if (!$emailCheck['success']) {
                $errors['COEmail'] = $emailCheck['message'];
            } elseif (($emailCheck['data']['exists'] ?? false) === true) {
                $errors['COEmail'] = 'Emel rasmi sedang digunakan. Sila masukkan email rasmi yang baru.';
            }
        }

        $result = [
            'success' => $errors === [],
            'values' => $values,
            'errors' => $errors,
            'proposed' => $proposed,
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
        if ($email === '') {
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
    private function insertPlan(array $values): array
    {
        $umsper = $this->nextUmsper($values['startDateLantik']);
        $derived = $this->derivedValues($values, $umsper);
        $educationTitle = $this->educationTitle($values['HighestEduLevelCd']);
        $vcIcno = $this->vcIcno();

        return [
            'summary' => [
                'ICNO' => $values['ICNO'],
                'COOldID' => $umsper['COOldID'],
                'COOldIDNo' => $umsper['COOldIDNo'],
                'COOldIDDt' => $umsper['COOldIDDt'],
                'ConfirmStatusCd' => $derived['ConfirmStatusCd'],
                'PsnStatusCd' => $derived['PsnStatusCd'],
                'PsnAthyCd' => $derived['PsnAthyCd'],
                'SalMoveMth' => $derived['SalMoveMth'],
                'ProbtnEndDt' => $derived['ProbtnEndDt'],
            ],
            'coreTables' => [
                'hronline.tblprcobiodata' => [
                    'ICNO' => $values['ICNO'],
                    'CONm' => $values['CONm'],
                    'COOldID' => $umsper['COOldID'],
                    'COEmail' => $values['COEmail'],
                    'Status' => '1',
                    'DeptId_hakiki' => $values['DeptId'],
                    'campus_id_hakiki' => $values['campus_id'],
                    'gredJawatan_2' => $values['gredJawatan'],
                    'COOPass' => self::DEFAULT_PASSWORD_HASH,
                ],
                'hronline.umsper' => [
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
                'hronline.tblrscoservstatus' => ['ICNO' => $values['ICNO'], 'ServStatusCd' => '1', 'ServStatusDtl' => '1', 'ServStatusStDt' => $values['startDateLantik'], 'sebab_berhenti' => '-'],
                'hronline.tblpreduach' => ['ICNO' => $values['ICNO'], 'InstCd' => '999', 'HighestEduLevelCd' => $values['HighestEduLevelCd'], 'EduCertTitle' => $educationTitle, 'OverallGrade' => 'PASS', 'AcrtdEduAch' => '1', 'Bon' => '0'],
                'hronline.tblpenempatan' => ['ICNO' => $values['ICNO'], 'date_start' => $values['startDateLantik'], 'dept_id' => $values['DeptId'], 'campus_id' => $values['campus_id'], 'reason_id' => '1', 'remark' => 'Penempatan asal'],
                'hronline.tblrscoaptathy' => ['ICNO' => $values['ICNO'], 'AptAthyCd' => '387', 'ICNOHeadServ' => $vcIcno, 'AptAthyStDt' => $values['startDateLantik']],
                'hronline.tblrscoservload' => ['ICNO' => $values['ICNO'], 'ServLoadCd' => '1', 'ServLoadStDt' => $values['startDateLantik']],
                'hronline.tblrscoconfirmstatus' => ['ICNO' => $values['ICNO'], 'ConfirmStatusCd' => $derived['ConfirmStatusCd'], 'ConfirmStatusStDt' => $values['startDateLantik']],
                'hronline.tblrscoprobtnperiod' => $values['statLantikan'] === '1' ? ['ICNO' => $values['ICNO'], 'ProbtnPeriod' => '36', 'ProbtnStDt' => $values['startDateLantik'], 'ProbtnEndDt' => $derived['ProbtnEndDt'], 'ProbtnPeriodMin' => '12'] : null,
                'hronline.tblrscopsnstatus' => ['ICNO' => $values['ICNO'], 'PsnStatusCd' => $derived['PsnStatusCd'], 'PsnStatusNo' => '-', 'PsnIncomeTaxNo' => '-', 'PsnEpfNo' => '-', 'PsnStatusStDt' => $values['startDateLantik']],
                'hronline.tblrscopsnathy' => ['ICNO' => $values['ICNO'], 'PsnAthyCd' => $derived['PsnAthyCd'], 'PsnAthyStDt' => $values['startDateLantik']],
                'hronline.tblrscosaltype' => ['ICNO' => $values['ICNO'], 'SalTypeCd' => '01', 'SalStatus' => '1', 'SalTypeStDt' => $values['startDateLantik']],
                'hronline.tblrscoservtype' => ['ICNO' => $values['ICNO'], 'ServTypeStDt' => $values['startDateLantik'], 'ServTypeCd' => 'SSPA'],
                'hronline.tblrscosalmovemth' => ['ICNO' => $values['ICNO'], 'SalMoveMth' => $derived['SalMoveMth'], 'SalMoveMthType' => '1', 'SalMoveMthStDt' => $values['startDateLantik']],
                'hronline.tblrscofileno' => ['ICNO' => $values['ICNO'], 'COFileNo' => $umsper['COOldID'], 'COFileNoEftvDt' => $values['startDateLantik']],
            ],
            'postCommit' => [
                'Active Directory create user',
                'IdP Mata addStaff',
                'IT/admin notifications',
                'Allocation profile handoff',
            ],
            'blockedSideEffects' => $this->blockedSideEffects($values),
        ];
    }

    /**
     * @return array{COOldID: string, COOldIDDt: string, COOldIDNo: string}
     */
    private function nextUmsper(string $startDate): array
    {
        $last = $this->db->createCommand(
            'SELECT COOldIDNo FROM hronline.umsper ORDER BY COOldIDNo DESC LIMIT 1',
        )->queryScalar();
        $next = sprintf('%05s', ((int) $last) + 1);
        $date = $this->date($startDate) ?? new DateTimeImmutable();
        $datePart = $date->format('ymd');

        return [
            'COOldID' => $datePart . '-' . $next,
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
        $confirmStatus = '8';
        $pensionStatus = '04';
        $pensionAuthority = '99';

        if ($values['statLantikan'] === '1') {
            $confirmStatus = '2';
            $pensionStatus = '03';
        }

        $start = $this->date($values['startDateLantik']) ?? new DateTimeImmutable();
        $month = (int) $start->format('m');

        return [
            'COOUCTelNo' => '1' . $umsper['COOldIDNo'],
            'ConfirmStatusCd' => $confirmStatus,
            'PsnStatusCd' => $pensionStatus,
            'PsnAthyCd' => $pensionAuthority,
            'ProbtnEndDt' => $start->modify('+3 years')->format('Y-m-d'),
            'SalMoveMth' => $month < 4 ? '01' : ($month < 7 ? '04' : ($month < 10 ? '07' : '10')),
        ];
    }

    private function educationTitle(string $code): string
    {
        $title = $this->db->createCommand(
            'SELECT HighestEduLevel FROM hronline.educationallevel WHERE HighestEduLevelCd = :code LIMIT 1',
            [':code' => $code],
        )->queryScalar();

        return is_scalar($title) && trim((string) $title) !== '' ? (string) $title : '-';
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

        $effects = ['attendance.tbl_wp pending STARS approval'];
        $grade = $this->db->createCommand(
            'SELECT gred FROM hronline.gredjawatan WHERE id = :id LIMIT 1',
            [':id' => $values['gredJawatan'] ?? ''],
        )->queryScalar();
        if (is_scalar($grade) && in_array((string) $grade, self::SECURITY_GRADES, true)) {
            $effects[] = 'keselamatan.tbl_staff_keselamatan pending security approval';
        }

        if (!in_array((string) ($values['statLantikan'] ?? ''), ['6', '7'], true)) {
            $effects[] = 'hrm.myhealth_tblmaxtuntutan pending medical/clinic approval';
        }

        return $effects;
    }

    private function date(string $value): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);

        return $date instanceof DateTimeImmutable && $date->format('Y-m-d') === $value ? $date : null;
    }
}
