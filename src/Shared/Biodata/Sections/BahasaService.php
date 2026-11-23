<?php

declare(strict_types=1);

namespace App\Shared\Biodata\Sections;

use App\Shared\EmployeeRepository;
use App\Shared\StaffDateFormatter;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class BahasaService
{
    private const SECTION = [
        'key' => 'bahasa',
        'title' => 'Bahasa',
        'subtitle' => 'Kemahiran bahasa kakitangan',
        'legacyBase' => '/biodata/bahasa',
        'addAction' => 'tambahbahasa',
        'viewAction' => 'lihatbahasa',
        'updateAction' => 'update',
        'idKey' => 'id',
        'columns' => ['Nama Bahasa', 'Kemahiran Lisan', 'Kemahiran Menulis', 'Sijil Kemahiran'],
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
                'backUrl' => '/biodata/bahasa/view',
                'updateUrl' => '/biodata/bahasa/update?id=' . rawurlencode($id),
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