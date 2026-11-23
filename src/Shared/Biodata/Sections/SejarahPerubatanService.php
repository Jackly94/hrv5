<?php

declare(strict_types=1);

namespace App\Shared\Biodata\Sections;

use App\Shared\EmployeeRepository;
use App\Shared\StaffDateFormatter;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class SejarahPerubatanService
{
    private const SECTION = [
        'key' => 'sejarahperubatan',
        'title' => 'Sejarah Perubatan',
        'subtitle' => 'Rekod sejarah perubatan kakitangan',
        'legacyBase' => '/biodata/sejarahperubatan',
        'addAction' => 'tambahsejarahperubatan',
        'viewAction' => 'lihatsejarahperubatan',
        'updateAction' => 'update',
        'idKey' => 'id',
        'columns' => ['Nama Penyakit', 'Tahun', 'Tarikh Mula Rawatan', 'Tarikh Akhir Rawatan'],
    ];

    public function __construct(
        private ConnectionInterface $db,
        private StaffDateFormatter $dateFormatter,
        private EmployeeRepository $employees,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function listForIcno(string $icno): ?array
    {
        $staff = $this->employees->byIcno($icno);
        if ($staff === null) {
            return null;
        }

        return [
            ...self::SECTION,
            'icno' => $icno,
            'name' => $this->titleCase($staff['CONm'] ?? null),
            'staffNo' => $this->value($staff['COOldID'] ?? null),
            'rows' => $this->rows($icno),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function detail(string $id, string $icno): ?array
    {
        $section = $this->listForIcno($icno);
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
                'backUrl' => '/biodata/' . self::SECTION['key'] . '/view',
                'updateUrl' => '/biodata/' . self::SECTION['key'] . '/update?id=' . rawurlencode($id),
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
     * @return list<array{id: string, cells: list<string>}>
     */
    private function rows(string $icno): array
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