<?php

declare(strict_types=1);

namespace App\Shared\Biodata\Sections;

use App\Shared\ProfessionalCertificateService;
use Psr\Http\Message\UploadedFileInterface;

final readonly class KelayakanProfesionalCrudService
{
    public function __construct(private ProfessionalCertificateService $certificateService) {}

    /**
     * @return array{name: string}|null
     */
    public function staffForIcno(string $icno): ?array
    {
        return $this->certificateService->staffForIcno($icno);
    }

    /**
     * @return array<string, list<array{id: string, name: string}>>
     */
    public function formOptions(): array
    {
        return $this->certificateService->formOptions();
    }

    /**
     * @return array<string, string>
     */
    public function defaultValues(string $icno): array
    {
        return $this->certificateService->defaultValues($icno);
    }

    /**
     * @return array<string, string>|null
     */
    public function valuesForEdit(string $icno, string $id): ?array
    {
        return $this->certificateService->valuesForEdit($icno, $id);
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, values: array<string, string>, errors: array<string, string>, id?: string}
     */
    public function save(string $icno, array $input, ?string $id, ?UploadedFileInterface $file, string $ipAddress): array
    {
        return $this->certificateService->save($icno, $input, $id, $file, $ipAddress);
    }

    public function delete(string $icno, string $id, string $ipAddress): bool
    {
        return $this->certificateService->delete($icno, $id, $ipAddress);
    }
}
