<?php

declare(strict_types=1);

namespace App\Shared;

use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class ReferenceDataService
{
    public function __construct(
        private ConnectionInterface $db,
    ) {}

    /**
     * @param array<string, string> $params
     * @return list<array{id: string, name: string}>
     */
    public function options(string $sql, string $idColumn, string $nameColumn, array $params = []): array
    {
        $rows = $this->db->createCommand($sql, $params)->queryAll();
        $options = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $id = $this->value($row[$idColumn] ?? null, '');
            $name = $this->value($row[$nameColumn] ?? null, '');
            if ($id !== '' && $name !== '') {
                $options[] = ['id' => $id, 'name' => $name];
            }
        }

        return $options;
    }

    public function addressTypes(): array
    {
        return $this->options(
            'SELECT AddrTypeCd, AddrType FROM hronline.addresstype WHERE isActive = 1 ORDER BY AddrTypeCd',
            'AddrTypeCd',
            'AddrType',
        );
    }

    public function countries(): array
    {
        return $this->options(
            'SELECT CountryCd, Country FROM hronline.country WHERE isActive = 1 ORDER BY CountryCd, Country',
            'CountryCd',
            'Country',
        );
    }

    public function statesByCountry(string $countryCode): array
    {
        return $this->options(
            'SELECT StateCd, State FROM hronline.state WHERE CountryCd = :code ORDER BY State',
            'StateCd',
            'State',
            [':code' => $countryCode],
        );
    }

    public function citiesByState(string $stateCode): array
    {
        return $this->options(
            'SELECT CityCd, City FROM hronline.city WHERE StateCd = :code ORDER BY City',
            'CityCd',
            'City',
            [':code' => $stateCode],
        );
    }

    public function licenseTypes(): array
    {
        return $this->options('SELECT LicCd, LicType FROM hronline.license ORDER BY LicType', 'LicCd', 'LicType');
    }

    public function educationLevels(): array
    {
        return $this->options(
            'SELECT HighestEduLevelCd, HighestEduLevel FROM hronline.educationallevel ORDER BY HighestEduLevelRank, HighestEduLevel',
            'HighestEduLevelCd',
            'HighestEduLevel',
        );
    }

    public function institutions(): array
    {
        return $this->options(
            'SELECT InstCd, InstNm FROM hronline.institution WHERE isActive = 1 ORDER BY InstNm',
            'InstCd',
            'InstNm',
        );
    }

    public function majors(): array
    {
        return $this->options(
            'SELECT MajorMinorCd, MajorMinor FROM hronline.majorminor WHERE isActive = 1 ORDER BY MajorMinor',
            'MajorMinorCd',
            'MajorMinor',
        );
    }

    public function passportTypes(): array
    {
        return $this->options(
            'SELECT PassportTypeCd, PassportType FROM hronline.passport ORDER BY PassportType',
            'PassportTypeCd',
            'PassportType',
        );
    }

    public function licenseClasses(): array
    {
        return $this->options('SELECT LicClassCd, LicClass FROM hronline.licenseclass ORDER BY LicClass', 'LicClassCd', 'LicClass');
    }

    public function disabilityTypes(): array
    {
        return $this->options('SELECT DisabilityTypeCd, DisabilityType FROM hronline.disabilitytype ORDER BY DisabilityType', 'DisabilityTypeCd', 'DisabilityType');
    }

    public function disabilityCauses(): array
    {
        return $this->options('SELECT DisabilityCauseCd, DisabilityCause FROM hronline.disabilitycause ORDER BY DisabilityCause', 'DisabilityCauseCd', 'DisabilityCause');
    }

    public function professionalBodies(): array
    {
        return $this->options(
            'SELECT ProfBodyCd, ProfBody FROM hronline.professionalassociation WHERE isActive = 1 ORDER BY ProfBody',
            'ProfBodyCd',
            'ProfBody',
        );
    }

    public function membershipTypes(): array
    {
        return $this->options(
            'SELECT MembershipTypeCd, MembershipType FROM hronline.membershiptype WHERE isActive = 1 ORDER BY MembershipType',
            'MembershipTypeCd',
            'MembershipType',
        );
    }

    public function professionalAssociationLevels(): array
    {
        return $this->options(
            'SELECT id, LvlNm FROM hronline.professional_association_level WHERE isActive = 1 ORDER BY id',
            'id',
            'LvlNm',
        );
    }

    public function clinicalCertificateTypes(): array
    {
        return $this->options(
            'SELECT id, certType FROM hronline.clinicalcert WHERE isActive = 1 ORDER BY id',
            'id',
            'certType',
        );
    }

    public function professionalCertificateFields(): array
    {
        return $this->options(
            'SELECT id, nama_kategori FROM hronline.kategori_sijil_profesional ORDER BY id',
            'id',
            'nama_kategori',
        );
    }

    public function expertiseClusters(): array
    {
        return $this->options(
            'SELECT id, kluster FROM hronline.klusterkepakaran ORDER BY kluster',
            'id',
            'kluster',
        );
    }

    public function relationships(): array
    {
        return $this->options(
            'SELECT RelCd, RelNm FROM hronline.relationship WHERE isActive = 1 ORDER BY RelNm',
            'RelCd',
            'RelNm',
        );
    }

    public function vaccineTypes(): array
    {
        return $this->options(
            'SELECT id, nama_vaksin FROM hronline.jenis_vaksin ORDER BY nama_vaksin',
            'id',
            'nama_vaksin',
        );
    }

    public function banks(): array
    {
        return $this->options(
            "SELECT bank_code, bank_desc FROM hronline.tbl_bank_main WHERE bank_status = 'ACTIVE' ORDER BY bank_desc",
            'bank_code',
            'bank_desc',
        );
    }

    public function titles(): array
    {
        return $this->options('SELECT TitleCd, Title FROM hronline.title ORDER BY Title', 'TitleCd', 'Title');
    }

    public function religions(): array
    {
        return $this->options(
            'SELECT ReligionCd, Religion FROM hronline.religion WHERE isActive = 1 ORDER BY Religion',
            'ReligionCd',
            'Religion',
        );
    }

    public function races(): array
    {
        return $this->options('SELECT RaceCd, Race FROM hronline.race ORDER BY Race', 'RaceCd', 'Race');
    }

    public function ethnicities(): array
    {
        return $this->options('SELECT EthnicCd, Ethnic FROM hronline.ethnic ORDER BY Ethnic', 'EthnicCd', 'Ethnic');
    }

    public function bloodTypes(): array
    {
        return $this->options(
            'SELECT BloodTypeCd, BloodType FROM hronline.bloodtype WHERE isActive = 1 ORDER BY BloodType',
            'BloodTypeCd',
            'BloodType',
        );
    }

    public function genders(): array
    {
        return $this->options('SELECT GenderCd, Gender FROM hronline.gender ORDER BY Gender', 'GenderCd', 'Gender');
    }

    public function maritalStatuses(): array
    {
        return $this->options(
            'SELECT MrtlStatusCd, MrtlStatus FROM hronline.maritalstatus WHERE isActive = 1 ORDER BY MrtlStatus',
            'MrtlStatusCd',
            'MrtlStatus',
        );
    }

    public function nationalityStatuses(): array
    {
        return $this->options(
            'SELECT NatStatusCd, NatStatus FROM hronline.nationalitystatus ORDER BY NatStatus',
            'NatStatusCd',
            'NatStatus',
        );
    }

    public function uniformStatuses(): array
    {
        return $this->options(
            'SELECT ArmyPoliceCd, ArmyPolice FROM hronline.armypolice ORDER BY ArmyPolice',
            'ArmyPoliceCd',
            'ArmyPolice',
        );
    }

    public function programs(): array
    {
        return $this->options(
            'SELECT id, NamaProgram FROM hronline.programpengajaran ORDER BY NamaProgram',
            'id',
            'NamaProgram',
        );
    }

    public function administrativePositions(): array
    {
        return $this->options(
            'SELECT id, name FROM hronline.jawatanpentadbiran ORDER BY name',
            'id',
            'name',
        );
    }

    public function jobGrades(): array
    {
        return $this->options('SELECT id, fname FROM hronline.gredjawatan ORDER BY fname', 'id', 'fname');
    }

    public function departments(): array
    {
        return $this->options('SELECT id, fullname FROM hronline.department ORDER BY fullname', 'id', 'fullname');
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
