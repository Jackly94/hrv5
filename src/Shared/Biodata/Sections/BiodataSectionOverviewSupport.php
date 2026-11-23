<?php

declare(strict_types=1);

namespace App\Shared\Biodata\Sections;

use App\Shared\EmployeeRepository;
use App\Shared\MediaHostFileService;
use App\Shared\StaffDateFormatter;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class BiodataSectionOverviewSupport
{
    public function __construct(
        private ConnectionInterface $db,
        private MediaHostFileService $fileService,
        private StaffDateFormatter $dateFormatter,
        private EmployeeRepository $employees,
    ) {}

    public function overview(string $icno, array $definition, array $groups): ?array
    {
        $staff = $this->employees->byIcno($icno);
        if ($staff === null) {
            return null;
        }

        return [
            ...$definition,
            'name' => $this->titleCase($staff['CONm'] ?? null),
            'groups' => $groups,
        ];
    }

    public function detail(array $overview, string $id, string $variant = ''): ?array
    {
        if ($id === '') {
            return null;
        }

        foreach ($overview['groups'] ?? [] as $group) {
            if (!is_array($group) || ($variant !== '' && ($group['variant'] ?? '') !== $variant)) {
                continue;
            }

            foreach ($group['rows'] ?? [] as $row) {
                if (!is_array($row) || ($row['id'] ?? '') !== $id) {
                    continue;
                }

                $fields = array_map(
                    static fn (string $label, string $value): array => ['label' => $label, 'value' => $value],
                    $group['columns'],
                    $row['cells'],
                );

                return [
                    'title' => $overview['title'],
                    'name' => $overview['name'],
                    'groupTitle' => $group['title'],
                    'updateUrl' => $row['updateUrl'] ?? '',
                    'fields' => [...$fields, ...($row['attachments'] ?? [])],
                ];
            }
        }

        return null;
    }

    public function withActionQuery(array $overview, array $query): array
    {
        $overview['addUrl'] = $this->appendActionQuery((string) ($overview['addUrl'] ?? ''), $query);

        foreach ($overview['groups'] ?? [] as $groupIndex => $group) {
            if (!is_array($group)) {
                continue;
            }

            $overview['groups'][$groupIndex]['addUrl'] = $this->appendActionQuery((string) ($group['addUrl'] ?? ''), $query);

            foreach ($group['rows'] ?? [] as $rowIndex => $row) {
                if (!is_array($row)) {
                    continue;
                }

                foreach (['viewUrl', 'updateUrl', 'deleteUrl'] as $urlKey) {
                    $overview['groups'][$groupIndex]['rows'][$rowIndex][$urlKey] = $this->appendActionQuery((string) ($row[$urlKey] ?? ''), $query);
                }
            }
        }

        return $overview;
    }

    public function queryAll(string $sql, array $params): array
    {
        try {
            $rows = $this->db->createCommand($sql, $params)->queryAll();
        } catch (\Throwable) {
            return [];
        }

        return array_values(array_filter($rows, 'is_array'));
    }

    public function row(
        string $id,
        array $cells,
        string $viewPrefix,
        string $updatePrefix,
        string $deletePrefix = '',
        array $attachments = [],
    ): array {
        return [
            'id' => $id,
            'cells' => $cells,
            'viewUrl' => $viewPrefix === '' || $id === '' ? '' : $viewPrefix . rawurlencode($id),
            'updateUrl' => $this->actionUrl($updatePrefix, $id),
            'deleteUrl' => $this->actionUrl($deletePrefix, $id),
            'attachments' => $attachments,
        ];
    }

    public function attachments(array $files): array
    {
        $attachments = [];

        foreach ($files as $file) {
            $hash = $this->value($file['hash'] ?? null, '');
            $url = $this->fileService->viewUrl($hash);
            if ($url === '') {
                continue;
            }

            $attachments[] = [
                'label' => $this->value($file['label'] ?? null, 'Fail'),
                'value' => 'Lihat Fail',
                'url' => $url,
                'kind' => 'file',
            ];
        }

        return $attachments;
    }

    public function booleanLabel(mixed $value, string $true, string $false): string
    {
        return ((string) $value === '1') ? $true : $false;
    }

    public function formatDate(mixed $value, string $fallback = '-'): string
    {
        return $this->dateFormatter->display($value, $fallback);
    }

    public function titleCase(mixed $value): string
    {
        $value = $this->value($value, '');

        return $value === '' ? 'Tiada Rekod' : ucwords(strtolower($value));
    }

    public function value(mixed $value, string $fallback = 'Tiada Rekod'): string
    {
        if ($value === null) {
            return $fallback;
        }

        $value = trim((string) $value);

        return $value === '' ? $fallback : $value;
    }

    private function actionUrl(string $prefix, string $id): string
    {
        if ($prefix === '') {
            return '';
        }

        return str_ends_with($prefix, '=') && $id !== '' ? $prefix . rawurlencode($id) : $prefix;
    }

    private function appendActionQuery(string $url, array $query): string
    {
        if ($url === '' || str_starts_with($url, 'http')) {
            return $url;
        }

        $queryString = http_build_query($query);
        if ($queryString === '') {
            return $url;
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . $queryString;
    }
}
