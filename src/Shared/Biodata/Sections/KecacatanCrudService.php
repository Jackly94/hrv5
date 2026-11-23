<?php

declare(strict_types=1);

namespace App\Shared\Biodata\Sections;

use App\Shared\DisabilityRecordService;
use Psr\Http\Message\UploadedFileInterface;

final readonly class KecacatanCrudService
{
    public function __construct(private DisabilityRecordService $disabilityService) {}

    public function staffForIcno(string $icno): ?array
    {
        return $this->disabilityService->staffForIcno($icno);
    }

    public function detailForIcno(string $icno, string $id): ?array
    {
        return $this->disabilityService->detailForIcno($icno, $id);
    }

    public function formOptions(): array
    {
        return $this->disabilityService->formOptions();
    }

    public function defaultValues(string $icno): array
    {
        return $this->disabilityService->defaultValues($icno);
    }

    public function valuesForEdit(string $icno, string $id): ?array
    {
        return $this->disabilityService->valuesForEdit($icno, $id);
    }

    public function save(string $icno, array $input, ?string $id, ?UploadedFileInterface $file, string $ipAddress): array
    {
        return $this->disabilityService->save($icno, $input, $id, $file, $ipAddress);
    }

    public function delete(string $icno, string $id, string $ipAddress): bool
    {
        return $this->disabilityService->delete($icno, $id, $ipAddress);
    }
}
