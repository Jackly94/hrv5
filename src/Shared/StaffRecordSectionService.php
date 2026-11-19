<?php

declare(strict_types=1);

namespace App\Shared;

use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class StaffRecordSectionService
{
    /**
     * @var array<string, array{
     *     title: string,
     *     subtitle: string,
     *     legacyBase: string,
     *     addAction: string,
     *     viewAction: string,
     *     updateAction: string,
     *     idKey: string,
     *     columns: list<string>
     * }>
     */
    private const SECTIONS = [
        'bahasa' => [
            'title' => 'Bahasa',
            'subtitle' => 'Kemahiran bahasa kakitangan',
            'legacyBase' => '/bahasa',
            'addAction' => 'tambahbahasa',
            'viewAction' => 'lihatbahasa',
            'updateAction' => 'update',
            'idKey' => 'id',
            'columns' => ['Nama Bahasa', 'Kemahiran Lisan', 'Kemahiran Menulis', 'Sijil Kemahiran'],
        ],
        'anugerah' => [
            'title' => 'Anugerah',
            'subtitle' => 'Rekod anugerah kakitangan',
            'legacyBase' => '/anugerah',
            'addAction' => 'tambahanugerah',
            'viewAction' => 'lihatanugerah',
            'updateAction' => 'update',
            'idKey' => 'awdId',
            'columns' => ['Kategori', 'Nama Anugerah', 'Gelaran', 'Dianugerahkan Oleh', 'Sebab Anugerah', 'Status Anugerah'],
        ],
        'pengalamankerja' => [
            'title' => 'Pengalaman Kerja',
            'subtitle' => 'Rekod pengalaman kerja kakitangan',
            'legacyBase' => '/pengalamankerja',
            'addAction' => 'tambahpengalamankerja',
            'viewAction' => 'lihatpengalamankerja',
            'updateAction' => 'update',
            'idKey' => 'id',
            'columns' => ['Nama Majikan', 'Sektor', 'Jenis Majikan (Jawatan)', 'Keterangan Tugas', 'Tarikh Mula', 'Tarikh Berhenti'],
        ],
        'sejarahperubatan' => [
            'title' => 'Sejarah Perubatan',
            'subtitle' => 'Rekod sejarah perubatan kakitangan',
            'legacyBase' => '/sejarahperubatan',
            'addAction' => 'tambahsejarahperubatan',
            'viewAction' => 'lihatsejarahperubatan',
            'updateAction' => 'update',
            'idKey' => 'id',
            'columns' => ['Nama Penyakit', 'Tahun', 'Tarikh Mula Rawatan', 'Tarikh Akhir Rawatan'],
        ],
    ];

    public function __construct(
        private ConnectionInterface $db,
        private StaffDateFormatter $dateFormatter,
        private EmployeeRepository $employees,
    ) {}

    public function sectionKeyFromPath(string $path): ?string
    {
        return match ($path) {
            '/bahasa/view', '/bahasa/lihatbahasa' => 'bahasa',
            '/anugerah/view', '/anugerah/lihatanugerah' => 'anugerah',
            '/pengalamankerja/view', '/pengalamankerja/lihatpengalamankerja' => 'pengalamankerja',
            '/sejarahperubatan/view', '/sejarahperubatan/lihatsejarahperubatan' => 'sejarahperubatan',
            default => null,
        };
    }

    /**
     * @return array{
     *     key: string,
     *     title: string,
     *     subtitle: string,
     *     legacyBase: string,
     *     addAction: string,
     *     viewAction: string,
     *     updateAction: string,
     *     idKey: string,
     *     columns: list<string>,
     *     icno: string,
     *     name: string,
     *     staffNo: string,
     *     rows: list<array{id: string, cells: list<string>, statusCode?: string}>
     * }|null
     */
    public function listForIcno(string $sectionKey, string $icno): ?array
    {
        $section = self::SECTIONS[$sectionKey] ?? null;
        if ($section === null) {
            return null;
        }

        $staff = $this->staffRow($icno);
        if ($staff === null) {
            return null;
        }

        return [
            'key' => $sectionKey,
            ...$section,
            'icno' => $icno,
            'name' => $this->titleCase($staff['CONm'] ?? null),
            'staffNo' => $this->value($staff['COOldID'] ?? null),
            'rows' => $this->rows($sectionKey, $icno),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function detail(string $sectionKey, string $id, string $icno): ?array
    {
        $section = $this->listForIcno($sectionKey, $icno);
        if ($section === null || $id === '') {
            return null;
        }

        foreach ($section['rows'] as $row) {
            if ($row['id'] !== $id) {
                continue;
            }

            return [
                'title' => $section['title'],
                'name' => $section['name'],
                'backUrl' => '/' . $sectionKey . '/view',
                'updateUrl' => '/' . $sectionKey . '/update?id=' . rawurlencode($id),
                'fields' => array_map(
                    static fn (string $label, string $value): array => ['label' => $label, 'value' => $value],
                    $section['columns'],
                    $row['cells'],
                ),
            ];
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function staffRow(string $icno): ?array
    {
        return $this->employees->byIcno($icno);
    }

    /**
     * @return list<array{id: string, cells: list<string>, statusCode?: string}>
     */
    private function rows(string $sectionKey, string $icno): array
    {
        return match ($sectionKey) {
            'bahasa' => $this->languageRows($icno),
            'anugerah' => $this->awardRows($icno),
            'pengalamankerja' => $this->employmentRows($icno),
            'sejarahperubatan' => $this->medicalRows($icno),
            default => [],
        };
    }

    /**
     * @return list<array{id: string, cells: list<string>}>
     */
    private function languageRows(string $icno): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT lang.id, name.Lang, oral.LangProficiency AS OralSkill,
                   written.LangProficiency AS WrittenSkill, lang.LangSkillCert
            FROM hronline.tblprlangskill lang
            LEFT JOIN hronline.languagenm name ON name.LangCd = lang.LangCd
            LEFT JOIN hronline.langproficiency oral ON oral.LangProficiencyCd = lang.LangSkillOral
            LEFT JOIN hronline.langproficiency written ON written.LangProficiencyCd = lang.LangSkillWritten
            WHERE lang.ICNO = :icno
            ORDER BY name.Lang, lang.id
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
                'cells' => [
                    $this->value($row['Lang'] ?? null, '-'),
                    $this->value($row['OralSkill'] ?? null, '-'),
                    $this->value($row['WrittenSkill'] ?? null, '-'),
                    ((string) ($row['LangSkillCert'] ?? '') !== '' && (string) ($row['LangSkillCert'] ?? '') !== '0') ? 'Ada' : 'Tiada',
                ],
            ];
        }

        return $items;
    }

    /**
     * @return list<array{id: string, cells: list<string>, statusCode: string}>
     */
    private function awardRows(string $icno): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT award.awdId, award.AwdReason, award.AwdStatus,
                   category.AwdCat, award_name.Awd, title.Title, conferred.CfdBy
            FROM hronline.tblprawd award
            LEFT JOIN hronline.awardcategory category ON category.AwdCatCd = award.AwdCatCd
            LEFT JOIN hronline.award award_name ON award_name.AwdCd = award.AwdCd
            LEFT JOIN hronline.title title ON title.TitleCd = award.TitleCd
            LEFT JOIN hronline.conferredby conferred ON conferred.CfdByCd = award.CfdByCd
            WHERE award.ICNO = :icno
            ORDER BY award.AwdCfdDt DESC, award.awdId DESC
            SQL,
            [':icno' => $icno],
        )->queryAll();

        $items = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $statusCode = $this->value($row['AwdStatus'] ?? null, '');
            $items[] = [
                'id' => $this->value($row['awdId'] ?? null, ''),
                'statusCode' => $statusCode,
                'cells' => [
                    $this->value($row['AwdCat'] ?? null, '-'),
                    $this->value($row['Awd'] ?? null, '-'),
                    $this->value($row['Title'] ?? null, '-'),
                    $this->value($row['CfdBy'] ?? null, '-'),
                    $this->value($row['AwdReason'] ?? null, '-'),
                    $this->activeStatus($statusCode),
                ],
            ];
        }

        return $items;
    }

    /**
     * @return list<array{id: string, cells: list<string>}>
     */
    private function employmentRows(string $icno): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT prev.id, prev.OrgNm, prev.PrevEmpRemarks, prev.PrevEmpStartDt,
                   prev.PrevEmpEndDt, sector.OccSector, body.CorpBodyType
            FROM hronline.tblprprevemp prev
            LEFT JOIN hronline.occupationsector sector ON sector.OccSectorCd = prev.OccSectorCd
            LEFT JOIN hronline.corporatebody body ON body.CorpBodyTypeCd = prev.CorpBodyTypeCd
            WHERE prev.ICNO = :icno
            ORDER BY prev.PrevEmpStartDt DESC, prev.id DESC
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
                'cells' => [
                    $this->value($row['OrgNm'] ?? null, '-'),
                    $this->value($row['OccSector'] ?? null, '-'),
                    $this->value($row['CorpBodyType'] ?? null, '-'),
                    $this->value($row['PrevEmpRemarks'] ?? null, '-'),
                    $this->formatDate($row['PrevEmpStartDt'] ?? null),
                    $this->formatDate($row['PrevEmpEndDt'] ?? null),
                ],
            ];
        }

        return $items;
    }

    /**
     * @return list<array{id: string, cells: list<string>}>
     */
    private function medicalRows(string $icno): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT med.id, med.IllnessOther, med.Year, med.TreatmentStartDt,
                   med.TreatmentEndDt, illness.Illness
            FROM hronline.tblprmedhis med
            LEFT JOIN hronline.illness illness ON illness.IllnessCd = med.IllnessCd
            WHERE med.ICNO = :icno
            ORDER BY med.Year DESC, med.id DESC
            SQL,
            [':icno' => $icno],
        )->queryAll();

        $items = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $illness = $this->value($row['Illness'] ?? null, '');
            if ($illness === '') {
                $illness = $this->value($row['IllnessOther'] ?? null, '-');
            }

            $items[] = [
                'id' => $this->value($row['id'] ?? null, ''),
                'cells' => [
                    $illness,
                    $this->value($row['Year'] ?? null, '-'),
                    $this->formatDate($row['TreatmentStartDt'] ?? null),
                    $this->formatDate($row['TreatmentEndDt'] ?? null),
                ],
            ];
        }

        return $items;
    }

    private function activeStatus(string $statusCode): string
    {
        return !in_array($statusCode, ['', '0'], true) ? 'Aktif' : 'Tidak Aktif';
    }

    private function formatDate(mixed $value): string
    {
        return $this->dateFormatter->display($value);
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
