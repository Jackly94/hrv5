<?php

declare(strict_types=1);

namespace App\Shared;

use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class StaffRecordOverviewService
{
    public function __construct(
        private ConnectionInterface $db,
        private MediaHostFileService $fileService,
        private StaffDateFormatter $dateFormatter,
        private EmployeeRepository $employees,
    ) {}

    public function keyFromPath(string $path): ?string
    {
        return match ($path) {
            '/pendidikan/view', '/pendidikan/lihatpendidikan' => 'pendidikan',
            '/pasport-permit/view', '/pasport-permit/lihatpasport', '/pasport-permit/lihatpermitkerja' => 'pasport-permit',
            '/lesen/view', '/lesen/lihatlesen' => 'lesen',
            '/akaun/senarai-akaun', '/akaun/lihat' => 'akaun',
            '/kecacatan/view', '/kecacatan/lihatkecacatan' => 'kecacatan',
            '/keluarga/view', '/keluarga/lihatkeluarga' => 'keluarga',
            '/bidangkepakaran/view', '/bidangkepakaran/lihatbidangkepakaran' => 'bidangkepakaran',
            '/vaksinasi/index' => 'vaksinasi',
            '/kelayakan-profesional/view', '/kelayakan-profesional/view-cc' => 'kelayakan-profesional',
            default => null,
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    public function overview(string $key, string $icno): ?array
    {
        $staff = $this->staffRow($icno);
        if ($staff === null) {
            return null;
        }

        $definition = $this->definition($key);
        if ($definition === null) {
            return null;
        }

        return [
            ...$definition,
            'key' => $key,
            'name' => $this->titleCase($staff['CONm'] ?? null),
            'groups' => $this->groups($key, $icno),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function detail(string $key, string $id, string $icno, string $variant = ''): ?array
    {
        $overview = $this->overview($key, $icno);
        if ($overview === null || $id === '') {
            return null;
        }

        foreach ($overview['groups'] as $group) {
            if ($variant !== '' && ($group['variant'] ?? '') !== $variant) {
                continue;
            }

            foreach ($group['rows'] as $row) {
                if ($row['id'] === $id) {
                    $fields = array_map(
                        static fn (string $label, string $value): array => ['label' => $label, 'value' => $value],
                        $group['columns'],
                        $row['cells'],
                    );

                    return [
                        'title' => $overview['title'],
                        'name' => $overview['name'],
                        'groupTitle' => $group['title'],
                        'updateUrl' => $row['updateUrl'] ?? '',
                        'fields' => [...$fields, ...($row['attachments'] ?? [])],
                    ];
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function definition(string $key): ?array
    {
        return match ($key) {
            'pendidikan' => [
                'title' => 'Pendidikan',
                'subtitle' => 'Rekod pendidikan kakitangan',
                'backUrl' => '/biodata/userview',
                'addUrl' => '/pendidikan/tambahpendidikan',
                'addLabel' => 'Tambah Pendidikan',
            ],
            'pasport-permit' => [
                'title' => 'Paspot & Permit Kerja',
                'subtitle' => 'Rekod paspot dan permit kerja',
                'backUrl' => '/biodata/userview',
                'addUrl' => '',
                'addLabel' => '',
            ],
            'lesen' => [
                'title' => 'Lesen',
                'subtitle' => 'Rekod lesen kakitangan',
                'backUrl' => '/biodata/userview',
                'addUrl' => '/lesen/tambahlesen',
                'addLabel' => 'Tambah Lesen',
            ],
            'akaun' => [
                'title' => 'Akaun',
                'subtitle' => 'Maklumat akaun bank untuk kemasukan gaji',
                'backUrl' => '/biodata/userview',
                'addUrl' => '/akaun/permohonan-penukaran-akaun-gaji',
                'addLabel' => 'Penukaran Akaun',
            ],
            'kecacatan' => [
                'title' => 'Kecacatan',
                'subtitle' => 'Rekod kecacatan kakitangan',
                'backUrl' => '/biodata/userview',
                'addUrl' => '/kecacatan/tambahkecacatan',
                'addLabel' => 'Tambah Kecacatan',
            ],
            'keluarga' => [
                'title' => 'Keluarga',
                'subtitle' => 'Rekod ahli keluarga kakitangan',
                'backUrl' => '/biodata/userview',
                'addUrl' => '/keluarga/ums-nonums',
                'addLabel' => 'Tambah Keluarga',
            ],
            'bidangkepakaran' => [
                'title' => 'Bidang Kepakaran',
                'subtitle' => 'Rekod bidang kepakaran kakitangan',
                'backUrl' => '/biodata/userview',
                'addUrl' => '/bidangkepakaran/tambahbidangkepakaran',
                'addLabel' => 'Tambah Kepakaran',
            ],
            'vaksinasi' => [
                'title' => 'Program Vaksinasi',
                'subtitle' => 'Rekod vaksinasi kakitangan',
                'backUrl' => '/biodata/userview',
                'addUrl' => '/vaksinasi/view-st-vaksinasi',
                'addLabel' => 'Kemaskini Vaksinasi',
            ],
            'kelayakan-profesional' => [
                'title' => 'Kelayakan Profesional',
                'subtitle' => 'Rekod sijil profesional kakitangan',
                'backUrl' => '/biodata/userview',
                'addUrl' => '/kelayakan-profesional/add-cl-cert',
                'addLabel' => 'Tambah Sijil',
            ],
            default => null,
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function groups(string $key, string $icno): array
    {
        return match ($key) {
            'pendidikan' => $this->educationGroups($icno),
            'pasport-permit' => $this->passportPermitGroups($icno),
            'lesen' => [$this->licenseGroup($icno)],
            'akaun' => [$this->accountGroup($icno)],
            'kecacatan' => [$this->disabilityGroup($icno)],
            'keluarga' => [$this->familyGroup($icno)],
            'bidangkepakaran' => [$this->expertiseGroup($icno)],
            'vaksinasi' => $this->vaccineGroups($icno),
            'kelayakan-profesional' => [$this->professionalCertificateGroup($icno)],
            default => [],
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function educationGroups(string $icno): array
    {
        $groups = [
            ['title' => 'Sarjana', 'ranks' => [1, 2, 3]],
            ['title' => 'Diploma/Setaraf', 'ranks' => [4]],
            ['title' => 'Sijil Kemahiran', 'ranks' => [5]],
            ['title' => 'SPM/Setaraf', 'ranks' => [6]],
            ['title' => 'PMR/PT3/Setaraf', 'ranks' => [7]],
            ['title' => 'Lain-lain Pendidikan', 'ranks' => [8, 9, 10]],
        ];

        return array_map(fn (array $group): array => [
            'title' => $group['title'],
            'variant' => 'education',
            'columns' => ['Institut', 'Tahap Pendidikan', 'Major', 'Nama Sijil (Malay)', 'Tarikh Dianugerahkan', 'Status Pengiktirafan'],
            'rows' => $this->educationRows($icno, $group['ranks']),
        ], $groups);
    }

    /**
     * @param list<int> $ranks
     * @return list<array<string, mixed>>
     */
    private function educationRows(string $icno, array $ranks): array
    {
        $rankList = implode(',', array_map('intval', $ranks));
        $rows = $this->queryAll(
            <<<SQL
            SELECT edu.id, inst.InstNm, level.HighestEduLevel, major.MajorMinor,
                   edu.EduCertTitle, edu.ConfermentDt, edu.AcrtdEduAch, edu.filename
            FROM hronline.tblpreduach edu
            LEFT JOIN hronline.educationallevel level ON level.HighestEduLevelCd = edu.HighestEduLevelCd
            LEFT JOIN hronline.institution inst ON inst.InstCd = edu.InstCd
            LEFT JOIN hronline.majorminor major ON major.MajorMinorCd = edu.MajorCd
            WHERE edu.ICNO = :icno AND level.HighestEduLevelRank IN ({$rankList})
            ORDER BY edu.ConfermentDt DESC, edu.id DESC
            SQL,
            [':icno' => $icno],
        );

        return array_map(fn (array $row): array => $this->row(
            $this->value($row['id'] ?? null, ''),
            [
                $this->value($row['InstNm'] ?? null, '-'),
                $this->value($row['HighestEduLevel'] ?? null, '-'),
                $this->value($row['MajorMinor'] ?? null, 'Tidak Berkaitan'),
                $this->value($row['EduCertTitle'] ?? null, '-'),
                $this->formatDate($row['ConfermentDt'] ?? null),
                ((string) ($row['AcrtdEduAch'] ?? '') === '1') ? 'Diiktiraf' : 'Tidak Berkaitan',
            ],
            '/pendidikan/lihatpendidikan?id=',
            '/pendidikan/update?id=',
            '/pendidikan/delete?id=',
            attachments: $this->attachments([
                ['label' => 'Fail', 'hash' => $row['filename'] ?? null],
            ]),
        ), $rows);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function passportPermitGroups(string $icno): array
    {
        return [
            [
                'title' => 'Paspot',
                'variant' => 'passport',
                'columns' => ['Nombor Paspot', 'Jenis Paspot', 'Warganegara', 'Tempat Lahir', 'Tarikh Dikeluarkan', 'Tarikh Luput'],
                'addUrl' => '/pasport-permit/tambahpasport',
                'addLabel' => 'Tambah Paspot',
                'rows' => $this->passportRows($icno),
            ],
            [
                'title' => 'Permit Kerja',
                'variant' => 'permit',
                'columns' => ['No. Permit Kerja', 'No. Rujukan Imigresen', 'Tarikh Dikeluarkan', 'Tarikh Luput'],
                'addUrl' => '/pasport-permit/tambahpermitkerja',
                'addLabel' => 'Tambah Permit Kerja',
                'rows' => $this->permitRows($icno),
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function passportRows(string $icno): array
    {
        $rows = $this->queryAll(
            <<<SQL
            SELECT passport.id, passport.PassportNo, type.PassportType, country.Country, state.State,
                   passport.IssuedDt, passport.PassportExpiryDt
            FROM hronline.tblprpassport passport
            LEFT JOIN hronline.passport type ON type.PassportTypeCd = passport.PassportTypeCd
            LEFT JOIN hronline.country country ON country.CountryCd = passport.CountryCd
            LEFT JOIN hronline.state state ON state.StateCd = passport.StateCd
            WHERE passport.ICNO = :icno
            ORDER BY passport.PassportExpiryDt DESC, passport.id DESC
            SQL,
            [':icno' => $icno],
        );

        return array_map(fn (array $row): array => $this->row(
            $this->value($row['id'] ?? null, ''),
            [
                $this->value($row['PassportNo'] ?? null, '-'),
                $this->value($row['PassportType'] ?? null, '-'),
                $this->value($row['Country'] ?? null, '-'),
                $this->value($row['State'] ?? null, '-'),
                $this->formatDate($row['IssuedDt'] ?? null),
                $this->formatDate($row['PassportExpiryDt'] ?? null),
            ],
            '/pasport-permit/lihatpasport?id=',
            '/pasport-permit/updatepasport?id=',
            '/pasport-permit/deletepaspot?id=',
        ), $rows);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function permitRows(string $icno): array
    {
        $rows = $this->queryAll(
            <<<SQL
            SELECT id, WrkPermitNo, ImigRefNo, WrkPermitIssueDt, WrkPermitExpiryDt
            FROM hronline.tblprwrkpermit
            WHERE ICNO = :icno
            ORDER BY WrkPermitExpiryDt DESC, id DESC
            SQL,
            [':icno' => $icno],
        );

        return array_map(fn (array $row): array => $this->row(
            $this->value($row['id'] ?? null, ''),
            [
                $this->value($row['WrkPermitNo'] ?? null, '-'),
                $this->value($row['ImigRefNo'] ?? null, '-'),
                $this->formatDate($row['WrkPermitIssueDt'] ?? null),
                $this->formatDate($row['WrkPermitExpiryDt'] ?? null),
            ],
            '/pasport-permit/lihatpermitkerja?id=',
            '/pasport-permit/updatepermitkerja?id=',
            '/pasport-permit/deletepermit?id=',
        ), $rows);
    }

    /**
     * @return array<string, mixed>
     */
    private function licenseGroup(string $icno): array
    {
        $rows = $this->queryAll(
            <<<SQL
            SELECT lic.licId, lic.LicNo, type.LicType, class.LicClass,
                   lic.FirstLicIssuedDt, lic.LicExpiryDt, lic.LicRnwlFee, lic.filename
            FROM hronline.tblprlic lic
            LEFT JOIN hronline.license type ON type.LicCd = lic.LicCd
            LEFT JOIN hronline.licenseclass class ON class.LicClassCd = lic.LicClassCd
            WHERE lic.ICNO = :icno
            ORDER BY lic.LicExpiryDt DESC, lic.licId DESC
            SQL,
            [':icno' => $icno],
        );

        return [
            'title' => 'Senarai Lesen',
            'variant' => 'license',
            'columns' => ['No. Lesen', 'Jenis Lesen', 'Kelas Lesen', 'Tarikh Dikeluarkan', 'Tarikh Luput', 'Yuran Pembaharuan'],
            'rows' => array_map(fn (array $row): array => $this->row(
                $this->value($row['licId'] ?? null, ''),
                [
                    $this->value($row['LicNo'] ?? null, '-'),
                    $this->value($row['LicType'] ?? null, '-'),
                    $this->value($row['LicClass'] ?? null, '-'),
                    $this->formatDate($row['FirstLicIssuedDt'] ?? null),
                    $this->formatDate($row['LicExpiryDt'] ?? null),
                    'RM ' . $this->value($row['LicRnwlFee'] ?? null, '0.00'),
                ],
                '/lesen/lihatlesen?id=',
                '/lesen/update?id=',
                attachments: $this->attachments([
                    ['label' => 'Fail', 'hash' => $row['filename'] ?? null],
                ]),
            ), $rows),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function accountGroup(string $icno): array
    {
        $rows = $this->queryAll(
            <<<SQL
            SELECT id, icno, nama_pemilik, ic_pemilik, no_akaun, kod_bank, status, attachment
            FROM hronline.tbl_akaun
            WHERE icno = :icno
            ORDER BY status ASC, id DESC
            SQL,
            [':icno' => $icno],
        );

        return [
            'title' => 'Senarai Akaun Bank',
            'variant' => 'account',
            'columns' => ['Nama Pemilik Akaun', 'IC/Paspot Pemilik Akaun', 'No. Akaun', 'Bank', 'Status Akaun'],
            'rows' => array_map(fn (array $row): array => $this->row(
                $this->value($row['id'] ?? null, ''),
                [
                    $this->value($row['nama_pemilik'] ?? null, '-'),
                    $this->value($row['ic_pemilik'] ?? null, '-'),
                    $this->value($row['no_akaun'] ?? null, '-'),
                    $this->value($row['kod_bank'] ?? null, '-'),
                    ((string) ($row['status'] ?? '') === '1') ? 'Aktif' : 'Tidak Aktif',
                ],
                '/akaun/lihat?id=',
                '/akaun/permohonan-penukaran-akaun-gaji?id=',
                attachments: $this->attachments([
                    ['label' => 'Lampiran Akaun', 'hash' => $row['attachment'] ?? null],
                ]),
            ), $rows),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function disabilityGroup(string $icno): array
    {
        $rows = $this->queryAll(
            <<<SQL
            SELECT disability.id, disability.SocialWelfareNo, disability.DrRptNo,
                   type.DisabilityType, cause.DisabilityCause,
                   disability.DisabilityDt, disability.HealDt, disability.filename
            FROM hronline.tblprdisability disability
            LEFT JOIN hronline.disabilitytype type ON type.DisabilityTypeCd = disability.DisabilityTypeCd
            LEFT JOIN hronline.disabilitycause cause ON cause.DisabilityCauseCd = disability.DisabilityCauseCd
            WHERE disability.ICNO = :icno
            ORDER BY disability.DisabilityDt DESC, disability.id DESC
            SQL,
            [':icno' => $icno],
        );

        return [
            'title' => 'Senarai Kecacatan',
            'variant' => 'disability',
            'columns' => ['No. Fail Kebajikan', 'No. Laporan Doktor', 'Jenis Kecacatan', 'Punca Kecacatan', 'Tarikh Kecacatan', 'Tarikh Sembuh'],
            'rows' => array_map(fn (array $row): array => $this->row(
                $this->value($row['id'] ?? null, ''),
                [
                    $this->value($row['SocialWelfareNo'] ?? null, '-'),
                    $this->value($row['DrRptNo'] ?? null, '-'),
                    $this->value($row['DisabilityType'] ?? null, '-'),
                    $this->value($row['DisabilityCause'] ?? null, '-'),
                    $this->formatDate($row['DisabilityDt'] ?? null, 'Tidak Berkaitan'),
                    $this->formatDate($row['HealDt'] ?? null, 'Tidak Berkaitan'),
                ],
                '/kecacatan/lihatkecacatan?id=',
                '/kecacatan/update?id=',
                '/kecacatan/delete?id=',
                attachments: $this->attachments([
                    ['label' => 'Fail', 'hash' => $row['filename'] ?? null],
                ]),
            ), $rows),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function familyGroup(string $icno): array
    {
        $rows = $this->queryAll(
            <<<SQL
            SELECT family.id, family.FamilyId, family.FmyNm, family.FmyDisabilityStatus,
                   family.dis_filename_kad_oku, family.dis_filename_sijil_lahir,
                   family.dis_filename_kad_temujanji, family.dis_filename_pengesahan_dr,
                   relationship.RelNm
            FROM hronline.tblprfmy family
            LEFT JOIN hronline.relationship relationship ON relationship.RelCd = family.RelCd
            WHERE family.ICNO = :icno
            ORDER BY relationship.RelNm, family.FmyNm
            SQL,
            [':icno' => $icno],
        );

        return [
            'title' => 'Senarai Keluarga',
            'variant' => 'family',
            'columns' => ['No. Kad Pengenalan', 'Nama', 'Hubungan', 'Status OKU'],
            'rows' => array_map(fn (array $row): array => $this->row(
                $this->value($row['id'] ?? null, ''),
                [
                    $this->value($row['FamilyId'] ?? null, '-'),
                    $this->titleCase($row['FmyNm'] ?? null),
                    $this->value($row['RelNm'] ?? null, '-'),
                    ((string) ($row['FmyDisabilityStatus'] ?? '') === '1') ? 'OKU' : 'Tidak',
                ],
                '/keluarga/lihatkeluarga?id=',
                '/keluarga/update?id=',
                '/keluarga/delete?id=',
                attachments: $this->attachments([
                    ['label' => 'Kad OKU', 'hash' => $row['dis_filename_kad_oku'] ?? null],
                    ['label' => 'Sijil Lahir', 'hash' => $row['dis_filename_sijil_lahir'] ?? null],
                    ['label' => 'Kad Temujanji', 'hash' => $row['dis_filename_kad_temujanji'] ?? null],
                    ['label' => 'Pengesahan Doktor', 'hash' => $row['dis_filename_pengesahan_dr'] ?? null],
                ]),
            ), $rows),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function expertiseGroup(string $icno): array
    {
        $rows = $this->queryAll(
            <<<SQL
            SELECT expertise.id, expertise.bidang, expertise.filename, cluster.kluster
            FROM hronline.tblprcobidangkepakaran expertise
            LEFT JOIN hronline.klusterkepakaran cluster ON cluster.id = expertise.klusterid
            WHERE expertise.ICNO = :icno
            ORDER BY cluster.kluster, expertise.bidang
            SQL,
            [':icno' => $icno],
        );

        return [
            'title' => 'Senarai Bidang Kepakaran',
            'variant' => 'expertise',
            'columns' => ['Kluster Kepakaran', 'Bidang Kepakaran'],
            'rows' => array_map(fn (array $row): array => $this->row(
                $this->value($row['id'] ?? null, ''),
                [
                    $this->value($row['kluster'] ?? null, 'Tidak Berkaitan'),
                    $this->value($row['bidang'] ?? null, '-'),
                ],
                '/bidangkepakaran/lihatbidangkepakaran?id=',
                '/bidangkepakaran/update?id=',
                '/bidangkepakaran/delete?id=',
                attachments: $this->attachments([
                    ['label' => 'Fail', 'hash' => $row['filename'] ?? null],
                ]),
            ), $rows),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function vaccineGroups(string $icno): array
    {
        $statusRows = $this->queryAll(
            'SELECT id, status_vaksin, terima_dos1, terima_dos2, terima_penggalak, catatan, sijil_digital, lampiran FROM hronline.tblstatusvaksinasi WHERE icno = :icno',
            [':icno' => $icno],
        );
        $doseRows = $this->queryAll(
            <<<SQL
            SELECT dose.id, dose.bil_dos, dose.tarikh_vaksin, vaccine.nama_vaksin,
                   dose.tempat_vaksin, dose.batch_vaksin
            FROM hronline.tblpenerima_vaksin dose
            LEFT JOIN hronline.jenis_vaksin vaccine ON vaccine.id = dose.jenis_vaksin
            WHERE dose.icno = :icno
            ORDER BY dose.bil_dos, dose.tarikh_vaksin
            SQL,
            [':icno' => $icno],
        );

        return [
            [
                'title' => 'Status Vaksinasi',
                'variant' => 'vaccine-status',
                'columns' => ['Status Vaksin', 'Dos Pertama', 'Dos Kedua', 'Dos Penggalak', 'Catatan'],
                'rows' => array_map(fn (array $row): array => $this->row(
                    $this->value($row['id'] ?? null, ''),
                    [
                        $this->booleanLabel($row['status_vaksin'] ?? null, 'Sudah Terima', 'Belum Terima'),
                        $this->booleanLabel($row['terima_dos1'] ?? null, 'Terima', 'Belum'),
                        $this->booleanLabel($row['terima_dos2'] ?? null, 'Terima', 'Belum'),
                        $this->booleanLabel($row['terima_penggalak'] ?? null, 'Terima', 'Belum'),
                        $this->value($row['catatan'] ?? null, '-'),
                    ],
                    '',
                    '/vaksinasi/update-status-vaksinasi?id=',
                    attachments: $this->attachments([
                        ['label' => 'Sijil Digital', 'hash' => $row['sijil_digital'] ?? null],
                        ['label' => 'Lampiran Sokongan', 'hash' => $row['lampiran'] ?? null],
                    ]),
                ), $statusRows),
            ],
            [
                'title' => 'Dos Vaksinasi',
                'variant' => 'vaccine-dose',
                'columns' => ['Bil. Dos', 'Tarikh Vaksin', 'Jenis Vaksin', 'Tempat Vaksin', 'Batch Vaksin'],
                'rows' => array_map(fn (array $row): array => $this->row(
                    $this->value($row['id'] ?? null, ''),
                    [
                        $this->value($row['bil_dos'] ?? null, '-'),
                        $this->formatDate($row['tarikh_vaksin'] ?? null),
                        $this->value($row['nama_vaksin'] ?? null, '-'),
                        $this->value($row['tempat_vaksin'] ?? null, '-'),
                        $this->value($row['batch_vaksin'] ?? null, '-'),
                    ],
                    '',
                    '/vaksinasi/update-st-vaksinasi?id=',
                ), $doseRows),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function professionalCertificateGroup(string $icno): array
    {
        $rows = $this->queryAll(
            <<<SQL
            SELECT cert.id, cert.title, type.certType, cert.certNo, cert.startDt, cert.endDt, cert.awardBy, cert.proof
            FROM hronline.tblprprofesionalcert cert
            LEFT JOIN hronline.clinicalcert type ON type.id = cert.type
            WHERE cert.icno = :icno
            ORDER BY cert.startDt DESC, cert.id DESC
            SQL,
            [':icno' => $icno],
        );

        return [
            'title' => 'Sijil Profesional',
            'variant' => 'professional-certificate',
            'columns' => ['Certificate Title', 'Certificate Type', 'Certificate No.', 'Start Date', 'End Date', 'Awarded By'],
            'rows' => array_map(fn (array $row): array => $this->row(
                $this->value($row['id'] ?? null, ''),
                [
                    $this->value($row['title'] ?? null, '-'),
                    $this->value($row['certType'] ?? null, 'Sila kemaskini.'),
                    $this->value($row['certNo'] ?? null, '-'),
                    $this->formatDate($row['startDt'] ?? null),
                    $this->formatDate($row['endDt'] ?? null),
                    $this->value($row['awardBy'] ?? null, '-'),
                ],
                '/kelayakan-profesional/view-cc?id=',
                '/kelayakan-profesional/update-cc?id=',
                '/kelayakan-profesional/delete-cc?id=',
                attachments: $this->attachments([
                    ['label' => 'Bukti Sijil', 'hash' => $row['proof'] ?? null],
                ]),
            ), $rows),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function staffRow(string $icno): ?array
    {
        return $this->employees->byIcno($icno);
    }

    /**
     * @param array<string, string> $params
     * @return list<array<string, mixed>>
     */
    private function queryAll(string $sql, array $params): array
    {
        try {
            $rows = $this->db->createCommand($sql, $params)->queryAll();
        } catch (\Throwable) {
            return [];
        }

        return array_values(array_filter($rows, 'is_array'));
    }

    /**
     * @param list<string> $cells
     * @param list<array{label: string, value: string, url: string, kind: string}> $attachments
     * @return array<string, mixed>
     */
    private function row(
        string $id,
        array $cells,
        string $viewPrefix,
        string $updatePrefix,
        string $deletePrefix = '',
        array $attachments = [],
    ): array {
        return [
            'id' => $id,
            'cells' => $cells,
            'viewUrl' => $viewPrefix === '' || $id === '' ? '' : $viewPrefix . rawurlencode($id),
            'updateUrl' => $this->actionUrl($updatePrefix, $id),
            'deleteUrl' => $this->actionUrl($deletePrefix, $id),
            'attachments' => $attachments,
        ];
    }

    /**
     * @param list<array{label: string, hash: mixed}> $files
     * @return list<array{label: string, value: string, url: string, kind: string}>
     */
    private function attachments(array $files): array
    {
        $attachments = [];

        foreach ($files as $file) {
            $hash = $this->value($file['hash'] ?? null, '');
            $url = $this->fileService->viewUrl($hash);
            if ($url === '') {
                continue;
            }

            $attachments[] = [
                'label' => $this->value($file['label'] ?? null, 'Fail'),
                'value' => 'Lihat Fail',
                'url' => $url,
                'kind' => 'file',
            ];
        }

        return $attachments;
    }

    private function actionUrl(string $prefix, string $id): string
    {
        if ($prefix === '') {
            return '';
        }

        return str_ends_with($prefix, '=') && $id !== '' ? $prefix . rawurlencode($id) : $prefix;
    }

    private function booleanLabel(mixed $value, string $true, string $false): string
    {
        return ((string) $value === '1') ? $true : $false;
    }

    private function formatDate(mixed $value, string $fallback = '-'): string
    {
        return $this->dateFormatter->display($value, $fallback);
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
