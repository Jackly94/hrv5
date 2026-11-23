<?php

declare(strict_types=1);

namespace App\Shared\Biodata\Sections;

use App\Shared\AddressRecordService;

final readonly class AlamatService
{
    public function __construct(private AddressRecordService $addressService) {}

    public function listForIcno(string $icno): ?array
    {
        return $this->addressService->listForIcno($icno);
    }

    public function detailForIcno(string $icno, string $id): ?array
    {
        return $this->addressService->detailForIcno($icno, $id);
    }

    public function statesForCountry(string $countryCode): array
    {
        return $this->addressService->statesForCountry($countryCode);
    }

    public function citiesForState(string $stateCode): array
    {
        return $this->addressService->citiesForState($stateCode);
    }
}
