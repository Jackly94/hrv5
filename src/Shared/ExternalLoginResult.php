<?php

declare(strict_types=1);

namespace App\Shared;

final readonly class ExternalLoginResult
{
    /**
     * @param array<string, string> $errors
     */
    private function __construct(
        public bool $success,
        public ?string $identityId,
        public ?string $displayName,
        public ?string $returnUrl,
        public array $errors,
    ) {}

    public static function success(string $identityId, string $displayName, ?string $returnUrl): self
    {
        return new self(true, $identityId, $displayName, $returnUrl, []);
    }

    /**
     * @param array<string, string> $errors
     */
    public static function failure(array $errors): self
    {
        return new self(false, null, null, null, $errors);
    }
}
