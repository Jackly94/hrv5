<?php

declare(strict_types=1);

namespace App\Shared\Biodata\Sections;

use App\Shared\EmployeeRepository;
use App\Shared\MediaHostFileService;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class AkaunService
{
    public function __construct(
        private ConnectionInterface $db,
        private MediaHostFileService $fileService,
        private EmployeeRepository $employees,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function overview(string $icno, bool $adminMode = false): ?array
    {
        $staff = $this->employees->byIcno($icno);
        if ($staff === null) {
            return null;
        }

        return [
            'key' => 'akaun',
            'title' => 'Akaun',
            'subtitle' => 'Maklumat akaun bank untuk kemasukan gaji',
            'backUrl' => '/biodata/userview',
            'addUrl' => $adminMode ? '/biodata/akaun/admin-tambah' : '/biodata/akaun/permohonan-penukaran-akaun-gaji',
            'addLabel' => $adminMode ? 'Tambah Akaun' : 'Penukaran Akaun',
            'name' => $this->titleCase($staff['CONm'] ?? null),
            'groups' => [$this->accountGroup($icno, $adminMode)],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function detail(string $id, string $icno, bool $adminMode = false): ?array
    {
        $overview = $this->overview($icno, $adminMode);
        if ($overview === null || $id === '') {
            return null;
        }

        foreach ($overview['groups'][0]['rows'] as $row) {
            if ($row['id'] !== $id) {
                continue;
            }

            $fields = array_map(
                static fn (string $label, string $value): array => ['label' => $label, 'value' => $value],
                $overview['groups'][0]['columns'],
                $row['cells'],
            );

            return [
                'title' => $overview['title'],
                'name' => $overview['name'],
                'groupTitle' => $overview['groups'][0]['title'],
                'updateUrl' => $row['updateUrl'] ?? '',
                'fields' => [...$fields, ...($row['attachments'] ?? [])],
            ];
        }

        return null;
    }

    /**
     * @param array<string, string> $query
     * @return array<string, mixed>
     */
    public function withActionQuery(array $overview, array $query): array
    {
        $overview['addUrl'] = $this->appendActionQuery((string) ($overview['addUrl'] ?? ''), $query);

        foreach ($overview['groups'] ?? [] as $groupIndex => $group) {
            if (!is_array($group)) {
                continue;
            }

            foreach ($group['rows'] ?? [] as $rowIndex => $row) {
                if (!is_array($row)) {
                    continue;
                }

                foreach (['viewUrl', 'updateUrl'] as $urlKey) {
                    $overview['groups'][$groupIndex]['rows'][$rowIndex][$urlKey] = $this->appendActionQuery((string) ($row[$urlKey] ?? ''), $query);
                }
            }
        }

        return $overview;
    }

    /**
     * @return array<string, mixed>
     */
    private function accountGroup(string $icno, bool $adminMode): array
    {
        $rows = $this->queryAll(
            <<<SQL
            SELECT id, icno, nama_pemilik, ic_pemilik, no_akaun, kod_bank, status, attachment
            FROM hronline.tbl_akaun
            WHERE icno = :icno
            ORDER BY status ASC, id DESC
            SQL,
            [':icno' => $icno],
        );

        return [
            'title' => 'Senarai Akaun Bank',
            'variant' => 'account',
            'columns' => ['Nama Pemilik Akaun', 'IC/Paspot Pemilik Akaun', 'No. Akaun', 'Bank', 'Status Akaun'],
            'rows' => array_map(fn (array $row): array => $this->row($row, $adminMode), $rows),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function row(array $row, bool $adminMode): array
    {
        $id = $this->value($row['id'] ?? null, '');

        return [
            'id' => $id,
            'cells' => [
                $this->value($row['nama_pemilik'] ?? null, '-'),
                $this->value($row['ic_pemilik'] ?? null, '-'),
                $this->value($row['no_akaun'] ?? null, '-'),
                $this->value($row['kod_bank'] ?? null, '-'),
                ((string) ($row['status'] ?? '') === '1') ? 'Aktif' : 'Tidak Aktif',
            ],
            'viewUrl' => $id === '' ? '' : '/biodata/akaun/lihat?id=' . rawurlencode($id),
            'updateUrl' => $adminMode && $id !== '' ? '/biodata/akaun/admin-kemaskini?id=' . rawurlencode($id) : '',
            'deleteUrl' => '',
            'attachments' => $this->attachments([
                ['label' => 'Lampiran Akaun', 'hash' => $row['attachment'] ?? null],
            ]),
        ];
    }

    /**
     * @param array<string, string> $params
     * @return list<array<string, mixed>>
     */
    private function queryAll(string $sql, array $params): array
    {
        try {
            $rows = $this->db->createCommand($sql, $params)->queryAll();
        } catch (\Throwable) {
            return [];
        }

        return array_values(array_filter($rows, 'is_array'));
    }

    /**
     * @param list<array{label: string, hash: mixed}> $files
     * @return list<array{label: string, value: string, url: string, kind: string}>
     */
    private function attachments(array $files): array
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

    /**
     * @param array<string, string> $query
     */
    private function appendActionQuery(string $url, array $query): string
    {
        $queryString = http_build_query($query);
        if ($url === '' || $queryString === '') {
            return $url;
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . $queryString;
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
