<?php

declare(strict_types=1);

namespace App\Shared\PengesahanDataStaf;

final readonly class PengesahanDataStafService
{
    /**
     * @return list<array<string, string|bool>>
     */
    public function sections(): array
    {
        return [
            [
                'title' => 'Akaun Bank Gaji',
                'subtitle' => 'Pengesahan dan kelulusan permohonan penukaran akaun bank gaji.',
                'url' => '/pengesahan-data-staf/akaun-bank',
                'icon' => 'landmark',
                'enabled' => true,
            ],
            [
                'title' => 'Keahlian',
                'subtitle' => 'Pengesahan rekod badan profesional kakitangan.',
                'url' => '/pengesahan-data-staf/keahlian',
                'icon' => 'badge-check',
                'enabled' => true,
            ],
            [
                'title' => 'Keluarga',
                'subtitle' => 'Pengesahan dokumen dan status keluarga kakitangan.',
                'url' => '/pengesahan-data-staf/keluarga',
                'icon' => 'users',
                'enabled' => true,
            ],
            [
                'title' => 'Hubungan Keluarga',
                'subtitle' => 'Pengesahan hubungan keluarga yang dikemaskini.',
                'url' => '/pengesahan-data-staf/hubungan-keluarga',
                'icon' => 'network',
                'enabled' => true,
            ],
        ];
    }
}
