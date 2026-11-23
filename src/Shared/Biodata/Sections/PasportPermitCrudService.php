<?php

declare(strict_types=1);

namespace App\Shared\Biodata\Sections;

final readonly class PasportPermitCrudService
{
    private const PASSPORT_KEY = 'passport';
    private const PERMIT_KEY = 'permit';
    private const CONFIGS = [
        self::PASSPORT_KEY => [
            'title' => 'Paspot',
            'backUrl' => '/biodata/pasport-permit/view',
            'table' => 'hronline.tblprpassport',
            'idColumn' => 'id',
            'icnoColumn' => 'ICNO',
            'createPath' => '/biodata/pasport-permit/tambahpasport',
            'updatePath' => '/biodata/pasport-permit/updatepasport',
            'deletePath' => '/biodata/pasport-permit/deletepaspot',
            'directory' => 'Maklumat Rekod Kakitangan/pasport',
            'extraCreate' => ['isActive' => 1],
            'fields' => [
                ['name' => 'PassportNo', 'label' => 'Nombor Paspot', 'type' => 'text', 'required' => true],
                ['name' => 'PassportTypeCd', 'label' => 'Jenis Paspot', 'type' => 'select', 'options' => 'passportTypes', 'required' => true],
                ['name' => 'CountryCd', 'label' => 'Warganegara', 'type' => 'select', 'options' => 'countries', 'required' => true],
                ['name' => 'StateCd', 'label' => 'Tempat Lahir', 'type' => 'select', 'options' => 'states', 'required' => false],
                ['name' => 'IssuedDt', 'label' => 'Tarikh Dikeluarkan', 'type' => 'date', 'required' => true],
                ['name' => 'PassportExpiryDt', 'label' => 'Tarikh Luput', 'type' => 'date', 'required' => true],
                ['name' => 'filename', 'label' => 'Fail', 'type' => 'file', 'required' => false],
            ],
        ],
        self::PERMIT_KEY => [
            'title' => 'Permit Kerja',
            'backUrl' => '/biodata/pasport-permit/view',
            'table' => 'hronline.tblprwrkpermit',
            'idColumn' => 'id',
            'icnoColumn' => 'ICNO',
            'createPath' => '/biodata/pasport-permit/tambahpermitkerja',
            'updatePath' => '/biodata/pasport-permit/updatepermitkerja',
            'deletePath' => '/biodata/pasport-permit/deletepermit',
            'directory' => 'Maklumat Rekod Kakitangan/permitkerja',
            'fields' => [
                ['name' => 'WrkPermitNo', 'label' => 'No. Permit Kerja', 'type' => 'text', 'required' => true],
                ['name' => 'ImigRefNo', 'label' => 'No. Rujukan Imigresen', 'type' => 'text', 'required' => false],
                ['name' => 'WrkPermitIssueDt', 'label' => 'Tarikh Dikeluarkan', 'type' => 'date', 'required' => true],
                ['name' => 'WrkPermitExpiryDt', 'label' => 'Tarikh Luput', 'type' => 'date', 'required' => true],
                ['name' => 'filename', 'label' => 'Fail', 'type' => 'file', 'required' => false],
            ],
        ],
    ];

    public function __construct(private BiodataSectionCrudSupport $crudSupport) {}

    public function form(string $recordKey, string $icno, ?string $id = null): ?array
    {
        return isset(self::CONFIGS[$recordKey]) ? $this->crudSupport->form(self::CONFIGS[$recordKey], $recordKey, $icno, $id) : null;
    }

    public function save(string $recordKey, string $icno, array $input, array $files, ?string $id, string $ipAddress): array
    {
        return isset(self::CONFIGS[$recordKey])
            ? $this->crudSupport->save(self::CONFIGS[$recordKey], $icno, $input, $files, $id, $ipAddress)
            : ['success' => false, 'values' => [], 'errors' => ['general' => 'Konfigurasi rekod tidak dijumpai.']];
    }

    public function delete(string $recordKey, string $icno, string $id, string $ipAddress): bool
    {
        return isset(self::CONFIGS[$recordKey]) && $this->crudSupport->delete(self::CONFIGS[$recordKey], $icno, $id, $ipAddress);
    }

    public function keyFromPath(string $path): ?string
    {
        if (str_starts_with($path, '/biodata/')) {
            $path = substr($path, strlen('/biodata'));
        }

        return match ($path) {
            '/pasport-permit/tambahpasport', '/pasport-permit/updatepasport', '/pasport-permit/deletepaspot' => self::PASSPORT_KEY,
            '/pasport-permit/tambahpermitkerja', '/pasport-permit/updatepermitkerja', '/pasport-permit/deletepermit' => self::PERMIT_KEY,
            default => null,
        };
    }

    public function passportKey(): string
    {
        return self::PASSPORT_KEY;
    }

    public function permitKey(): string
    {
        return self::PERMIT_KEY;
    }
}