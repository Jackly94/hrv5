<?php

declare(strict_types=1);

namespace App\Shared\Biodata\Sections;

final readonly class KeluargaService
{
    public function __construct(private BiodataSectionOverviewSupport $support) {}

    public function overview(string $icno, bool $adminMode = false): ?array
    {
        return $this->support->overview($icno, [
            'key' => 'keluarga',
            'title' => 'Keluarga',
            'subtitle' => 'Rekod ahli keluarga kakitangan',
            'backUrl' => '/biodata/userview',
            'addUrl' => '/biodata/keluarga/ums-nonums',
            'addLabel' => 'Tambah Keluarga',
        ], [$this->familyGroup($icno, $adminMode)]);
    }

    public function detail(string $id, string $icno): ?array
    {
        $overview = $this->overview($icno);

        return $overview === null ? null : $this->support->detail($overview, $id);
    }

    public function withActionQuery(array $overview, array $query): array
    {
        return $this->support->withActionQuery($overview, $query);
    }

    private function familyGroup(string $icno, bool $adminMode): array
    {
        $rows = $this->support->queryAll(
            <<<SQL
            SELECT family.id, family.FamilyId, family.FmyNm, family.FmyDisabilityStatus,
                   family.dis_filename_kad_oku, family.dis_filename_sijil_lahir,
                   family.dis_filename_kad_temujanji, family.dis_filename_pengesahan_dr,
                   relationship.RelNm
            FROM hronline.tblprfmy family
            LEFT JOIN hronline.relationship relationship ON relationship.RelCd = family.RelCd
            WHERE family.ICNO = :icno
            ORDER BY relationship.RelNm, family.FmyNm
            SQL,
            [':icno' => $icno],
        );

        return [
            'title' => 'Senarai Keluarga',
            'variant' => 'family',
            'columns' => ['No. Kad Pengenalan', 'Nama', 'Hubungan', 'Status OKU'],
            'rows' => array_map(fn (array $row): array => $this->support->row(
                $this->support->value($row['id'] ?? null, ''),
                [
                    $this->support->value($row['FamilyId'] ?? null, '-'),
                    $this->support->titleCase($row['FmyNm'] ?? null),
                    $this->support->value($row['RelNm'] ?? null, '-'),
                    ((string) ($row['FmyDisabilityStatus'] ?? '') === '1') ? 'OKU' : 'Tidak',
                ],
                '/biodata/keluarga/lihatkeluarga?id=',
                '/biodata/keluarga/update?id=',
                $adminMode ? '/biodata/keluarga/delete?id=' : '',
                $this->support->attachments([
                    ['label' => 'Kad OKU', 'hash' => $row['dis_filename_kad_oku'] ?? null],
                    ['label' => 'Sijil Lahir', 'hash' => $row['dis_filename_sijil_lahir'] ?? null],
                    ['label' => 'Kad Temujanji', 'hash' => $row['dis_filename_kad_temujanji'] ?? null],
                    ['label' => 'Pengesahan Doktor', 'hash' => $row['dis_filename_pengesahan_dr'] ?? null],
                ]),
            ), $rows),
        ];
    }
}
