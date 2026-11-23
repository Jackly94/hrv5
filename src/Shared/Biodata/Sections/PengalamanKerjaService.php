<?php

declare(strict_types=1);

namespace App\Shared\Biodata\Sections;

use App\Shared\EmployeeRepository;
use App\Shared\StaffDateFormatter;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class PengalamanKerjaService
{
    private const SECTION = [
        'key' => 'pengalamankerja',
        'title' => 'Pengalaman Kerja',
        'subtitle' => 'Rekod pengalaman kerja kakitangan',
        'legacyBase' => '/biodata/pengalamankerja',
        'addAction' => 'tambahpengalamankerja',
        'viewAction' => 'lihatpengalamankerja',
        'updateAction' => 'update',
        'idKey' => 'id',
        'columns' => ['Nama Majikan', 'Sektor', 'Jenis Majikan (Jawatan)', 'Keterangan Tugas', 'Tarikh Mula', 'Tarikh Berhenti'],
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