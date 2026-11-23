<?php

declare(strict_types=1);

namespace App\Shared\Biodata\Sections;

final readonly class PendidikanCrudService
{
    private const KEY = 'pendidikan';
    private const CONFIG = [
        'title' => 'Pendidikan',
        'backUrl' => '/biodata/pendidikan/view',
        'table' => 'hronline.tblpreduach',
        'idColumn' => 'id',
        'icnoColumn' => 'ICNO',
        'createPath' => '/biodata/pendidikan/tambahpendidikan',
        'updatePath' => '/biodata/pendidikan/update',
        'deletePath' => '/biodata/pendidikan/delete',
        'directory' => 'Maklumat Rekod Kakitangan/Pendidikan',
        'fields' => [
            ['name' => 'InstCd', 'label' => 'Institut', 'type' => 'select', 'options' => 'institutions', 'required' => true],
            ['name' => 'HighestEduLevelCd', 'label' => 'Tahap Pendidikan', 'type' => 'select', 'options' => 'educationLevels', 'required' => true],
            ['name' => 'MajorCd', 'label' => 'Major', 'type' => 'select', 'options' => 'majors', 'required' => false],
            ['name' => 'EduCertTitle', 'label' => 'Nama Sijil', 'type' => 'text', 'required' => true],
            ['name' => 'ConfermentDt', 'label' => 'Tarikh Dianugerahkan', 'type' => 'date', 'required' => true],
            ['name' => 'StartEduDt', 'label' => 'Tarikh Mula Pengajian', 'type' => 'date', 'required' => false],
            ['name' => 'AcrtdEduAch', 'label' => 'Status Pengiktirafan', 'type' => 'select', 'options' => 'yesNo', 'required' => false],
            ['name' => 'filename', 'label' => 'Fail', 'type' => 'file', 'required' => true],
        ],
    ];

    public function __construct(private BiodataSectionCrudSupport $crudSupport) {}

    public function form(string $icno, ?string $id = null): ?array
    {
        return $this->crudSupport->form(self::CONFIG, self::KEY, $icno, $id);
    }

    public function save(string $icno, array $input, array $files, ?string $id, string $ipAddress): array
    {
        return $this->crudSupport->save(self::CONFIG, $icno, $input, $files, $id, $ipAddress);
    }

    public function delete(string $icno, string $id, string $ipAddress): bool
    {
        return $this->crudSupport->delete(self::CONFIG, $icno, $id, $ipAddress);
    }

    public function key(): string
    {
        return self::KEY;
    }
}