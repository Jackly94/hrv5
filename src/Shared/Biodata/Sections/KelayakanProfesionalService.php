<?php

declare(strict_types=1);

namespace App\Shared\Biodata\Sections;

final readonly class KelayakanProfesionalService
{
    public function __construct(private BiodataSectionOverviewSupport $support) {}

    public function overview(string $icno): ?array
    {
        return $this->support->overview($icno, [
            'key' => 'kelayakan-profesional',
            'title' => 'Kelayakan Profesional',
            'subtitle' => 'Rekod sijil profesional kakitangan',
            'backUrl' => '/biodata/userview',
            'addUrl' => '/biodata/kelayakan-profesional/add-cl-cert',
            'addLabel' => 'Tambah Sijil',
        ], [$this->professionalCertificateGroup($icno)]);
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

    private function professionalCertificateGroup(string $icno): array
    {
        $rows = $this->support->queryAll(
            <<<SQL
            SELECT cert.id, cert.title, type.certType, cert.certNo, cert.startDt, cert.endDt, cert.awardBy, cert.proof
            FROM hronline.tblprprofesionalcert cert
            LEFT JOIN hronline.clinicalcert type ON type.id = cert.type
            WHERE cert.icno = :icno
            ORDER BY cert.startDt DESC, cert.id DESC
            SQL,
            [':icno' => $icno],
        );

        return [
            'title' => 'Sijil Profesional',
            'variant' => 'professional-certificate',
            'columns' => ['Certificate Title', 'Certificate Type', 'Certificate No.', 'Start Date', 'End Date', 'Awarded By'],
            'rows' => array_map(fn (array $row): array => $this->support->row(
                $this->support->value($row['id'] ?? null, ''),
                [
                    $this->support->value($row['title'] ?? null, '-'),
                    $this->support->value($row['certType'] ?? null, 'Sila kemaskini.'),
                    $this->support->value($row['certNo'] ?? null, '-'),
                    $this->support->formatDate($row['startDt'] ?? null),
                    $this->support->formatDate($row['endDt'] ?? null),
                    $this->support->value($row['awardBy'] ?? null, '-'),
                ],
                '/biodata/kelayakan-profesional/view-cc?id=',
                '/biodata/kelayakan-profesional/update-cc?id=',
                '/biodata/kelayakan-profesional/delete-cc?id=',
                $this->support->attachments([
                    ['label' => 'Bukti Sijil', 'hash' => $row['proof'] ?? null],
                ]),
            ), $rows),
        ];
    }
}