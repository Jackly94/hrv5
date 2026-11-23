<?php

declare(strict_types=1);

namespace App\Shared\Biodata\Sections;

final readonly class PasportPermitService
{
    public function __construct(private BiodataSectionOverviewSupport $support) {}

    public function overview(string $icno): ?array
    {
        return $this->support->overview($icno, [
            'key' => 'pasport-permit',
            'title' => 'Paspot & Permit Kerja',
            'subtitle' => 'Rekod paspot dan permit kerja',
            'backUrl' => '/biodata/userview',
            'addUrl' => '',
            'addLabel' => '',
        ], $this->passportPermitGroups($icno));
    }

    public function detail(string $id, string $icno, string $variant = ''): ?array
    {
        $overview = $this->overview($icno);

        return $overview === null ? null : $this->support->detail($overview, $id, $variant);
    }

    public function withActionQuery(array $overview, array $query): array
    {
        return $this->support->withActionQuery($overview, $query);
    }

    private function passportPermitGroups(string $icno): array
    {
        return [
            [
                'title' => 'Paspot',
                'variant' => 'passport',
                'columns' => ['Nombor Paspot', 'Jenis Paspot', 'Warganegara', 'Tempat Lahir', 'Tarikh Dikeluarkan', 'Tarikh Luput'],
                'addUrl' => '/biodata/pasport-permit/tambahpasport',
                'addLabel' => 'Tambah Paspot',
                'rows' => $this->passportRows($icno),
            ],
            [
                'title' => 'Permit Kerja',
                'variant' => 'permit',
                'columns' => ['No. Permit Kerja', 'No. Rujukan Imigresen', 'Tarikh Dikeluarkan', 'Tarikh Luput'],
                'addUrl' => '/biodata/pasport-permit/tambahpermitkerja',
                'addLabel' => 'Tambah Permit Kerja',
                'rows' => $this->permitRows($icno),
            ],
        ];
    }

    private function passportRows(string $icno): array
    {
        $rows = $this->support->queryAll(
            <<<SQL
            SELECT passport.id, passport.PassportNo, type.PassportType, country.Country, state.State,
                   passport.IssuedDt, passport.PassportExpiryDt
            FROM hronline.tblprpassport passport
            LEFT JOIN hronline.passport type ON type.PassportTypeCd = passport.PassportTypeCd
            LEFT JOIN hronline.country country ON country.CountryCd = passport.CountryCd
            LEFT JOIN hronline.state state ON state.StateCd = passport.StateCd
            WHERE passport.ICNO = :icno
            ORDER BY passport.PassportExpiryDt DESC, passport.id DESC
            SQL,
            [':icno' => $icno],
        );

        return array_map(fn (array $row): array => $this->support->row(
            $this->support->value($row['id'] ?? null, ''),
            [
                $this->support->value($row['PassportNo'] ?? null, '-'),
                $this->support->value($row['PassportType'] ?? null, '-'),
                $this->support->value($row['Country'] ?? null, '-'),
                $this->support->value($row['State'] ?? null, '-'),
                $this->support->formatDate($row['IssuedDt'] ?? null),
                $this->support->formatDate($row['PassportExpiryDt'] ?? null),
            ],
            '/biodata/pasport-permit/lihatpasport?id=',
            '/biodata/pasport-permit/updatepasport?id=',
            '/biodata/pasport-permit/deletepaspot?id=',
        ), $rows);
    }

    private function permitRows(string $icno): array
    {
        $rows = $this->support->queryAll(
            <<<SQL
            SELECT id, WrkPermitNo, ImigRefNo, WrkPermitIssueDt, WrkPermitExpiryDt
            FROM hronline.tblprwrkpermit
            WHERE ICNO = :icno
            ORDER BY WrkPermitExpiryDt DESC, id DESC
            SQL,
            [':icno' => $icno],
        );

        return array_map(fn (array $row): array => $this->support->row(
            $this->support->value($row['id'] ?? null, ''),
            [
                $this->support->value($row['WrkPermitNo'] ?? null, '-'),
                $this->support->value($row['ImigRefNo'] ?? null, '-'),
                $this->support->formatDate($row['WrkPermitIssueDt'] ?? null),
                $this->support->formatDate($row['WrkPermitExpiryDt'] ?? null),
            ],
            '/biodata/pasport-permit/lihatpermitkerja?id=',
            '/biodata/pasport-permit/updatepermitkerja?id=',
            '/biodata/pasport-permit/deletepermit?id=',
        ), $rows);
    }
}