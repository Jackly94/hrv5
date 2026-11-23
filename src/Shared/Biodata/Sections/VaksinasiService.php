<?php

declare(strict_types=1);

namespace App\Shared\Biodata\Sections;

final readonly class VaksinasiService
{
    public function __construct(private BiodataSectionOverviewSupport $support) {}

    public function overview(string $icno): ?array
    {
        return $this->support->overview($icno, [
            'key' => 'vaksinasi',
            'title' => 'Program Vaksinasi',
            'subtitle' => 'Rekod vaksinasi kakitangan',
            'backUrl' => '/biodata/userview',
            'addUrl' => '/biodata/vaksinasi/view-st-vaksinasi',
            'addLabel' => 'Kemaskini Vaksinasi',
        ], $this->vaccineGroups($icno));
    }

    public function withActionQuery(array $overview, array $query): array
    {
        return $this->support->withActionQuery($overview, $query);
    }

    private function vaccineGroups(string $icno): array
    {
        $statusRows = $this->support->queryAll(
            'SELECT id, status_vaksin, terima_dos1, terima_dos2, terima_penggalak, catatan, sijil_digital, lampiran FROM hronline.tblstatusvaksinasi WHERE icno = :icno',
            [':icno' => $icno],
        );
        $doseRows = $this->support->queryAll(
            <<<SQL
            SELECT dose.id, dose.bil_dos, dose.tarikh_vaksin, vaccine.nama_vaksin,
                   dose.tempat_vaksin, dose.batch_vaksin
            FROM hronline.tblpenerima_vaksin dose
            LEFT JOIN hronline.jenis_vaksin vaccine ON vaccine.id = dose.jenis_vaksin
            WHERE dose.icno = :icno
            ORDER BY dose.bil_dos, dose.tarikh_vaksin
            SQL,
            [':icno' => $icno],
        );

        return [
            [
                'title' => 'Status Vaksinasi',
                'variant' => 'vaccine-status',
                'columns' => ['Status Vaksin', 'Dos Pertama', 'Dos Kedua', 'Dos Penggalak', 'Catatan'],
                'rows' => array_map(fn (array $row): array => $this->support->row(
                    $this->support->value($row['id'] ?? null, ''),
                    [
                        $this->support->booleanLabel($row['status_vaksin'] ?? null, 'Sudah Terima', 'Belum Terima'),
                        $this->support->booleanLabel($row['terima_dos1'] ?? null, 'Terima', 'Belum'),
                        $this->support->booleanLabel($row['terima_dos2'] ?? null, 'Terima', 'Belum'),
                        $this->support->booleanLabel($row['terima_penggalak'] ?? null, 'Terima', 'Belum'),
                        $this->support->value($row['catatan'] ?? null, '-'),
                    ],
                    '',
                    '/biodata/vaksinasi/update-status-vaksinasi?id=',
                    '',
                    $this->support->attachments([
                        ['label' => 'Sijil Digital', 'hash' => $row['sijil_digital'] ?? null],
                        ['label' => 'Lampiran Sokongan', 'hash' => $row['lampiran'] ?? null],
                    ]),
                ), $statusRows),
            ],
            [
                'title' => 'Dos Vaksinasi',
                'variant' => 'vaccine-dose',
                'columns' => ['Bil. Dos', 'Tarikh Vaksin', 'Jenis Vaksin', 'Tempat Vaksin', 'Batch Vaksin'],
                'rows' => array_map(fn (array $row): array => $this->support->row(
                    $this->support->value($row['id'] ?? null, ''),
                    [
                        $this->support->value($row['bil_dos'] ?? null, '-'),
                        $this->support->formatDate($row['tarikh_vaksin'] ?? null),
                        $this->support->value($row['nama_vaksin'] ?? null, '-'),
                        $this->support->value($row['tempat_vaksin'] ?? null, '-'),
                        $this->support->value($row['batch_vaksin'] ?? null, '-'),
                    ],
                    '',
                    '/biodata/vaksinasi/update-st-vaksinasi?id=',
                ), $doseRows),
            ],
        ];
    }
}