<?php

declare(strict_types=1);

namespace App\Shared\Biodata\Sections;

final readonly class VaksinasiCrudService
{
    private const STATUS_KEY = 'vaccine-status';
    private const DOSE_KEY = 'vaccine-dose';
    private const CONFIGS = [
        self::STATUS_KEY => [
            'title' => 'Status Vaksinasi',
            'backUrl' => '/biodata/vaksinasi/index',
            'table' => 'hronline.tblstatusvaksinasi',
            'idColumn' => 'id',
            'icnoColumn' => 'icno',
            'createPath' => '/biodata/vaksinasi/view-st-vaksinasi',
            'updatePath' => '/biodata/vaksinasi/update-status-vaksinasi',
            'deletePath' => '',
            'directory' => 'Maklumat Rekod Kakitangan/vaksinasi',
            'fields' => [
                ['name' => 'status_vaksin', 'label' => 'Status Vaksin', 'type' => 'select', 'options' => 'yesNo', 'required' => true],
                ['name' => 'terima_dos1', 'label' => 'Dos Pertama', 'type' => 'select', 'options' => 'yesNo', 'required' => false],
                ['name' => 'terima_dos2', 'label' => 'Dos Kedua', 'type' => 'select', 'options' => 'yesNo', 'required' => false],
                ['name' => 'terima_penggalak', 'label' => 'Dos Penggalak', 'type' => 'select', 'options' => 'yesNo', 'required' => false],
                ['name' => 'catatan', 'label' => 'Catatan', 'type' => 'textarea', 'required' => false],
                ['name' => 'sijil_digital', 'label' => 'Sijil Digital', 'type' => 'file', 'required' => false],
                ['name' => 'lampiran', 'label' => 'Lampiran Sokongan', 'type' => 'file', 'required' => false],
            ],
        ],
        self::DOSE_KEY => [
            'title' => 'Dos Vaksinasi',
            'backUrl' => '/biodata/vaksinasi/index',
            'table' => 'hronline.tblpenerima_vaksin',
            'idColumn' => 'id',
            'icnoColumn' => 'icno',
            'createPath' => '/biodata/vaksinasi/update-st-vaksinasi',
            'updatePath' => '/biodata/vaksinasi/update-st-vaksinasi',
            'deletePath' => '',
            'directory' => '',
            'fields' => [
                ['name' => 'bil_dos', 'label' => 'Bil. Dos', 'type' => 'number', 'required' => true],
                ['name' => 'tarikh_vaksin', 'label' => 'Tarikh Vaksin', 'type' => 'date', 'required' => true],
                ['name' => 'jenis_vaksin', 'label' => 'Jenis Vaksin', 'type' => 'select', 'options' => 'vaccineTypes', 'required' => true],
                ['name' => 'tempat_vaksin', 'label' => 'Tempat Vaksin', 'type' => 'text', 'required' => false],
                ['name' => 'batch_vaksin', 'label' => 'Batch Vaksin', 'type' => 'text', 'required' => false],
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

    public function keyFromPath(string $path): ?string
    {
        if (str_starts_with($path, '/biodata/')) {
            $path = substr($path, strlen('/biodata'));
        }

        return match ($path) {
            '/vaksinasi/view-st-vaksinasi', '/vaksinasi/update-status-vaksinasi' => self::STATUS_KEY,
            '/vaksinasi/update-st-vaksinasi' => self::DOSE_KEY,
            default => null,
        };
    }

    public function statusKey(): string
    {
        return self::STATUS_KEY;
    }

    public function doseKey(): string
    {
        return self::DOSE_KEY;
    }
}