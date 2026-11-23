<?php

declare(strict_types=1);

namespace App\Shared\Biodata\Sections;

use App\Shared\EmployeeRepository;
use App\Shared\StaffDateFormatter;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class AnugerahService
{
    private const SECTION = [
        'key' => 'anugerah',
        'title' => 'Anugerah',
        'subtitle' => 'Rekod anugerah kakitangan',
        'legacyBase' => '/biodata/anugerah',
        'addAction' => 'tambahanugerah',
        'viewAction' => 'lihatanugerah',
        'updateAction' => 'update',
        'idKey' => 'awdId',
        'columns' => ['Kategori', 'Nama Anugerah', 'Gelaran', 'Dianugerahkan Oleh', 'Sebab Anugerah', 'Status Anugerah'],
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
     * @return list<array{id: string, cells: list<string>, statusCode: string}>
     */
    private function rows(string $icno): array
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