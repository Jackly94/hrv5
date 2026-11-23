<?php

declare(strict_types=1);

namespace App\Shared\Biodata\Sections;

use App\Shared\ProfessionalMembershipService;

final readonly class KeahlianService
{
    public function __construct(private ProfessionalMembershipService $membershipService) {}

    public function listForIcno(string $icno): ?array
    {
        return $this->membershipService->listForIcno($icno);
    }

    public function detailForIcno(string $icno, string $id): ?array
    {
        return $this->membershipService->detailForIcno($icno, $id);
    }
}
