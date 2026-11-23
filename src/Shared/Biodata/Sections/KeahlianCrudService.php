<?php

declare(strict_types=1);

namespace App\Shared\Biodata\Sections;

use App\Shared\ProfessionalMembershipService;
use Psr\Http\Message\UploadedFileInterface;

final readonly class KeahlianCrudService
{
    public function __construct(private ProfessionalMembershipService $membershipService) {}

    public function listForIcno(string $icno): ?array
    {
        return $this->membershipService->listForIcno($icno);
    }

    public function formOptions(): array
    {
        return $this->membershipService->formOptions();
    }

    public function defaultValues(): array
    {
        return $this->membershipService->defaultValues();
    }

    public function valuesForEdit(string $icno, string $id): ?array
    {
        return $this->membershipService->valuesForEdit($icno, $id);
    }

    public function saveForIcno(string $icno, array $input, ?string $id, ?UploadedFileInterface $file, string $ipAddress): array
    {
        return $this->membershipService->saveForIcno($icno, $input, $id, $file, $ipAddress);
    }

    public function delete(string $icno, string $id, string $ipAddress): bool
    {
        return $this->membershipService->delete($icno, $id, $ipAddress);
    }
}
