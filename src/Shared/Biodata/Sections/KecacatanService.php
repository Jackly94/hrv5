<?php

declare(strict_types=1);

namespace App\Shared\Biodata\Sections;

final readonly class KecacatanService
{
    public function __construct(private BiodataSectionOverviewSupport $support) {}

    public function overview(string $icno): ?array
    {
        return $this->support->overview($icno, [
            'key' => 'kecacatan',
            'title' => 'Kecacatan',
            'subtitle' => 'Rekod kecacatan kakitangan',
            'backUrl' => '/biodata/userview',
            'addUrl' => '/biodata/kecacatan/tambahkecacatan',
            'addLabel' => 'Tambah Kecacatan',
        ], [$this->disabilityGroup($icno)]);
    }

    public function withActionQuery(array $overview, array $query): array
    {
        return $this->support->withActionQuery($overview, $query);
    }

    private function disabilityGroup(string $icno): array
    {
        $rows = $this->support->queryAll(
            <<<SQL
            SELECT disability.id, disability.SocialWelfareNo, disability.DrRptNo,
                   type.DisabilityType, cause.DisabilityCause,
                   disability.DisabilityDt, disability.HealDt, disability.filename
            FROM hronline.tblprdisability disability
            LEFT JOIN hronline.disabilitytype type ON type.DisabilityTypeCd = disability.DisabilityTypeCd
            LEFT JOIN hronline.disabilitycause cause ON cause.DisabilityCauseCd = disability.DisabilityCauseCd
            WHERE disability.ICNO = :icno
            ORDER BY disability.DisabilityDt DESC, disability.id DESC
            SQL,
            [':icno' => $icno],
        );

        return [
            'title' => 'Senarai Kecacatan',
            'variant' => 'disability',
            'columns' => ['No. Fail Kebajikan', 'No. Laporan Doktor', 'Jenis Kecacatan', 'Punca Kecacatan', 'Tarikh Kecacatan', 'Tarikh Sembuh'],
            'rows' => array_map(fn (array $row): array => $this->support->row(
                $this->support->value($row['id'] ?? null, ''),
                [
                    $this->support->value($row['SocialWelfareNo'] ?? null, '-'),
                    $this->support->value($row['DrRptNo'] ?? null, '-'),
                    $this->support->value($row['DisabilityType'] ?? null, '-'),
                    $this->support->value($row['DisabilityCause'] ?? null, '-'),
                    $this->support->formatDate($row['DisabilityDt'] ?? null, 'Tidak Berkaitan'),
                    $this->support->formatDate($row['HealDt'] ?? null, 'Tidak Berkaitan'),
                ],
                '/biodata/kecacatan/lihatkecacatan?id=',
                '/biodata/kecacatan/update?id=',
                '/biodata/kecacatan/delete?id=',
                $this->support->attachments([
                    ['label' => 'Fail', 'hash' => $row['filename'] ?? null],
                ]),
            ), $rows),
        ];
    }
}