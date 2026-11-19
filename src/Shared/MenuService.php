<?php

declare(strict_types=1);

namespace App\Shared;

use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class MenuService
{
    private const LOCAL_ROUTE_MAP = [
        'dashboard/index' => '/dashboard',
        'site/menu' => '/menu',
        'biodata/userview' => '/biodata/userview',
        'biodata/index' => '/biodata/index',
        'biodata/lihatbiodata' => '/biodata/lihatbiodata',
        'alamat/view' => '/alamat/view',
        'pendidikan/view' => '/pendidikan/view',
        'bahasa/view' => '/bahasa/view',
        'badan-profesional/view' => '/badan-profesional/view',
        'anugerah/view' => '/anugerah/view',
        'pasport-permit/view' => '/pasport-permit/view',
        'lesen/view' => '/lesen/view',
        'akaun/senarai-akaun' => '/akaun/senarai-akaun',
        'kecacatan/view' => '/kecacatan/view',
        'pengalamankerja/view' => '/pengalamankerja/view',
        'sejarahperubatan/view' => '/sejarahperubatan/view',
        'keluarga/view' => '/keluarga/view',
        'bidangkepakaran/view' => '/bidangkepakaran/view',
        'vaksinasi/index' => '/vaksinasi/index',
        'kelayakan-profesional/view' => '/kelayakan-profesional/view',
        'cuti/individu/index' => '/cuti/individu/index',
        'lantikan/index' => '/lantikan/index',
        'lantikan/lantikan-belum-selesai' => '/lantikan/lantikan-belum-selesai',
        'lantikan/lssu-index' => '/lantikan/lssu-index',
    ];

    public function __construct(
        private ConnectionInterface $db,
        private EmployeeRepository $employees,
        private MenuVisibilityService $visibility,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function menuForIdentity(string $identityId): ?array
    {
        $icno = $this->employees->icnoFromIdentity($identityId);
        $staff = $this->employees->byIcno($icno);
        if ($icno === '' || $staff === null) {
            return null;
        }

        $hiddenUnsupported = [];
        $childRows = $this->visibleRows($this->childRows(), $icno, $hiddenUnsupported);
        $children = $this->childrenByParent($childRows);
        $mainRows = $this->visibleRows($this->mainRows(), $icno, $hiddenUnsupported);

        $categories = [];
        foreach ($this->categories() as $category) {
            $items = [];
            foreach ($mainRows as $row) {
                if ((string) ($row['category_id'] ?? '') !== (string) ($category['id'] ?? '')) {
                    continue;
                }

                $item = $this->menuItem($row, $children[(string) ($row['id'] ?? '')] ?? []);
                if ($item !== null) {
                    $items[] = $item;
                }
            }

            if ($items === []) {
                continue;
            }

            $categories[] = [
                'id' => (string) ($category['id'] ?? ''),
                'name' => $this->value($category['name'] ?? null),
                'icon' => $this->value($category['icon'] ?? null, 'folder'),
                'items' => $items,
                'count' => count($items),
            ];
        }

        return [
            'icno' => $icno,
            'staffName' => $this->employees->displayName($staff['CONm'] ?? null),
            'categories' => $categories,
            'unsupportedRules' => array_values(array_unique($hiddenUnsupported)),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function categories(): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT id, name, icon
            FROM system_core.tbl_menu_category
            WHERE status = 1
            ORDER BY `order`
            SQL,
        )->queryAll();

        return array_values(array_filter($rows, 'is_array'));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mainRows(): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT id, category_id, label, url, visible
            FROM system_core.tbl_menu_side
            WHERE parent_id IS NULL
              AND status = 1
            ORDER BY label
            SQL,
        )->queryAll();

        return array_values(array_filter($rows, 'is_array'));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function childRows(): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT id, parent_id, label, url, visible
            FROM system_core.tbl_menu_side
            WHERE parent_id IS NOT NULL
              AND status = 1
            ORDER BY label
            SQL,
        )->queryAll();

        return array_values(array_filter($rows, 'is_array'));
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @param list<string> $hiddenUnsupported
     * @return list<array<string, mixed>>
     */
    private function visibleRows(array $rows, string $icno, array &$hiddenUnsupported): array
    {
        $visible = [];
        foreach ($rows as $row) {
            $result = $this->visibility->evaluate($this->value($row['visible'] ?? null, ''), $icno);
            if ($result['visible']) {
                $visible[] = $row;
                continue;
            }

            if (!$result['supported']) {
                $hiddenUnsupported[] = $this->value($row['label'] ?? null, 'Unknown') . ': ' . $result['reason'];
            }
        }

        return $visible;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array<string, list<array<string, mixed>>>
     */
    private function childrenByParent(array $rows): array
    {
        $grouped = [];
        foreach ($rows as $row) {
            $parentId = (string) ($row['parent_id'] ?? '');
            if ($parentId === '') {
                continue;
            }

            $grouped[$parentId][] = $row;
        }

        return $grouped;
    }

    /**
     * @param array<string, mixed> $row
     * @param list<array<string, mixed>> $children
     * @return array<string, mixed>|null
     */
    private function menuItem(array $row, array $children): ?array
    {
        $childItems = [];
        foreach ($children as $child) {
            $childItem = $this->menuItem($child, []);
            if ($childItem !== null) {
                $childItems[] = $childItem;
            }
        }

        $url = $this->value($row['url'] ?? null, '');
        if ($url === '' && $childItems === []) {
            return null;
        }

        $normalized = $url === '' ? ['url' => '', 'external' => false, 'legacy' => false] : $this->normalizeUrl($url);

        return [
            'id' => (string) ($row['id'] ?? ''),
            'label' => $this->value($row['label'] ?? null),
            'url' => $normalized['url'],
            'external' => $normalized['external'],
            'legacy' => $normalized['legacy'],
            'children' => $childItems,
        ];
    }

    /**
     * @return array{url: string, external: bool, legacy: bool}
     */
    private function normalizeUrl(string $url): array
    {
        $url = trim($url);
        if ($url === '') {
            return ['url' => '', 'external' => false, 'legacy' => false];
        }

        if (str_starts_with($url, 'https://') || str_starts_with($url, 'http://')) {
            return ['url' => $url, 'external' => true, 'legacy' => false];
        }

        $route = ltrim($url, '/');
        if (isset(self::LOCAL_ROUTE_MAP[$route])) {
            return ['url' => self::LOCAL_ROUTE_MAP[$route], 'external' => false, 'legacy' => false];
        }

        return [
            'url' => LegacyUrl::to($route),
            'external' => true,
            'legacy' => true,
        ];
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
