<?php

declare(strict_types=1);

namespace App\Shared;

final readonly class StaffDateFormatter
{
    /**
     * @var array<int, string>
     */
    private const MALAY_MONTHS = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Mac',
        4 => 'April',
        5 => 'Mei',
        6 => 'Jun',
        7 => 'Julai',
        8 => 'Ogos',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Disember',
    ];

    public function display(mixed $value, string $fallback = '-'): string
    {
        $date = $this->date($value);

        return $date === null ? $fallback : $date->format('d/m/Y');
    }

    public function displayMalay(mixed $value, string $fallback = '-'): string
    {
        $date = $this->date($value);
        if ($date === null) {
            return $fallback;
        }

        return $date->format('d') . ' ' . self::MALAY_MONTHS[(int) $date->format('n')] . ' ' . $date->format('Y');
    }

    public function formValue(mixed $value): string
    {
        $date = $this->date($value);

        return $date === null ? '' : $date->format('Y-m-d');
    }

    public function isValidFormDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        return $date instanceof \DateTimeImmutable && $date->format('Y-m-d') === $value;
    }

    private function date(mixed $value): ?\DateTimeImmutable
    {
        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }

        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '' || str_starts_with($value, '0000-00-00')) {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
