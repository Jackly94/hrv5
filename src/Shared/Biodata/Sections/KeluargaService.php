<?php

declare(strict_types=1);

namespace App\Shared\Biodata\Sections;

final readonly class KeluargaService
{
    public function __construct(private BiodataSectionOverviewSupport $support) {}

    public function overview(string $icno, bool $adminMode = false): ?array
    {
        return $this->support->overview($icno, [
            'key' => 'keluarga',
            'title' => 'Keluarga',
            'subtitle' => 'Rekod ahli keluarga kakitangan',
            'backUrl' => '/biodata/userview',
            'addUrl' => '/biodata/keluarga/ums-nonums',
            'addLabel' => 'Tambah Keluarga',
        ], [$this->familyGroup($icno, $adminMode)]);
    }

    public function detail(string $id, string $icno): ?array
    {
        if ($id === '') {
            return null;
        }

        $overview = $this->overview($icno);
        if ($overview === null) {
            return null;
        }

        $rows = $this->support->queryAll(
            <<<SQL
            SELECT family.*,
                   relationship.RelNm,
                   idtype.IdType,
                   ictype.ICType,
                   title.Title,
                   birthplace.State AS BirthPlace,
                   gender.Gender,
                   religion.Religion,
                   race.Race,
                   education.HighestEduLevel,
                   nationality.Country AS Nationality,
                   marital.MrtlStatus,
                   natstatus.NatStatus,
                   dependency.DependencyNm,
                   familystatus.FmyStatus,
                   corporate.CorpBodyType,
                   sector.OccSector,
                   country.Country,
                   state.State,
                   city.City,
                   disability.DisabilityType,
                   occupation.nama_pekerjaan
            FROM hronline.tblprfmy family
            LEFT JOIN hronline.relationship relationship ON relationship.RelCd = family.RelCd
            LEFT JOIN hronline.ref_idtype idtype ON idtype.IdTypeCd = family.IdTypeCd
            LEFT JOIN hronline.ictype ictype ON ictype.ICTypeCd = family.FmyDependencyICTypeCd
            LEFT JOIN hronline.title title ON title.TitleCd = family.TitleCd
            LEFT JOIN hronline.state birthplace ON birthplace.StateCd = family.FmyBirthPlaceCd
            LEFT JOIN hronline.gender gender ON gender.GenderCd = family.GenderCd
            LEFT JOIN hronline.religion religion ON religion.ReligionCd = family.ReligionCd
            LEFT JOIN hronline.race race ON race.RaceCd = family.RaceCd
            LEFT JOIN hronline.educationallevel education ON education.HighestEduLevelCd = family.HighestEduLevelCd
            LEFT JOIN hronline.country nationality ON nationality.CountryCd = family.NatCd
            LEFT JOIN hronline.maritalstatus marital ON marital.MrtlStatusCd = family.MrtlStatusCd
            LEFT JOIN hronline.nationalitystatus natstatus ON natstatus.NatStatusCd = family.NatStatusCd
            LEFT JOIN hronline.dependency dependency ON dependency.DependencyCd = family.FmyDependencyCd
            LEFT JOIN hronline.familystatus familystatus ON familystatus.FmyStatusCd = family.FmyStatusCd
            LEFT JOIN hronline.corporatebody corporate ON corporate.CorpBodyTypeCd = family.CorpBodyTypeCd
            LEFT JOIN hronline.occupationsector sector ON sector.OccSectorCd = family.OccSectorCd
            LEFT JOIN hronline.country country ON country.CountryCd = family.CountryCd
            LEFT JOIN hronline.state state ON state.StateCd = family.StateCd
            LEFT JOIN hronline.city city ON city.CityCd = family.CityCd
            LEFT JOIN hronline.disabilitytype disability ON disability.DisabilityTypeCd = family.dis_jeniskecacatan
            LEFT JOIN hronline.ref_pekerjaan occupation ON occupation.id = family.pekerjaan
            WHERE family.ICNO = :icno AND family.id = :id
            LIMIT 1
            SQL,
            [':icno' => $icno, ':id' => $id],
        );

        $row = $rows[0] ?? null;
        if (!is_array($row)) {
            return null;
        }

        return [
            'title' => 'Keluarga',
            'name' => $overview['name'],
            'groupTitle' => 'Maklumat Keluarga',
            'updateUrl' => '/biodata/keluarga/update?id=' . rawurlencode($id),
            'fields' => [
                ...$this->detailFields($row),
                ...$this->okuFields($row),
                ...$this->support->attachments([
                    ['label' => 'Kad OKU', 'hash' => $row['dis_filename_kad_oku'] ?? null],
                    ['label' => 'Sijil Lahir', 'hash' => $row['dis_filename_sijil_lahir'] ?? null],
                    ['label' => 'Kad Temujanji', 'hash' => $row['dis_filename_kad_temujanji'] ?? null],
                    ['label' => 'Surat Pengesahan Doktor', 'hash' => $row['dis_filename_pengesahan_dr'] ?? null],
                ]),
                ['label' => 'Menghidap Penyakit Kronik', 'value' => $this->yesNo($row['chronic_disease'] ?? null)],
                ['label' => 'Mempunyai Alahan', 'value' => $this->yesNo($row['allergic'] ?? null)],
            ],
            'tables' => [
                [
                    'title' => 'Penyakit',
                    'columns' => ['Nama Penyakit'],
                    'rows' => $this->diseaseRows((string) ($row['FamilyId'] ?? ''), '1'),
                ],
                [
                    'title' => 'Alahan',
                    'columns' => ['Nama Alahan'],
                    'rows' => $this->diseaseRows((string) ($row['FamilyId'] ?? ''), '2'),
                ],
            ],
        ];
    }

    public function withActionQuery(array $overview, array $query): array
    {
        return $this->support->withActionQuery($overview, $query);
    }

    private function familyGroup(string $icno, bool $adminMode): array
    {
        $rows = $this->support->queryAll(
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
            'rows' => array_map(fn (array $row): array => $this->support->row(
                $this->support->value($row['id'] ?? null, ''),
                [
                    $this->support->value($row['FamilyId'] ?? null, '-'),
                    $this->support->titleCase($row['FmyNm'] ?? null),
                    $this->support->value($row['RelNm'] ?? null, '-'),
                    ((string) ($row['FmyDisabilityStatus'] ?? '') === '1') ? 'OKU' : 'Tidak',
                ],
                '/biodata/keluarga/lihatkeluarga?id=',
                '/biodata/keluarga/update?id=',
                $adminMode ? '/biodata/keluarga/delete?id=' : '',
                $this->support->attachments([
                    ['label' => 'Kad OKU', 'hash' => $row['dis_filename_kad_oku'] ?? null],
                    ['label' => 'Sijil Lahir', 'hash' => $row['dis_filename_sijil_lahir'] ?? null],
                    ['label' => 'Kad Temujanji', 'hash' => $row['dis_filename_kad_temujanji'] ?? null],
                    ['label' => 'Pengesahan Doktor', 'hash' => $row['dis_filename_pengesahan_dr'] ?? null],
                ]),
            ), $rows),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return list<array{label: string, value: string}>
     */
    private function detailFields(array $row): array
    {
        return [
            ['label' => 'Hubungan', 'value' => $this->support->value($row['RelNm'] ?? null, '-')],
            ['label' => 'Staf UMS', 'value' => $this->yesNo($row['isUms'] ?? null)],
            ['label' => 'No. Pengenalan', 'value' => $this->support->value($row['FamilyId'] ?? null, '-')],
            ['label' => 'Jenis Pengenalan', 'value' => $this->support->value($row['IdType'] ?? null, '-')],
            ['label' => 'Nama', 'value' => $this->support->value($row['FmyNm'] ?? null, '-')],
            ['label' => 'Gelaran', 'value' => $this->support->value($row['Title'] ?? null, '-')],
            ['label' => 'Jenis Kad Pengenalan', 'value' => $this->support->value($row['ICType'] ?? null, 'Tidak Berkaitan')],
            ['label' => 'No. Pasport', 'value' => $this->support->value($row['FmyPassportNo'] ?? null, '-')],
            ['label' => 'No. Sijil Lahir', 'value' => $this->support->value($row['FmyBirthCertNo'] ?? null, '-')],
            ['label' => 'Tarikh Lahir', 'value' => $this->support->formatDate($row['FmyBirthDt'] ?? null, 'Tidak Berkaitan')],
            ['label' => 'Tempat Lahir', 'value' => $this->support->value($row['BirthPlace'] ?? null, '-')],
            ['label' => 'Jantina', 'value' => $this->support->value($row['Gender'] ?? null, '-')],
            ['label' => 'Agama', 'value' => $this->support->value($row['Religion'] ?? null, '-')],
            ['label' => 'Bangsa', 'value' => $this->support->value($row['Race'] ?? null, '-')],
            ['label' => 'Pendidikan Tertinggi', 'value' => $this->support->value($row['HighestEduLevel'] ?? null, '-')],
            ['label' => 'Warganegara', 'value' => $this->support->value($row['Nationality'] ?? null, '-')],
            ['label' => 'Nama Ibu', 'value' => $this->support->value($row['FmyMomNm'] ?? null, '-')],
            ['label' => 'Taraf Perkahwinan', 'value' => $this->support->value($row['MrtlStatus'] ?? null, '-')],
            ['label' => 'Status Kembar', 'value' => ((string) ($row['FmyTwinStatus'] ?? '') === '1') ? 'Ada' : 'Tidak ada'],
            ['label' => 'Status Warganegara', 'value' => $this->support->value($row['NatStatus'] ?? null, '-')],
            ['label' => 'Status Bumiputera', 'value' => ((string) ($row['FmyBumiStatus'] ?? '') === '1') ? 'Bumiputera' : 'Bukan Bumiputera'],
            ['label' => 'Status Tanggungan', 'value' => ((string) ($row['FmyDependencyStatus'] ?? '') === '1') ? 'Tanggungan' : 'Bukan tanggungan'],
            ['label' => 'Jenis Tanggungan', 'value' => $this->support->value($row['DependencyNm'] ?? null, 'Tidak Berkaitan')],
            ['label' => 'Status Pelepasan Cukai', 'value' => $this->yesNo($row['ChildReliefInd'] ?? null)],
            ['label' => 'Tarikh Kematian', 'value' => $this->support->formatDate($row['FmyDeceaseDt'] ?? null, 'Tidak Berkaitan')],
            ['label' => 'Tarikh Perkahwinan', 'value' => $this->support->formatDate($row['FmyMarriageDt'] ?? null, 'Tidak Berkaitan')],
            ['label' => 'No. Sijil Perkahwinan', 'value' => $this->support->value($row['FmyMarriageCertNo'] ?? null, '-')],
            ['label' => 'Tarikh Perceraian', 'value' => $this->support->formatDate($row['FmyDivorceDt'] ?? null, 'Tidak Berkaitan')],
            ['label' => 'Status Pekerjaan', 'value' => $this->support->value($row['FmyStatus'] ?? null, '-')],
            ['label' => 'Nama Majikan', 'value' => $this->support->value($row['FmyEmployerNm'] ?? null, '-')],
            ['label' => 'Jenis Majikan', 'value' => $this->support->value($row['CorpBodyType'] ?? null, 'Tidak Berkaitan')],
            ['label' => 'Sektor Pekerjaan', 'value' => $this->support->value($row['OccSector'] ?? null, '-')],
            ['label' => 'Pekerjaan', 'value' => $this->support->value($row['nama_pekerjaan'] ?? null, '-')],
            ['label' => 'Alamat', 'value' => $this->support->value($row['FmyAddr1'] ?? null, '-')],
            ['label' => 'Alamat 2', 'value' => $this->support->value($row['FmyAddr2'] ?? null, '-')],
            ['label' => 'Alamat 3', 'value' => $this->support->value($row['FmyAddr3'] ?? null, '-')],
            ['label' => 'Poskod', 'value' => $this->support->value($row['FmyPostcode'] ?? null, '-')],
            ['label' => 'Negara', 'value' => $this->support->value($row['Country'] ?? null, '-')],
            ['label' => 'Negeri', 'value' => $this->support->value($row['State'] ?? null, '-')],
            ['label' => 'Bandar', 'value' => $this->support->value($row['City'] ?? null, '-')],
            ['label' => 'No. Telefon', 'value' => $this->support->value($row['FmyTelNo'] ?? null, '-')],
            ['label' => 'No. Dihubungi jika kecemasan', 'value' => $this->yesNo($row['FmyEmerContactStatus'] ?? null)],
            ['label' => 'Email', 'value' => $this->support->value($row['FmyEmailAddr'] ?? null, '-')],
            ['label' => 'Status Pewaris', 'value' => ((string) ($row['FmyNextOfKinStatus'] ?? '') === '1') ? 'Pewaris' : 'Bukan pewaris'],
            ['label' => 'Penerima Pencen', 'value' => ((string) ($row['FmyPensionRecipient'] ?? '') === '1') ? 'Penerima Pencen' : 'Bukan penerima'],
            ['label' => 'ID MySejahtera', 'value' => $this->support->value($row['MySJ_ID'] ?? null, '-')],
            ['label' => 'Status OKU', 'value' => $this->yesNo($row['FmyDisabilityStatus'] ?? null)],
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return list<array{label: string, value: string}>
     */
    private function okuFields(array $row): array
    {
        if ((string) ($row['FmyDisabilityStatus'] ?? '') !== '1') {
            return [];
        }

        return [
            ['label' => 'Status Pengesahan', 'value' => $this->verificationStatus($row['status_pengesahan'] ?? null)],
            ['label' => 'Catatan', 'value' => $this->support->value($row['catatan'] ?? null, '-')],
            ['label' => 'No. JKM', 'value' => $this->support->value($row['dis_nojkm'] ?? null, '-')],
            ['label' => 'No. Laporan Doktor', 'value' => $this->support->value($row['dis_nolaporan_dr'] ?? null, '-')],
            ['label' => 'Jenis Kecacatan', 'value' => $this->support->value($row['DisabilityType'] ?? null, 'Sila kemaskini')],
            ['label' => 'Tarikh Kad Dikeluarkan', 'value' => $this->support->formatDate($row['dis_tarikh_kad'] ?? null, '-')],
        ];
    }

    /**
     * @return list<list<string>>
     */
    private function diseaseRows(string $familyId, string $type): array
    {
        if ($familyId === '') {
            return [];
        }

        $rows = $this->support->queryAll(
            'SELECT description FROM hronline.tblprfmy_disease WHERE FamilyId = :familyId AND type = :type ORDER BY description',
            [':familyId' => $familyId, ':type' => $type],
        );

        return array_map(fn (array $row): array => [$this->support->value($row['description'] ?? null, '-')], $rows);
    }

    private function yesNo(mixed $value): string
    {
        return ((string) $value === '1') ? 'Ya' : 'Tidak';
    }

    private function verificationStatus(mixed $value): string
    {
        return match ((string) $value) {
            '1' => 'Telah Disahkan',
            '2' => 'Ditolak - Perlu penambahbaikkan',
            default => 'Belum disemak',
        };
    }
}
