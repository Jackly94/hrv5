<?php

declare(strict_types=1);

namespace App\Shared;

use App\Shared\Biodata\Support\BiodataSectionUrlService;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class BiodataProfileService
{
    private const SEARCH_PAGE_SIZE = 15;

    public function __construct(
        private ConnectionInterface $db,
        private StaffDateFormatter $dateFormatter,
        private BiodataSectionUrlService $sectionUrls,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function profileForIdentity(string $identityId): ?array
    {
        $icno = $this->icnoFromIdentity($identityId);
        if ($icno === '') {
            return null;
        }

        return $this->profileForIcno($icno);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function profileForIcno(string $icno, string $mode = 'user'): ?array
    {
        $row = $this->profileRow($icno);
        if ($row === null) {
            return null;
        }

        return [
            'icno' => $icno,
            'displayName' => $this->displayName($row),
            'rawName' => (string) ($row['CONm'] ?? ''),
            'staffNo' => $this->value($row['COOldID'] ?? null),
            'email' => $this->value($row['COEmail'] ?? null),
            'profileImage' => 'https://bkums.ums.edu.my/public/picstf/' . strtoupper(sha1($icno)) . '.jpeg',
            'appointmentPeriod' => [
                'startDate' => $this->formatDate($row['startDateLantik'] ?? null),
                'endDate' => $this->formatDate($row['endDateLantik'] ?? null),
            ],
            'fields' => $this->fields($row, $icno),
            'mode' => $mode,
            'sections' => $this->sections($mode, $icno),
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{
     *     filters: array<string, string>,
     *     options: array<string, list<array{id: string, name: string}>>,
     *     rows: list<array<string, string>>,
     *     count: int,
     *     total: int,
     *     limit: int,
     *     page: int,
     *     pageCount: int,
     *     offset: int
     * }
     */
    public function searchProfiles(array $input): array
    {
        $filters = $this->searchFilters($input);
        [$where, $params] = $this->searchWhere($filters);
        $from = <<<SQL
            FROM hronline.tblprcobiodata b
            LEFT JOIN hronline.department dept ON dept.id = b.DeptId
            LEFT JOIN hronline.gredjawatan job ON job.id = b.gredJawatan
            LEFT JOIN hronline.servicestatus service ON service.ServStatusCd = b.Status
            LEFT JOIN hronline.campus campus ON campus.campus_id = b.campus_id
            LEFT JOIN hronline.programpengajaran program ON program.id = b.KodProgram
            SQL;

        $limit = self::SEARCH_PAGE_SIZE;
        $options = $this->searchOptions();
        $educationLabels = $this->optionMap($options['educationLevels']);
        $resultCount = $this->countRows($from, $where, $params);
        $pageCount = max(1, (int) ceil($resultCount / $limit));
        $page = min($this->pageValue($input['page'] ?? null), $pageCount);
        $offset = ($page - 1) * $limit;
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT b.ICNO, b.CONm, b.COOldID, b.COEmail, b.Status AS status_code,
                   b.HighestEduLevelCd AS education_code,
                   dept.fullname AS department_name,
                   job.gred AS job_grade,
                   service.ServStatusNm,
                   campus.campus_name
            {$from}
            WHERE {$where}
            ORDER BY b.CONm
            LIMIT {$limit}
            OFFSET {$offset}
            SQL,
            $params,
        )->queryAll();

        $profiles = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $educationCode = $this->value($row['education_code'] ?? null, '');
            $profiles[] = [
                'icno' => $this->value($row['ICNO'] ?? null, ''),
                'staffNo' => $this->value($row['COOldID'] ?? null),
                'name' => $this->titleCase($row['CONm'] ?? null),
                'grade' => $this->value($row['job_grade'] ?? null),
                'email' => $this->value($row['COEmail'] ?? null),
                'department' => $this->value($row['department_name'] ?? null),
                'education' => $educationCode !== '' ? ($educationLabels[$educationCode] ?? $educationCode) : 'Tiada Rekod',
                'campus' => $this->titleCase($row['campus_name'] ?? null),
                'status' => $this->value($row['ServStatusNm'] ?? null),
                'statusCode' => $this->value($row['status_code'] ?? null, ''),
            ];
        }

        return [
            'filters' => $filters,
            'options' => $options,
            'rows' => $profiles,
            'count' => $resultCount,
            'total' => $this->totalProfiles(),
            'limit' => $limit,
            'page' => $page,
            'pageCount' => $pageCount,
            'offset' => $offset,
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    private function searchFilters(array $input): array
    {
        $nested = $input['TblprcobiodataSearch'] ?? null;
        if (is_array($nested)) {
            $input = array_merge($input, $nested);
        }

        $filters = [
            'carian_kategorijawatan' => $this->filterValue($input['carian_kategorijawatan'] ?? null),
            'carian_programpengajaran' => $this->filterValue($input['carian_programpengajaran'] ?? null),
            'carian_kodprogram' => $this->filterValue($input['carian_kodprogram'] ?? null),
            'campus_id' => $this->filterValue($input['campus_id'] ?? null),
            'DeptId' => $this->filterValue($input['DeptId'] ?? null),
            'statLantikan' => $this->filterValue($input['statLantikan'] ?? null),
            'HighestEduLevelCd' => $this->filterValue($input['HighestEduLevelCd'] ?? null),
            'Status' => $this->filterValue($input['Status'] ?? null),
            'jenis_carian' => $this->filterValue($input['jenis_carian'] ?? '0'),
            'carian_data' => $this->filterValue($input['carian_data'] ?? null),
            'q' => $this->filterValue($input['q'] ?? null),
        ];

        if (!in_array($filters['jenis_carian'], ['0', '1', '2'], true)) {
            $filters['jenis_carian'] = '0';
        }

        return $filters;
    }

    /**
     * @param array<string, string> $filters
     * @return array{0: string, 1: array<string, string>}
     */
    private function searchWhere(array $filters): array
    {
        $where = ['1 = 1'];
        $params = [];

        $exactFilters = [
            'carian_kategorijawatan' => 'job.job_category',
            'carian_programpengajaran' => 'b.KodProgram',
            'carian_kodprogram' => 'program.KodProgram',
            'campus_id' => 'b.campus_id',
            'DeptId' => 'b.DeptId',
            'statLantikan' => 'b.statLantikan',
            'HighestEduLevelCd' => 'b.HighestEduLevelCd',
            'Status' => 'b.Status',
        ];

        foreach ($exactFilters as $key => $column) {
            if ($filters[$key] === '') {
                continue;
            }

            $param = ':' . $key;
            $where[] = "{$column} = {$param}";
            $params[$param] = $filters[$key];
        }

        if ($filters['carian_data'] !== '') {
            $column = match ($filters['jenis_carian']) {
                '1' => 'b.ICNO',
                '2' => 'b.COOldID',
                default => 'b.CONm',
            };
            $where[] = "{$column} LIKE :carian_data";
            $params[':carian_data'] = '%' . $filters['carian_data'] . '%';
        } elseif ($filters['q'] !== '') {
            $where[] = '(b.ICNO LIKE :quick OR b.CONm LIKE :quick OR b.COOldID LIKE :quick)';
            $params[':quick'] = '%' . $filters['q'] . '%';
        }

        return [implode(' AND ', $where), $params];
    }

    /**
     * @return array<string, list<array{id: string, name: string}>>
     */
    private function searchOptions(): array
    {
        return [
            'jobCategories' => [
                ['id' => '1', 'name' => 'Akademik'],
                ['id' => '2', 'name' => 'Pentadbiran'],
            ],
            'programs' => $this->optionsFromSql(
                'SELECT id, NamaProgram FROM hronline.programpengajaran ORDER BY NamaProgram',
                'id',
                'NamaProgram',
            ),
            'programCodes' => $this->optionsFromSql(
                "SELECT DISTINCT KodProgram FROM hronline.programpengajaran WHERE KodProgram IS NOT NULL AND KodProgram <> '' ORDER BY KodProgram",
                'KodProgram',
                'KodProgram',
            ),
            'campuses' => $this->optionsFromSql(
                'SELECT campus_id, campus_name FROM hronline.campus ORDER BY campus_name',
                'campus_id',
                'campus_name',
            ),
            'departments' => $this->optionsFromSql(
                'SELECT id, shortname FROM hronline.department ORDER BY shortname',
                'id',
                'shortname',
            ),
            'appointmentStatuses' => $this->optionsFromSql(
                'SELECT ApmtStatusCd, ApmtStatusNm FROM hronline.appointmentstatus ORDER BY ApmtStatusNm',
                'ApmtStatusCd',
                'ApmtStatusNm',
            ),
            'educationLevels' => $this->educationOptions(),
            'serviceStatuses' => $this->optionsFromSql(
                'SELECT ServStatusCd, ServStatusNm FROM hronline.servicestatus ORDER BY ServStatusNm',
                'ServStatusCd',
                'ServStatusNm',
            ),
            'searchTypes' => [
                ['id' => '0', 'name' => 'Nama'],
                ['id' => '1', 'name' => 'IC'],
                ['id' => '2', 'name' => 'UMSPER'],
            ],
        ];
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function educationOptions(): array
    {
        $options = $this->optionsFromSql(
            'SELECT HighestEduLevelCd, HighestEduLevel FROM hronline.educationallevel ORDER BY HighestEduLevelRank, HighestEduLevel',
            'HighestEduLevelCd',
            'HighestEduLevel',
        );

        if ($options !== []) {
            return $options;
        }

        return $this->optionsFromSql(
            "SELECT DISTINCT HighestEduLevelCd FROM hronline.tblprcobiodata WHERE HighestEduLevelCd IS NOT NULL AND HighestEduLevelCd <> '' ORDER BY HighestEduLevelCd",
            'HighestEduLevelCd',
            'HighestEduLevelCd',
        );
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function optionsFromSql(string $sql, string $idColumn, string $nameColumn): array
    {
        try {
            $rows = $this->db->createCommand($sql)->queryAll();
        } catch (\Throwable) {
            return [];
        }

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
     * @param list<array{id: string, name: string}> $options
     * @return array<string, string>
     */
    private function optionMap(array $options): array
    {
        $map = [];
        foreach ($options as $option) {
            $map[$option['id']] = $option['name'];
        }

        return $map;
    }

    /**
     * @param array<string, string> $params
     */
    private function countRows(string $from, string $where, array $params): int
    {
        return (int) $this->db->createCommand(
            "SELECT COUNT(*) {$from} WHERE {$where}",
            $params,
        )->queryScalar();
    }

    private function totalProfiles(): int
    {
        return (int) $this->db->createCommand(
            'SELECT COUNT(*) FROM hronline.tblprcobiodata',
        )->queryScalar();
    }

    private function filterValue(mixed $value): string
    {
        if (!is_scalar($value)) {
            return '';
        }

        return trim((string) $value);
    }

    private function pageValue(mixed $value): int
    {
        if (!is_scalar($value)) {
            return 1;
        }

        $page = (int) $value;

        return max(1, $page);
    }

    /**
     * @return array<string, string>|null
     */
    public function accessSettings(string $icno): ?array
    {
        $row = $this->db->createCommand(
            <<<SQL
            SELECT ICNO, CONm, COOldID, accessLevel, accessSecondLevel
            FROM hronline.tblprcobiodata
            WHERE ICNO = :icno
            LIMIT 1
            SQL,
            [':icno' => $icno],
        )->queryOne();

        if (!is_array($row)) {
            return null;
        }

        return [
            'icno' => $this->value($row['ICNO'] ?? null, ''),
            'name' => $this->titleCase($row['CONm'] ?? null),
            'staffNo' => $this->value($row['COOldID'] ?? null),
            'accessLevel' => $this->value($row['accessLevel'] ?? null, '0'),
            'accessSecondLevel' => $this->value($row['accessSecondLevel'] ?? null, '0'),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function biodataDetailsForIdentity(string $identityId): ?array
    {
        $icno = $this->icnoFromIdentity($identityId);
        if ($icno === '') {
            return null;
        }

        return $this->biodataDetailsForIcno($icno, false);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function biodataDetailsForIcno(string $icno, bool $includeEmploymentExtras): ?array
    {
        $row = $this->biodataRow($icno);
        if ($row === null) {
            return null;
        }

        $fields = [
            ['label' => 'ICNO', 'value' => $this->value($row['ICNO'] ?? null)],
            ['label' => 'UMSPER', 'value' => $this->value($row['COOldID'] ?? null)],
            ['label' => 'Gelaran', 'value' => $this->value($row['Title'] ?? null, '-')],
            ['label' => 'Nama', 'value' => $this->value($row['CONm'] ?? null)],
            ['label' => 'Agama', 'value' => $this->value($row['Religion'] ?? null)],
            ['label' => 'Bangsa', 'value' => $this->value($row['Race'] ?? null)],
            ['label' => 'Etnik', 'value' => $this->value($row['Ethnic'] ?? null)],
            ['label' => 'Status Uniform', 'value' => $this->value($row['ArmyPolice'] ?? null)],
            ['label' => 'Jenis Darah', 'value' => $this->value($row['BloodType'] ?? null)],
            ['label' => 'Taraf Perkahwinan', 'value' => $this->value($row['MrtlStatus'] ?? null)],
            ['label' => 'Pendidikan Tertinggi', 'value' => $this->value($row['HighestEduLevelCd'] ?? null)],
            ['label' => 'Jantina', 'value' => $this->value($row['Gender'] ?? null)],
            ['label' => 'Negara Lahir', 'value' => $this->value($row['birth_country'] ?? null)],
            ['label' => 'Tempat Lahir', 'value' => $this->value($row['birth_state'] ?? null, 'Not Set')],
            ['label' => 'Negara Asal Staf', 'value' => $this->value($row['origin_country'] ?? null, '-')],
            ['label' => 'Negeri Asal Staf', 'value' => $this->value($row['origin_state'] ?? null, '-')],
            ['label' => 'Negeri Asal Ibu', 'value' => $this->value($row['mother_state'] ?? null, '-')],
            ['label' => 'Negeri Asal Bapa', 'value' => $this->value($row['father_state'] ?? null, '-')],
            ['label' => 'Warganegara', 'value' => $this->value($row['nationality_country'] ?? null)],
            ['label' => 'Status Warganegara', 'value' => $this->value($row['NatStatus'] ?? null)],
            ['label' => 'Status Bumiputera', 'value' => ((string) ($row['COBumiStatus'] ?? '0')) === '1' ? 'Bumiputera' : 'Bukan Bumiputera'],
            ['label' => 'Status Sabah', 'value' => $this->sabahStatus($row['isSabahan'] ?? null)],
            ['label' => 'E-mel Rasmi (@ums.edu.my)', 'value' => $this->value($row['COEmail'] ?? null)],
            ['label' => 'No. Sijil Lahir', 'value' => $this->value($row['COBirthCertNo'] ?? null)],
            ['label' => 'Tarikh Lahir', 'value' => $this->formatDate($row['COBirthDt'] ?? null)],
            ['label' => 'No. Telefon Bimbit', 'value' => $this->value($row['COHPhoneNo'] ?? null)],
            ['label' => 'No. Telefon Bimbit 2', 'value' => $this->value($row['COHPhoneNo2'] ?? null, '-')],
            ['label' => 'No. Telefon Pejabat', 'value' => $this->value($row['COOffTelNo'] ?? null)],
            ['label' => 'No. Sambungan 1', 'value' => $this->value($row['COOffTelNoExtn'] ?? null)],
            ['label' => 'No. UC', 'value' => $this->value($row['COOUCTelNo'] ?? null)],
            ['label' => 'ID MySejahtera', 'value' => $this->value($row['mySejahteraId'] ?? null)],
        ];

        if ($this->value($row['COEmail2'] ?? null, '') !== '') {
            $fields[] = ['label' => 'E-mel Peribadi (jika ada)', 'value' => $this->value($row['COEmail2'] ?? null)];
        }

        if ($this->value($row['COOffTelNoExtn2'] ?? null, '') !== '') {
            $fields[] = ['label' => 'No. Sambungan 2', 'value' => $this->value($row['COOffTelNoExtn2'] ?? null)];
        }

        if ($includeEmploymentExtras) {
            $program = $this->value($row['KodProgram'] ?? null, '');
            $programName = $this->value($row['NamaProgram'] ?? null, '');
            $fields[] = ['label' => 'Kod Program', 'value' => $program !== '' ? $program . ($programName !== '' ? ' ( ' . $programName . ' )' : '') : 'Tidak Berkaitan'];
            $fields[] = ['label' => 'Jawatan Pentadbiran', 'value' => $this->value($row['admin_position_name'] ?? null, 'Tidak Berkaitan')];
            $fields[] = ['label' => 'Gred Jawatan Hakiki', 'value' => $this->value($row['hakiki_job_full_name'] ?? null, 'Not set')];
            $fields[] = ['label' => 'Jabatan Hakiki', 'value' => $this->value($row['hakiki_department_name'] ?? null, 'Not set')];
            $fields[] = ['label' => 'Kampus Hakiki', 'value' => $this->value($row['hakiki_campus_name'] ?? null, 'Not set')];
        }

        return [
            'icno' => $icno,
            'name' => $this->titleCase($row['CONm'] ?? null),
            'includeEmploymentExtras' => $includeEmploymentExtras,
            'fields' => $fields,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function biodataRow(string $icno): ?array
    {
        $row = $this->db->createCommand(
            <<<SQL
            SELECT
                b.ICNO,
                b.COOldID,
                b.CONm,
                b.HighestEduLevelCd,
                b.COBumiStatus,
                b.isSabahan,
                b.COEmail,
                b.COEmail2,
                b.COBirthCertNo,
                b.COBirthDt,
                b.COHPhoneNo,
                b.COHPhoneNo2,
                b.COOffTelNo,
                b.COOffTelNoExtn,
                b.COOffTelNoExtn2,
                b.COOUCTelNo,
                b.mySejahteraId,
                b.KodProgram,
                title.Title,
                religion.Religion,
                race.Race,
                ethnic.Ethnic,
                uniform.ArmyPolice,
                blood.BloodType,
                marital.MrtlStatus,
                gender.Gender,
                birth_country.Country AS birth_country,
                birth_state.State AS birth_state,
                origin_country.Country AS origin_country,
                origin_state.State AS origin_state,
                mother_state.State AS mother_state,
                father_state.State AS father_state,
                nationality_country.Country AS nationality_country,
                nationality_status.NatStatus,
                program.KodProgram AS program_code,
                program.NamaProgram,
                admin_position.name AS admin_position_name,
                hakiki_job.fname AS hakiki_job_full_name,
                hakiki_department.fullname AS hakiki_department_name,
                hakiki_campus.campus_name AS hakiki_campus_name
            FROM hronline.tblprcobiodata b
            LEFT JOIN hronline.title title ON title.TitleCd = b.TitleCd
            LEFT JOIN hronline.religion religion ON religion.ReligionCd = b.ReligionCd
            LEFT JOIN hronline.race race ON race.RaceCd = b.RaceCd
            LEFT JOIN hronline.ethnic ethnic ON ethnic.EthnicCd = b.EthnicCd
            LEFT JOIN hronline.armypolice uniform ON uniform.ArmyPoliceCd = b.ArmyPoliceCd
            LEFT JOIN hronline.bloodtype blood ON blood.BloodTypeCd = b.BloodTypeCd
            LEFT JOIN hronline.maritalstatus marital ON marital.MrtlStatusCd = b.MrtlStatusCd
            LEFT JOIN hronline.gender gender ON gender.GenderCd = b.GenderCd
            LEFT JOIN hronline.country birth_country ON birth_country.CountryCd = b.COBirthCountryCd
            LEFT JOIN hronline.state birth_state ON birth_state.StateCd = b.COBirthPlaceCd
            LEFT JOIN hronline.country origin_country ON origin_country.CountryCd = b.NegaraAsalCd
            LEFT JOIN hronline.state origin_state ON origin_state.StateCd = b.NegeriAsalCd
            LEFT JOIN hronline.state mother_state ON mother_state.StateCd = b.NegeriAsalIbu
            LEFT JOIN hronline.state father_state ON father_state.StateCd = b.NegeriAsalBapa
            LEFT JOIN hronline.country nationality_country ON nationality_country.CountryCd = b.NatCd
            LEFT JOIN hronline.nationalitystatus nationality_status ON nationality_status.NatStatusCd = b.NatStatusCd
            LEFT JOIN hronline.programpengajaran program ON program.id = b.KodProgram
            LEFT JOIN hronline.jawatanpentadbiran admin_position ON admin_position.id = b.jawatanTadbir
            LEFT JOIN hronline.gredjawatan hakiki_job ON hakiki_job.id = b.gredJawatan_hakiki
            LEFT JOIN hronline.department hakiki_department ON hakiki_department.id = b.DeptId_hakiki
            LEFT JOIN hronline.campus hakiki_campus ON hakiki_campus.campus_id = b.campus_id_hakiki
            WHERE b.ICNO = :icno
            LIMIT 1
            SQL,
            [':icno' => $icno],
        )->queryOne();

        return is_array($row) ? $row : null;
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    public function statesForCountry(string $countryCode): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT StateCd, State
            FROM hronline.state
            WHERE CountryCd = :countryCode
            ORDER BY State
            SQL,
            [':countryCode' => $countryCode],
        )->queryAll();

        $states = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                $states[] = [
                    'id' => (string) ($row['StateCd'] ?? ''),
                    'name' => (string) ($row['State'] ?? ''),
                ];
            }
        }

        return $states;
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    public function secondAccessLevels(string $accessLevel): array
    {
        try {
            $rows = $this->db->createCommand(
                <<<SQL
                SELECT id, nama
                FROM hronline.accesssecondlevel
                WHERE jenis = :accessLevel
                ORDER BY nama
                SQL,
                [':accessLevel' => $accessLevel],
            )->queryAll();
        } catch (\Throwable) {
            return [];
        }

        $levels = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                $levels[] = [
                    'id' => (string) ($row['id'] ?? ''),
                    'name' => (string) ($row['nama'] ?? ''),
                ];
            }
        }

        return $levels;
    }

    public function icnoFromIdentity(string $identityId): string
    {
        if (!str_starts_with($identityId, 'staff-')) {
            return '';
        }

        return substr($identityId, strlen('staff-'));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function profileRow(string $icno): ?array
    {
        $row = $this->db->createCommand(
            <<<SQL
            SELECT
                b.ICNO,
                b.CONm,
                b.COOldID,
                b.COEmail,
                b.statLantikan,
                b.startDateLantik,
                b.endDateLantik,
                title.Title,
                campus.campus_name,
                dept.fullname AS department_name,
                dept_hakiki.fullname AS department_hakiki_name,
                job.class_id,
                job.fname AS job_full_name,
                job.nama AS job_name,
                job.gred AS job_grade,
                job.nama_skim AS scheme_name,
                job.gred_skim AS scheme_grade,
                job.gred_no AS scheme_grade_no,
                service.ServStatusNm,
                appointment.ApmtStatusNm,
                admin_post.position_name,
                confirmation.ConfirmStatusNm,
                confirmation.ConfirmStatusCd,
                confirmation.ConfirmStatusStDt,
                salary.SalMoveMth,
                retirement.RetireAgeCd,
                pension.PsnStatusNm,
                pension.PsnStatusCd,
                pension.PsnStatusNo,
                pension.PsnEpfNo,
                kwsp.kwsp_no
            FROM hronline.tblprcobiodata b
            LEFT JOIN hronline.title title ON title.TitleCd = b.TitleCd
            LEFT JOIN hronline.campus campus ON campus.campus_id = b.campus_id
            LEFT JOIN hronline.department dept ON dept.id = b.DeptId
            LEFT JOIN hronline.department dept_hakiki ON dept_hakiki.id = b.DeptId_hakiki
            LEFT JOIN hronline.gredjawatan job ON job.id = b.gredJawatan
            LEFT JOIN hronline.servicestatus service ON service.ServStatusCd = b.Status
            LEFT JOIN hronline.appointmentstatus appointment ON appointment.ApmtStatusCd = b.statLantikan
            LEFT JOIN hronline.rekod_lantikan_terkini admin_post ON admin_post.ICNO = b.ICNO
            LEFT JOIN hronline.v_latest_pengesahan confirmation ON confirmation.ICNO = b.ICNO
            LEFT JOIN hronline.v_latest_bln_gaji salary ON salary.ICNO = b.ICNO
            LEFT JOIN hronline.v_latest_bersara retirement ON retirement.ICNO = b.ICNO
            LEFT JOIN hronline.v_latest_pencen pension ON pension.ICNO = b.ICNO
            LEFT JOIN hronline.tbl_kwsp kwsp ON kwsp.icno = b.ICNO
            WHERE b.ICNO = :icno
            LIMIT 1
            SQL,
            [':icno' => $icno],
        )->queryOne();

        return is_array($row) ? $row : null;
    }

    /**
     * @param array<string, mixed> $row
     * @return list<array{label: string, value: string}>
     */
    private function fields(array $row, string $icno): array
    {
        return [
            ['label' => 'Nama', 'value' => $this->displayName($row)],
            ['label' => 'Kampus', 'value' => $this->titleCase($row['campus_name'] ?? null)],
            ['label' => 'UMSPER', 'value' => $this->value($row['COOldID'] ?? null)],
            ['label' => 'Tempoh Lantikan', 'value' => $this->appointmentPeriod($row)],
            ['label' => 'No. KP / Paspot', 'value' => $icno],
            ['label' => 'Status Lantikan', 'value' => $this->value($row['ApmtStatusNm'] ?? null)],
            ['label' => 'Klasifikasi Perkhidmatan', 'value' => $this->value($row['class_id'] ?? null)],
            ['label' => 'Status Pekerja', 'value' => $this->value($row['ServStatusNm'] ?? null)],
            ['label' => 'Skim Perkhidmatan', 'value' => $this->schemeName($row)],
            ['label' => 'Status Pengesahan Jawatan', 'value' => $this->confirmationStatus($row)],
            ['label' => 'Jawatan Hakiki', 'value' => $this->jobTitle($row, $icno)],
            ['label' => 'Status Pencen/KWSP', 'value' => $this->pensionDisplay($row)],
            ['label' => 'Gelaran Jawatan', 'value' => $this->jobName($row, $icno)],
            ['label' => 'Umur Bersara Wajib (Opsyen)', 'value' => $this->retirementAge($row)],
            ['label' => 'Skim Saraan', 'value' => $this->value($row['scheme_grade'] ?? null)],
            ['label' => 'Bulan Pergerakan Gaji', 'value' => $this->malayMonth($row['SalMoveMth'] ?? null)],
            ['label' => 'JAFPIB Hakiki', 'value' => $this->value($row['department_hakiki_name'] ?? null)],
            ['label' => 'Jawatan Pentadbiran', 'value' => $this->value($row['position_name'] ?? null, 'Tidak Berkaitan')],
            ['label' => 'JAFPIB Semasa', 'value' => $this->value($row['department_name'] ?? null)],
            ['label' => 'Emel', 'value' => $this->value($row['COEmail'] ?? null)],
        ];
    }

    /**
     * @return list<array{label: string, url: string, external: bool}>
     */
    private function sections(string $mode, string $icno): array
    {
        if ($mode === 'admin') {
            $idQuery = static fn (string $path): string => $path . '?' . http_build_query(['id' => $icno]);

            return [
                ['label' => 'Biodata', 'url' => $idQuery('/biodata/lihatbiodatakakitangan'), 'external' => false],
                ['label' => 'Alamat', 'url' => $this->sectionUrls->admin('alamat', $icno), 'external' => false],
                ['label' => 'Pendidikan', 'url' => $this->sectionUrls->admin('pendidikan', $icno), 'external' => false],
                ['label' => 'Bahasa', 'url' => $this->sectionUrls->admin('bahasa', $icno), 'external' => false],
                ['label' => 'Keahlian', 'url' => $this->sectionUrls->admin('keahlian', $icno), 'external' => false],
                ['label' => 'Anugerah', 'url' => $this->sectionUrls->admin('anugerah', $icno), 'external' => false],
                ['label' => 'Paspot & Permit Kerja', 'url' => $this->sectionUrls->admin('pasport-permit', $icno), 'external' => false],
                ['label' => 'Lesen', 'url' => $this->sectionUrls->admin('lesen', $icno), 'external' => false],
                ['label' => 'Akaun', 'url' => $this->sectionUrls->admin('akaun', $icno, 'admin-senarai-akaun'), 'external' => false],
                ['label' => 'Kecacatan', 'url' => $this->sectionUrls->admin('kecacatan', $icno), 'external' => false],
                ['label' => 'Pengalaman Kerja', 'url' => $this->sectionUrls->admin('pengalamankerja', $icno), 'external' => false],
                ['label' => 'Sejarah Perubatan', 'url' => $this->sectionUrls->admin('sejarahperubatan', $icno), 'external' => false],
                ['label' => 'Keluarga', 'url' => $this->sectionUrls->admin('keluarga', $icno), 'external' => false],
                ['label' => 'Bidang Kepakaran', 'url' => $this->sectionUrls->admin('bidangkepakaran', $icno), 'external' => false],
                ['label' => 'Penetapan Pengguna', 'url' => $this->sectionUrls->appendQuery('/biodata/penetapanpengguna', ['icno' => $icno]), 'external' => false],
                ['label' => 'Program Vaksinasi', 'url' => $this->sectionUrls->admin('vaksinasi', $icno, 'admin-view-status-vaksinasi'), 'external' => false],
                ['label' => 'Kelayakan Profesional', 'url' => $this->sectionUrls->admin('kelayakan-profesional', $icno, 'admin-view'), 'external' => false],
            ];
        }

        return [
            ['label' => 'Biodata', 'url' => '/biodata/lihatbiodata', 'external' => false],
            ['label' => 'Alamat', 'url' => $this->sectionUrls->user('alamat'), 'external' => false],
            ['label' => 'Pendidikan', 'url' => $this->sectionUrls->user('pendidikan'), 'external' => false],
            ['label' => 'Bahasa', 'url' => $this->sectionUrls->user('bahasa'), 'external' => false],
            ['label' => 'Keahlian', 'url' => $this->sectionUrls->user('keahlian'), 'external' => false],
            ['label' => 'Anugerah', 'url' => $this->sectionUrls->user('anugerah'), 'external' => false],
            ['label' => 'Paspot & Permit Kerja', 'url' => $this->sectionUrls->user('pasport-permit'), 'external' => false],
            ['label' => 'Lesen', 'url' => $this->sectionUrls->user('lesen'), 'external' => false],
            ['label' => 'Akaun', 'url' => $this->sectionUrls->user('akaun', 'senarai-akaun'), 'external' => false],
            ['label' => 'Kecacatan', 'url' => $this->sectionUrls->user('kecacatan'), 'external' => false],
            ['label' => 'Pengalaman Kerja', 'url' => $this->sectionUrls->user('pengalamankerja'), 'external' => false],
            ['label' => 'Sejarah Perubatan', 'url' => $this->sectionUrls->user('sejarahperubatan'), 'external' => false],
            ['label' => 'Keluarga', 'url' => $this->sectionUrls->user('keluarga'), 'external' => false],
            ['label' => 'Bidang Kepakaran', 'url' => $this->sectionUrls->user('bidangkepakaran'), 'external' => false],
            ['label' => 'Program Vaksinasi', 'url' => $this->sectionUrls->user('vaksinasi', 'index'), 'external' => false],
            ['label' => 'Kelayakan Profesional', 'url' => $this->sectionUrls->user('kelayakan-profesional'), 'external' => false],
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function displayName(array $row): string
    {
        $name = $this->titleCase($row['CONm'] ?? null);
        $title = $this->value($row['Title'] ?? null, '');

        return trim($title . ' ' . $name);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function appointmentPeriod(array $row): string
    {
        return $this->formatDate($row['startDateLantik'] ?? null)
            . ' hingga '
            . $this->formatDate($row['endDateLantik'] ?? null);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function schemeName(array $row): string
    {
        $scheme = $this->value($row['scheme_name'] ?? null, '');
        if ($scheme === '') {
            $scheme = $this->value($row['job_name'] ?? null, '');
        }

        return $scheme !== '' ? $scheme : 'Tiada Rekod';
    }

    /**
     * @param array<string, mixed> $row
     */
    private function confirmationStatus(array $row): string
    {
        $status = $this->value($row['ConfirmStatusNm'] ?? null, '');
        if ($status === '') {
            return 'Maklumat tidak tersedia';
        }

        if ((string) ($row['ConfirmStatusCd'] ?? '') === '1') {
            return $status . ' [ ' . $this->formatDate($row['ConfirmStatusStDt'] ?? null) . ' ]';
        }

        return $status;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function jobTitle(array $row, string $icno): string
    {
        $name = $this->value($row['job_full_name'] ?? null, '');
        if ($name === '') {
            $name = $this->jobName($row, $icno);
        }

        $grade = $this->value($row['job_grade'] ?? null, '');
        if ($grade !== '' && !str_contains($name, '(' . $grade . ')')) {
            return $name . ' (' . $grade . ')';
        }

        return $name !== '' ? $name : 'Tiada Rekod';
    }

    /**
     * @param array<string, mixed> $row
     */
    private function jobName(array $row, string $icno): string
    {
        $name = $this->value($row['job_name'] ?? null, '');
        if ($name === '') {
            return 'Tiada Rekod';
        }

        if ((string) ($row['job_grade'] ?? '') === '' || ((string) ($row['job_full_name'] ?? '')) === $name) {
            return $name;
        }

        if ((string) ($row['job_grade'] ?? '') !== '' && !($row['job_name'] === 'Ketua Pustakawan' && $icno === '800709125093')) {
            return $name . ' (' . $row['job_grade'] . ')';
        }

        return $name;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function pensionDisplay(array $row): string
    {
        $status = $this->value($row['PsnStatusNm'] ?? null, '');
        if ($status !== '') {
            $code = (string) ($row['PsnStatusCd'] ?? '');
            if ($code === '01') {
                $number = $this->value($row['PsnStatusNo'] ?? null, 'Tiada Nombor');
                return $status . ' [ No. KWAP: ' . $number . ' ]';
            }

            if ($code === '04') {
                $number = $this->value($row['kwsp_no'] ?? $row['PsnEpfNo'] ?? null, 'Tiada Maklumat KWSP');
                return $status . ' [ No. KWSP: ' . $number . ' ]';
            }

            return $status;
        }

        return $this->value($row['kwsp_no'] ?? null, 'Tiada Maklumat KWSP');
    }

    /**
     * @param array<string, mixed> $row
     */
    private function retirementAge(array $row): string
    {
        if ((string) ($row['statLantikan'] ?? '') !== '1') {
            return 'Tiada Berkaitan';
        }

        return $this->value($row['RetireAgeCd'] ?? null, '60') . ' Tahun';
    }

    private function malayMonth(mixed $month): string
    {
        $month = str_pad((string) $month, 2, '0', STR_PAD_LEFT);
        $months = [
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Mac',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Jun',
            '07' => 'Julai',
            '08' => 'Ogos',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Disember',
        ];

        return $months[$month] ?? 'Tiada Rekod';
    }

    private function sabahStatus(mixed $value): string
    {
        if ((string) $value === '1') {
            return 'Sabah';
        }

        if ((string) $value === '0') {
            return 'Bukan Sabah';
        }

        return ' ';
    }

    private function formatDate(mixed $value): string
    {
        return $this->dateFormatter->display($value, 'Tiada Rekod');
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
