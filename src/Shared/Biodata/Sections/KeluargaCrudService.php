<?php

declare(strict_types=1);

namespace App\Shared\Biodata\Sections;

final readonly class KeluargaCrudService
{
    private const KEY = 'family';
    private const CONFIG = [
        'title' => 'Keluarga',
        'backUrl' => '/biodata/keluarga/view',
        'table' => 'hronline.tblprfmy',
        'idColumn' => 'id',
        'icnoColumn' => 'ICNO',
        'createPath' => '/biodata/keluarga/ums-nonums',
        'updatePath' => '/biodata/keluarga/update',
        'deletePath' => '/biodata/keluarga/delete',
        'directory' => 'Maklumat Rekod Kakitangan/keluarga/oku',
        'fields' => [
            ['name' => 'FamilyId', 'label' => 'No. Kad Pengenalan', 'type' => 'text', 'required' => true, 'readonlyOnUpdate' => true],
            ['name' => 'FmyNm', 'label' => 'Nama', 'type' => 'text', 'required' => true],
            ['name' => 'RelCd', 'label' => 'Hubungan', 'type' => 'select', 'options' => 'relationships', 'required' => true, 'readonlyOnUpdate' => true],
            ['name' => 'FmyDisabilityStatus', 'label' => 'Status OKU', 'type' => 'select', 'options' => 'yesNo', 'required' => false],
            ['name' => 'dis_filename_kad_oku', 'label' => 'Kad OKU', 'type' => 'file', 'required' => false],
            ['name' => 'dis_filename_sijil_lahir', 'label' => 'Sijil Lahir', 'type' => 'file', 'required' => false],
            ['name' => 'dis_filename_kad_temujanji', 'label' => 'Kad Temujanji', 'type' => 'file', 'required' => false],
            ['name' => 'dis_filename_pengesahan_dr', 'label' => 'Pengesahan Doktor', 'type' => 'file', 'required' => false],
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
