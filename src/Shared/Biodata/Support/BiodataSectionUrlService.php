<?php

declare(strict_types=1);

namespace App\Shared\Biodata\Support;

final readonly class BiodataSectionUrlService
{
    /**
     * @var array<string, string>
     */
    private const BASE_PATHS = [
        'alamat' => '/biodata/alamat',
        'pendidikan' => '/biodata/pendidikan',
        'bahasa' => '/biodata/bahasa',
        'keahlian' => '/biodata/keahlian',
        'badan-profesional' => '/biodata/keahlian',
        'anugerah' => '/biodata/anugerah',
        'pasport-permit' => '/biodata/pasport-permit',
        'lesen' => '/biodata/lesen',
        'akaun' => '/biodata/akaun',
        'kecacatan' => '/biodata/kecacatan',
        'pengalamankerja' => '/biodata/pengalamankerja',
        'sejarahperubatan' => '/biodata/sejarahperubatan',
        'keluarga' => '/biodata/keluarga',
        'bidangkepakaran' => '/biodata/bidangkepakaran',
        'vaksinasi' => '/biodata/vaksinasi',
        'kelayakan-profesional' => '/biodata/kelayakan-profesional',
    ];

    /**
     * @param array<string, string> $query
     */
    public function user(string $section, string $action = 'view', array $query = []): string
    {
        return $this->appendQuery($this->basePath($section) . '/' . ltrim($action, '/'), $query);
    }

    /**
     * @param array<string, string> $query
     */
    public function admin(string $section, string $icno, string $action = 'adminview', array $query = []): string
    {
        return $this->appendQuery(
            $this->basePath($section) . '/' . ltrim($action, '/'),
            ['icno' => $this->cleanQueryValue($icno), ...$query],
        );
    }

    public function basePath(string $section): string
    {
        return self::BASE_PATHS[$section] ?? '/biodata/' . trim($section, '/');
    }

    /**
     * @param array<string, string> $query
     */
    public function appendQuery(string $url, array $query): string
    {
        $query = array_filter(
            $query,
            static fn (string $value): bool => $value !== '',
        );
        $queryString = http_build_query($query);
        if ($url === '' || $queryString === '') {
            return $url;
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . $queryString;
    }

    public function cleanQueryValue(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return trim(strtok($value, '?&') ?: $value);
    }
}
