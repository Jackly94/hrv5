<?php

declare(strict_types=1);

namespace App\Shared\Biodata\Sections;

use App\Shared\LicenseRecordService;
use Psr\Http\Message\UploadedFileInterface;

final readonly class LesenCrudService
{
    public function __construct(private LicenseRecordService $licenseService) {}

    /**
     * @return array{name: string}|null
     */
    public function staffForIcno(string $icno): ?array
    {
        return $this->licenseService->staffForIcno($icno);
    }

    /**
     * @return array<string, list<array{id: string, name: string}>>
     */
    public function formOptions(): array
    {
        return $this->licenseService->formOptions();
    }

    /**
     * @return array<string, string>
     */
    public function defaultValues(string $icno): array
    {
        return $this->licenseService->defaultValues($icno);
    }

    /**
     * @return array<string, string>|null
     */
    public function valuesForEdit(string $icno, string $id): ?array
    {
        return $this->licenseService->valuesForEdit($icno, $id);
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, values: array<string, string>, errors: array<string, string>, id?: string}
     */
    public function save(string $icno, array $input, ?string $id, ?UploadedFileInterface $file, string $ipAddress): array
    {
        return $this->licenseService->save($icno, $input, $id, $file, $ipAddress);
    }
}
