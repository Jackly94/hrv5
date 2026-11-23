<?php

declare(strict_types=1);

namespace App\Web\Biodata\Sections;

use Psr\Http\Message\ServerRequestInterface;

final class SectionTemplatePath
{
    /**
     * @var array<string, string>
     */
    private const FOLDERS = [
        'alamat' => 'Alamat',
        'pendidikan' => 'Pendidikan',
        'bahasa' => 'Bahasa',
        'keahlian' => 'Keahlian',
        'anugerah' => 'Anugerah',
        'pasport-permit' => 'PasportPermit',
        'lesen' => 'Lesen',
        'akaun' => 'Akaun',
        'kecacatan' => 'Kecacatan',
        'pengalamankerja' => 'PengalamanKerja',
        'sejarahperubatan' => 'SejarahPerubatan',
        'keluarga' => 'Keluarga',
        'bidangkepakaran' => 'BidangKepakaran',
        'vaksinasi' => 'Vaksinasi',
        'kelayakan-profesional' => 'KelayakanProfesional',
    ];

    public static function resolve(ServerRequestInterface $request, string $fallbackDirectory, string $template): string
    {
        $path = $request->getUri()->getPath();
        $section = self::sectionFromPath($path);
        if ($section === null) {
            return $fallbackDirectory . '/' . $template;
        }

        $candidate = __DIR__ . '/' . $section . '/' . $template;
        if (is_file($candidate . '.php')) {
            return $candidate;
        }

        return $fallbackDirectory . '/' . $template;
    }

    private static function sectionFromPath(string $path): ?string
    {
        if (!str_starts_with($path, '/biodata/')) {
            return null;
        }

        $segments = explode('/', trim($path, '/'));
        $slug = $segments[1] ?? '';

        return self::FOLDERS[$slug] ?? null;
    }
}
