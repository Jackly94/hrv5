<?php

declare(strict_types=1);

namespace App\Shared\Biodata\Sections;

use App\Shared\AddressRecordService;

final readonly class AlamatCrudService
{
    public function __construct(private AddressRecordService $addressService) {}

    public function listForIcno(string $icno): ?array
    {
        return $this->addressService->listForIcno($icno);
    }

    public function formOptions(): array
    {
        return $this->addressService->formOptions();
    }

    public function defaultValues(string $icno): array
    {
        return $this->addressService->defaultValues($icno);
    }

    public function valuesForEdit(string $icno, string $id): ?array
    {
        return $this->addressService->valuesForEdit($icno, $id);
    }

    public function hasCurrentAddress(string $icno): bool
    {
        return $this->addressService->hasCurrentAddress($icno);
    }

    public function copyAddress(string $icno, string $fromType, string $toType, string $ipAddress): array
    {
        return $this->addressService->copyAddress($icno, $fromType, $toType, $ipAddress);
    }

    public function save(string $icno, array $input, ?string $id, string $ipAddress): array
    {
        return $this->addressService->save($icno, $input, $id, $ipAddress);
    }

    public function delete(string $icno, string $id, string $ipAddress): bool
    {
        return $this->addressService->delete($icno, $id, $ipAddress);
    }
}
