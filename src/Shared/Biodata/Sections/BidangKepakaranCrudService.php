<?php

declare(strict_types=1);

namespace App\Shared\Biodata\Sections;

final readonly class BidangKepakaranCrudService
{
    private const KEY = 'expertise';
    private const CONFIG = [
        'title' => 'Bidang Kepakaran',
        'backUrl' => '/biodata/bidangkepakaran/view',
        'table' => 'hronline.tblprcobidangkepakaran',
        'idColumn' => 'id',
        'icnoColumn' => 'ICNO',
        'createPath' => '/biodata/bidangkepakaran/tambahbidangkepakaran',
        'updatePath' => '/biodata/bidangkepakaran/update',
        'deletePath' => '/biodata/bidangkepakaran/delete',
        'directory' => 'Maklumat Rekod Kakitangan/Bidang Kepakaran',
        'fields' => [
            ['name' => 'klusterid', 'label' => 'Kluster Kepakaran', 'type' => 'select', 'options' => 'expertiseClusters', 'required' => true],
            ['name' => 'bidang', 'label' => 'Bidang Kepakaran', 'type' => 'textarea', 'required' => true],
            ['name' => 'filename', 'label' => 'Fail', 'type' => 'file', 'required' => false],
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