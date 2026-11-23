<?php

declare(strict_types=1);

namespace App\Shared\Biodata\Sections;

final readonly class BidangKepakaranService
{
    public function __construct(private BiodataSectionOverviewSupport $support) {}

    public function overview(string $icno): ?array
    {
        return $this->support->overview($icno, [
            'key' => 'bidangkepakaran',
            'title' => 'Bidang Kepakaran',
            'subtitle' => 'Rekod bidang kepakaran kakitangan',
            'backUrl' => '/biodata/userview',
            'addUrl' => '/biodata/bidangkepakaran/tambahbidangkepakaran',
            'addLabel' => 'Tambah Kepakaran',
        ], [$this->expertiseGroup($icno)]);
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

    private function expertiseGroup(string $icno): array
    {
        $rows = $this->support->queryAll(
            <<<SQL
            SELECT expertise.id, expertise.bidang, expertise.filename, cluster.kluster
            FROM hronline.tblprcobidangkepakaran expertise
            LEFT JOIN hronline.klusterkepakaran cluster ON cluster.id = expertise.klusterid
            WHERE expertise.ICNO = :icno
            ORDER BY cluster.kluster, expertise.bidang
            SQL,
            [':icno' => $icno],
        );

        return [
            'title' => 'Senarai Bidang Kepakaran',
            'variant' => 'expertise',
            'columns' => ['Kluster Kepakaran', 'Bidang Kepakaran'],
            'rows' => array_map(fn (array $row): array => $this->support->row(
                $this->support->value($row['id'] ?? null, ''),
                [
                    $this->support->value($row['kluster'] ?? null, 'Tidak Berkaitan'),
                    $this->support->value($row['bidang'] ?? null, '-'),
                ],
                '/biodata/bidangkepakaran/lihatbidangkepakaran?id=',
                '/biodata/bidangkepakaran/update?id=',
                '/biodata/bidangkepakaran/delete?id=',
                $this->support->attachments([
                    ['label' => 'Fail', 'hash' => $row['filename'] ?? null],
                ]),
            ), $rows),
        ];
    }
}