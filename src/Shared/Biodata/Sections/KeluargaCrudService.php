<?php

declare(strict_types=1);

namespace App\Shared\Biodata\Sections;

final readonly class KeluargaCrudService
{
    private const KEY = 'family';
    private const CONFIG = [
        'title' => 'Keluarga',
        'backUrl' => '/biodata/keluarga/view',
        'table' => 'hronline.tblprfmy',
        'idColumn' => 'id',
        'icnoColumn' => 'ICNO',
        'createPath' => '/biodata/keluarga/ums-nonums',
        'updatePath' => '/biodata/keluarga/update',
        'deletePath' => '/biodata/keluarga/delete',
        'directory' => 'Maklumat Rekod Kakitangan/keluarga/oku',
        'fields' => [
            ['section' => 'Maklumat Asas Keluarga'],
            ['name' => 'RelCd', 'label' => 'Hubungan Keluarga', 'type' => 'select', 'options' => 'relationships', 'required' => true, 'readonlyOnUpdate' => true],
            ['name' => 'isUms', 'label' => 'Staf UMS', 'type' => 'select', 'options' => 'yesNo', 'required' => false],
            ['name' => 'FamilyId', 'label' => 'No. KP / Paspot', 'type' => 'text', 'required' => true, 'readonlyOnUpdate' => true],
            ['name' => 'IdTypeCd', 'label' => 'Jenis No. KP / Paspot', 'type' => 'select', 'options' => 'idTypes', 'required' => true],
            ['name' => 'FmyNm', 'label' => 'Nama', 'type' => 'text', 'required' => true],
            ['name' => 'TitleCd', 'label' => 'Gelaran', 'type' => 'select', 'options' => 'titles', 'required' => true],
            ['name' => 'FmyDependencyICTypeCd', 'label' => 'Jenis Kad Pengenalan', 'type' => 'select', 'options' => 'icTypes', 'required' => false],
            ['name' => 'FmyPassportNo', 'label' => 'No. Pasport', 'type' => 'text', 'required' => false],
            ['name' => 'FmyBirthCertNo', 'label' => 'No. Sijil Lahir', 'type' => 'text', 'required' => false],
            ['name' => 'FmyBirthDt', 'label' => 'Tarikh Lahir', 'type' => 'date', 'required' => false],
            ['name' => 'FmyBirthPlaceCd', 'label' => 'Tempat Lahir', 'type' => 'select', 'options' => 'states', 'required' => false],
            ['name' => 'GenderCd', 'label' => 'Jantina', 'type' => 'select', 'options' => 'genders', 'required' => true],
            ['name' => 'ReligionCd', 'label' => 'Agama', 'type' => 'select', 'options' => 'religions', 'required' => true],
            ['name' => 'RaceCd', 'label' => 'Bangsa', 'type' => 'select', 'options' => 'races', 'required' => true],
            ['name' => 'HighestEduLevelCd', 'label' => 'Pendidikan Tertinggi', 'type' => 'select', 'options' => 'educationLevels', 'required' => false],
            ['name' => 'NatCd', 'label' => 'Warganegara', 'type' => 'select', 'options' => 'countries', 'required' => true],
            ['name' => 'FmyMomNm', 'label' => 'Nama Ibu', 'type' => 'text', 'required' => false],

            ['section' => 'Status'],
            ['name' => 'MrtlStatusCd', 'label' => 'Taraf Perkahwinan', 'type' => 'select', 'options' => 'maritalStatuses', 'required' => true],
            ['name' => 'FmyTwinStatus', 'label' => 'Status Kembar', 'type' => 'select', 'options' => 'yesNo', 'required' => false],
            ['name' => 'NatStatusCd', 'label' => 'Status Warganegara', 'type' => 'select', 'options' => 'nationalityStatuses', 'required' => true],
            ['name' => 'FmyBumiStatus', 'label' => 'Status Bumiputera', 'type' => 'select', 'options' => 'yesNo', 'required' => false],
            ['name' => 'FmyDisabilityStatus', 'label' => 'Status OKU', 'type' => 'select', 'options' => 'yesNo', 'required' => false],
            ['name' => 'FmyDependencyStatus', 'label' => 'Status Tanggungan', 'type' => 'select', 'options' => 'yesNo', 'required' => false],
            ['name' => 'FmyDependencyCd', 'label' => 'Kebergantungan Kepada Anda', 'type' => 'select', 'options' => 'dependencyTypes', 'required' => false],
            ['name' => 'ChildReliefInd', 'label' => 'Pelepasan Cukai', 'type' => 'select', 'options' => 'yesNo', 'required' => false],
            ['name' => 'FmyDeceaseDt', 'label' => 'Tarikh Kematian', 'type' => 'date', 'required' => false],
            ['name' => 'FmyStatusCd', 'label' => 'Status Pekerjaan', 'type' => 'select', 'options' => 'familyStatuses', 'required' => true],

            ['section' => 'Maklumat Perkahwinan'],
            ['name' => 'FmyMarriageDt', 'label' => 'Tarikh Perkahwinan', 'type' => 'date', 'required' => false],
            ['name' => 'FmyMarriageCertNo', 'label' => 'No. Sijil Perkahwinan', 'type' => 'text', 'required' => false],
            ['name' => 'FmyDivorceDt', 'label' => 'Tarikh Perceraian', 'type' => 'date', 'required' => false],

            ['section' => 'Pekerjaan'],
            ['name' => 'FmyEmployerNm', 'label' => 'Nama Majikan', 'type' => 'text', 'required' => false],
            ['name' => 'CorpBodyTypeCd', 'label' => 'Jenis Majikan', 'type' => 'select', 'options' => 'corporateBodies', 'required' => false],
            ['name' => 'OccSectorCd', 'label' => 'Sektor Pekerjaan', 'type' => 'select', 'options' => 'occupationSectors', 'required' => false],
            ['name' => 'pekerjaan', 'label' => 'Pekerjaan', 'type' => 'select', 'options' => 'occupations', 'required' => false],

            ['section' => 'Alamat'],
            ['name' => 'FmyAddr1', 'label' => 'Alamat 1', 'type' => 'text', 'required' => true],
            ['name' => 'FmyAddr2', 'label' => 'Alamat 2', 'type' => 'text', 'required' => false],
            ['name' => 'FmyAddr3', 'label' => 'Alamat 3', 'type' => 'text', 'required' => false],
            ['name' => 'FmyPostcode', 'label' => 'Poskod', 'type' => 'text', 'required' => true],
            ['name' => 'CountryCd', 'label' => 'Negara', 'type' => 'select', 'options' => 'countries', 'required' => true],
            ['name' => 'StateCd', 'label' => 'Negeri', 'type' => 'select', 'options' => 'states', 'required' => true],
            ['name' => 'CityCd', 'label' => 'Bandar', 'type' => 'select', 'options' => 'cities', 'required' => true],
            ['name' => 'FmyTelNo', 'label' => 'No. Telefon Bimbit', 'type' => 'text', 'required' => false],
            ['name' => 'FmyEmailAddr', 'label' => 'Email', 'type' => 'text', 'required' => false],
            ['name' => 'FmyNextOfKinStatus', 'label' => 'Pewaris', 'type' => 'select', 'options' => 'yesNo', 'required' => false],
            ['name' => 'FmyEmerContactStatus', 'label' => 'No. Dihubungi Sekiranya Kecemasan', 'type' => 'select', 'options' => 'yesNo', 'required' => false],
            ['name' => 'FmyPensionRecipient', 'label' => 'Penerima Pencen', 'type' => 'select', 'options' => 'yesNo', 'required' => false],

            ['section' => 'Kurang Upaya'],
            ['name' => 'dis_nojkm', 'label' => 'No. JKM', 'type' => 'text', 'required' => false],
            ['name' => 'dis_nolaporan_dr', 'label' => 'No. Laporan Doktor', 'type' => 'text', 'required' => false],
            ['name' => 'dis_jeniskecacatan', 'label' => 'Jenis Kecacatan', 'type' => 'select', 'options' => 'disabilityTypes', 'required' => false],
            ['name' => 'dis_tarikh_kad', 'label' => 'Tarikh Kad Dikeluarkan', 'type' => 'date', 'required' => false],
            ['name' => 'dis_filename_kad_oku', 'label' => 'Kad OKU', 'type' => 'file', 'required' => false],
            ['name' => 'dis_filename_sijil_lahir', 'label' => 'Sijil Lahir', 'type' => 'file', 'required' => false],
            ['name' => 'dis_filename_kad_temujanji', 'label' => 'Kad Temujanji', 'type' => 'file', 'required' => false],
            ['name' => 'dis_filename_pengesahan_dr', 'label' => 'Pengesahan Doktor', 'type' => 'file', 'required' => false],

            ['section' => 'Penyakit Kronik / Alahan'],
            ['name' => 'chronic_disease', 'label' => 'Menghidap Penyakit Kronik', 'type' => 'select', 'options' => 'yesNo', 'required' => false],
            ['name' => 'allergic', 'label' => 'Mempunyai Alahan', 'type' => 'select', 'options' => 'yesNo', 'required' => false],
            ['name' => 'MySJ_ID', 'label' => 'ID MySejahtera', 'type' => 'text', 'required' => false],
        ],
    ];

    public function __construct(private BiodataSectionCrudSupport $crudSupport) {}

    public function form(string $icno, ?string $id = null): ?array
    {
        return $this->crudSupport->form(self::CONFIG, self::KEY, $icno, $id);
    }

    public function save(string $icno, array $input, array $files, ?string $id, string $ipAddress): array
    {
        return $this->crudSupport->save(self::CONFIG, $icno, $input, $files, $id, $ipAddress);
    }

    public function delete(string $icno, string $id, string $ipAddress): bool
    {
        return $this->crudSupport->delete(self::CONFIG, $icno, $id, $ipAddress);
    }

    public function key(): string
    {
        return self::KEY;
    }
}
