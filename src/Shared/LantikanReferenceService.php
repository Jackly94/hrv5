<?php

declare(strict_types=1);

namespace App\Shared;

use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class LantikanReferenceService
{
    public function __construct(
        private ConnectionInterface $db,
        private ReferenceDataService $referenceData,
    ) {}

    /**
     * @return array<string, list<array{id: string, name: string}>>
     */
    public function options(): array
    {
        return [
            'titles' => $this->referenceData->titles(),
            'religions' => $this->referenceData->religions(),
            'races' => $this->referenceData->races(),
            'ethnicities' => $this->referenceData->ethnicities(),
            'bloodTypes' => $this->referenceData->bloodTypes(),
            'genders' => $this->referenceData->genders(),
            'maritalStatuses' => $this->referenceData->maritalStatuses(),
            'countries' => $this->referenceData->countries(),
            'nationalityStatuses' => $this->referenceData->nationalityStatuses(),
            'educationLevels' => $this->referenceData->educationLevels(),
            'uniformStatuses' => $this->referenceData->uniformStatuses(),
            'programs' => $this->referenceData->programs(),
            'jobGrades' => $this->referenceData->jobGrades(),
            'jobGradesNonSpdp' => $this->jobGradesNonSpdp(),
            'jobGradesKhas' => $this->jobGradesKhas(),
            'jobGradesSpdp' => $this->jobGradesSpdp(),
            'appointmentStatuses' => $this->appointmentStatuses(),
            'appointmentStatusesKhas' => $this->appointmentStatusesKhas(),
            'appointmentStatusesPhs' => $this->appointmentStatusesPhs(),
            'appointmentStatusesSpdp' => $this->appointmentStatusesSpdp(),
            'sandanganStatuses' => $this->sandanganStatuses(),
            'appointmentTypes' => $this->appointmentTypes(),
            'departments' => $this->referenceData->departments(),
            'campuses' => $this->campuses(),
            'states' => $this->states(),
            'sabahStatuses' => [
                ['id' => '1', 'name' => 'Sabah'],
                ['id' => '0', 'name' => 'Bukan Sabah'],
            ],
            'jobCategories' => [
                ['id' => '1', 'name' => 'Akademik'],
                ['id' => '2', 'name' => 'Pentadbiran'],
            ],
            'staffStatuses' => $this->staffStatuses(),
        ];
    }

    /**
     * @return list<array{name: string, label: string, type: string, options?: string, required: bool}>
     */
    public function appointmentFields(string $flow): array
    {
        $base = [
            ['name' => 'ICNO', 'label' => 'No. Kad Pengenalan', 'type' => 'text', 'required' => true],
            ['name' => 'COOldID', 'label' => 'UMSPER', 'type' => 'static', 'required' => false],
            ['name' => 'TitleCd', 'label' => 'Gelaran', 'type' => 'select', 'options' => 'titles', 'required' => true],
            ['name' => 'CONm', 'label' => 'Nama', 'type' => 'text', 'required' => true],
            ['name' => 'ReligionCd', 'label' => 'Agama', 'type' => 'select', 'options' => 'religions', 'required' => true],
            ['name' => 'RaceCd', 'label' => 'Bangsa', 'type' => 'select', 'options' => 'races', 'required' => true],
            ['name' => 'EthnicCd', 'label' => 'Etnik', 'type' => 'select', 'options' => 'ethnicities', 'required' => true],
            ['name' => 'BloodTypeCd', 'label' => 'Jenis Darah', 'type' => 'select', 'options' => 'bloodTypes', 'required' => true],
            ['name' => 'GenderCd', 'label' => 'Jantina', 'type' => 'select', 'options' => 'genders', 'required' => true],
            ['name' => 'MrtlStatusCd', 'label' => 'Taraf Perkahwinan', 'type' => 'select', 'options' => 'maritalStatuses', 'required' => true],
            ['name' => 'COBirthCertNo', 'label' => 'No. Sijil Lahir', 'type' => 'text', 'required' => true],
            ['name' => 'COBirthDt', 'label' => 'Tarikh Lahir', 'type' => 'date', 'required' => true],
            ['name' => 'COBirthCountryCd', 'label' => 'Negara Lahir', 'type' => 'select', 'options' => 'countries', 'required' => true],
            ['name' => 'COBirthPlaceCd', 'label' => 'Tempat Lahir', 'type' => 'select', 'options' => 'states', 'required' => true],
            ['name' => 'NegaraAsalCd', 'label' => 'Negara Asal Staf', 'type' => 'select', 'options' => 'countries', 'required' => true],
            ['name' => 'NegeriAsalCd', 'label' => 'Negeri Asal Staf', 'type' => 'select', 'options' => 'states', 'required' => true],
            ['name' => 'NegeriAsalIbu', 'label' => 'Negeri Asal Ibu', 'type' => 'select', 'options' => 'states', 'required' => true],
            ['name' => 'NegeriAsalBapa', 'label' => 'Negeri Asal Bapa', 'type' => 'select', 'options' => 'states', 'required' => true],
            ['name' => 'NatCd', 'label' => 'Warganegara', 'type' => 'select', 'options' => 'countries', 'required' => true],
            ['name' => 'NatStatusCd', 'label' => 'Status Warganegara', 'type' => 'select', 'options' => 'nationalityStatuses', 'required' => true],
            ['name' => 'COBumiStatus', 'label' => 'Bumiputera', 'type' => 'checkbox', 'required' => true],
            ['name' => 'isSabahan', 'label' => 'Status Sabah', 'type' => 'select', 'options' => 'sabahStatuses', 'required' => false],
            ['name' => 'COEmail', 'label' => 'Emel Rasmi', 'type' => 'email', 'required' => true],
            ['name' => 'COEmail2', 'label' => 'Emel Peribadi', 'type' => 'email', 'required' => true],
            ['name' => 'COHPhoneNo', 'label' => 'No. Telefon Bimbit', 'type' => 'text', 'required' => true],
            ['name' => 'COHPhoneNo2', 'label' => 'No. Telefon Bimbit 2', 'type' => 'text', 'required' => false],
            ['name' => 'COHPhoneStatus', 'label' => 'Status Telefon Bimbit', 'type' => 'checkbox', 'required' => false],
            ['name' => 'HighestEduLevelCd', 'label' => 'Pendidikan Tertinggi', 'type' => 'select', 'options' => 'educationLevels', 'required' => true],
            ['name' => 'ConfermentDt', 'label' => 'Tarikh Konvokesyen', 'type' => 'date', 'required' => true],
            ['name' => 'ArmyPoliceCd', 'label' => 'Status Tentera/Polis', 'type' => 'select', 'options' => 'uniformStatuses', 'required' => true],
        ];

        if ($flow === 'staf-baru') {
            $base[] = ['name' => 'KodProgram', 'label' => 'Program Pengajaran', 'type' => 'select', 'options' => 'programs', 'required' => false];
        }

        return [
            ...$base,
            ['name' => 'statLantikan', 'label' => 'Status Lantikan', 'type' => 'select', 'options' => match ($flow) {
                'khas' => 'appointmentStatusesKhas',
                'phs' => 'appointmentStatusesPhs',
                'spdp' => 'appointmentStatusesSpdp',
                default => 'appointmentStatuses',
            }, 'required' => true],
            ['name' => 'startDateLantik', 'label' => 'Tarikh Mula Lantikan', 'type' => 'date', 'required' => true],
            ['name' => 'endDateLantik', 'label' => 'Tarikh Tamat Lantikan', 'type' => 'date', 'required' => true],
            ['name' => 'gredJawatan', 'label' => 'Gred Jawatan', 'type' => 'select', 'options' => match ($flow) {
                'khas' => 'jobGradesKhas',
                'staf-baru' => 'jobGradesNonSpdp',
                'spdp' => 'jobGradesSpdp',
                default => 'jobGrades',
            }, 'required' => true],
            ...(in_array($flow, ['khas', 'phs'], true) ? [] : [
                ['name' => 'gredJawatan_hakiki', 'label' => 'Gred Jawatan Hakiki', 'type' => 'select', 'options' => match ($flow) {
                    'staf-baru' => 'jobGradesNonSpdp',
                    'spdp' => 'jobGradesSpdp',
                    default => 'jobGrades',
                }, 'required' => in_array($flow, ['staf-baru', 'spdp'], true)],
            ]),
            ['name' => 'statSandangan', 'label' => 'Status Sandangan', 'type' => 'select', 'options' => 'sandanganStatuses', 'required' => true],
            ['name' => 'ApmtTypeCd', 'label' => 'Jenis Lantikan', 'type' => 'select', 'options' => 'appointmentTypes', 'required' => true],
            ['name' => 'noWaran', 'label' => 'No. Waran', 'type' => 'text', 'required' => false],
            ['name' => 'DeptId', 'label' => 'Jabatan', 'type' => 'select', 'options' => 'departments', 'required' => true],
            ['name' => 'campus_id', 'label' => 'Kampus', 'type' => 'select', 'options' => 'campuses', 'required' => true],
        ];
    }

    public function emailSuggestion(string $name): string
    {
        $name = preg_replace('/[^a-zA-Z\s]/', ' ', strtolower($name)) ?? '';
        $parts = array_values(array_filter(
            preg_split('/\s+/', trim($name)) ?: [],
            static fn (string $part): bool => !in_array($part, ['bin', 'binti', 'bte', 'anak'], true),
        ));

        if ($parts === []) {
            return '';
        }

        if (count($parts) >= 4) {
            return $parts[0] . '.' . $parts[1] . '@ums.edu.my';
        }

        if (count($parts) === 3) {
            return $parts[0] . $parts[1] . '.' . $parts[2] . '@ums.edu.my';
        }

        if (count($parts) === 2) {
            return $parts[0] . '.' . $parts[1] . '@ums.edu.my';
        }

        return $parts[0] . '@ums.edu.my';
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function appointmentStatuses(): array
    {
        return $this->referenceData->options(
            'SELECT ApmtStatusCd, ApmtStatusNm FROM hronline.appointmentstatus ORDER BY ApmtStatusNm',
            'ApmtStatusCd',
            'ApmtStatusNm',
        );
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function appointmentStatusesKhas(): array
    {
        return $this->referenceData->options(
            'SELECT ApmtStatusCd, ApmtStatusNm FROM hronline.appointmentstatus WHERE ApmtStatusCd = 2 ORDER BY ApmtStatusNm',
            'ApmtStatusCd',
            'ApmtStatusNm',
        );
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function appointmentStatusesPhs(): array
    {
        return $this->referenceData->options(
            'SELECT ApmtStatusCd, ApmtStatusNm FROM hronline.appointmentstatus WHERE ApmtStatusCd IN (6, 7) ORDER BY ApmtStatusNm',
            'ApmtStatusCd',
            'ApmtStatusNm',
        );
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function appointmentStatusesSpdp(): array
    {
        return $this->referenceData->options(
            'SELECT ApmtStatusCd, ApmtStatusNm FROM hronline.appointmentstatus WHERE ApmtStatusCd IN (2, 3) ORDER BY ApmtStatusCd',
            'ApmtStatusCd',
            'ApmtStatusNm',
        );
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function jobGradesNonSpdp(): array
    {
        return $this->referenceData->options(
            "SELECT id, fname FROM hronline.gredjawatan WHERE scheme_type IS NULL OR scheme_type != 'SPDP' ORDER BY fname",
            'id',
            'fname',
        );
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function jobGradesKhas(): array
    {
        return $this->referenceData->options(
            'SELECT id, fname FROM hronline.gredjawatan WHERE isKhas = 1 ORDER BY nama',
            'id',
            'fname',
        );
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function jobGradesSpdp(): array
    {
        return $this->referenceData->options(
            "SELECT id, fname FROM hronline.gredjawatan WHERE scheme_type = 'SPDP' ORDER BY fname",
            'id',
            'fname',
        );
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function states(): array
    {
        return $this->referenceData->options(
            'SELECT StateCd, State FROM hronline.state ORDER BY State',
            'StateCd',
            'State',
        );
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function sandanganStatuses(): array
    {
        return $this->referenceData->options(
            'SELECT sandangan_id, sandangan_name FROM hronline.sandangan ORDER BY sandangan_name',
            'sandangan_id',
            'sandangan_name',
        );
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function appointmentTypes(): array
    {
        return $this->referenceData->options(
            'SELECT ApmtTypeCd, ApmtTypeNm FROM hronline.appointmenttype ORDER BY ApmtTypeNm',
            'ApmtTypeCd',
            'ApmtTypeNm',
        );
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function campuses(): array
    {
        return $this->referenceData->options(
            'SELECT campus_id, campus_name FROM hronline.campus ORDER BY campus_name',
            'campus_id',
            'campus_name',
        );
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function staffStatuses(): array
    {
        return $this->referenceData->options(
            'SELECT ServStatusCd, ServStatusNm FROM hronline.servicestatus ORDER BY ServStatusNm',
            'ServStatusCd',
            'ServStatusNm',
        );
    }
}
