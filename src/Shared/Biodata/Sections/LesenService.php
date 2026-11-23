<?php

declare(strict_types=1);

namespace App\Shared\Biodata\Sections;

use App\Shared\LicenseRecordService;

final readonly class LesenService
{
    public function __construct(
        private BiodataSectionOverviewSupport $support,
        private LicenseRecordService $licenseService,
    ) {}

    public function overview(string $icno): ?array
    {
        return $this->support->overview($icno, [
            'key' => 'lesen',
            'title' => 'Lesen',
            'subtitle' => 'Rekod lesen kakitangan',
            'backUrl' => '/biodata/userview',
            'addUrl' => '/biodata/lesen/tambahlesen',
            'addLabel' => 'Tambah Lesen',
        ], [$this->licenseGroup($icno)]);
    }

    public function withActionQuery(array $overview, array $query): array
    {
        return $this->support->withActionQuery($overview, $query);
    }

    public function staffForIcno(string $icno): ?array
    {
        return $this->licenseService->staffForIcno($icno);
    }

    public function detail(string $icno, string $id): ?array
    {
        return $this->licenseService->detailForIcno($icno, $id);
    }

    private function licenseGroup(string $icno): array
    {
        $rows = $this->support->queryAll(
            <<<SQL
            SELECT lic.licId, lic.LicNo, type.LicType, class.LicClass,
                   lic.FirstLicIssuedDt, lic.LicExpiryDt, lic.LicRnwlFee, lic.filename
            FROM hronline.tblprlic lic
            LEFT JOIN hronline.license type ON type.LicCd = lic.LicCd
            LEFT JOIN hronline.licenseclass class ON class.LicClassCd = lic.LicClassCd
            WHERE lic.ICNO = :icno
            ORDER BY lic.LicExpiryDt DESC, lic.licId DESC
            SQL,
            [':icno' => $icno],
        );

        return [
            'title' => 'Senarai Lesen',
            'variant' => 'license',
            'columns' => ['No. Lesen', 'Jenis Lesen', 'Kelas Lesen', 'Tarikh Dikeluarkan', 'Tarikh Luput', 'Yuran Pembaharuan'],
            'rows' => array_map(fn (array $row): array => $this->support->row(
                $this->support->value($row['licId'] ?? null, ''),
                [
                    $this->support->value($row['LicNo'] ?? null, '-'),
                    $this->support->value($row['LicType'] ?? null, '-'),
                    $this->support->value($row['LicClass'] ?? null, '-'),
                    $this->support->formatDate($row['FirstLicIssuedDt'] ?? null),
                    $this->support->formatDate($row['LicExpiryDt'] ?? null),
                    'RM ' . $this->support->value($row['LicRnwlFee'] ?? null, '0.00'),
                ],
                '/biodata/lesen/lihatlesen?id=',
                '/biodata/lesen/update?id=',
                '',
                $this->support->attachments([
                    ['label' => 'Fail', 'hash' => $row['filename'] ?? null],
                ]),
            ), $rows),
        ];
    }
}