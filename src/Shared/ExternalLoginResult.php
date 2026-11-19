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
        public ?string $legacySessionId,
        public array $errors,
    ) {}

    public static function success(
        string $identityId,
        string $displayName,
        ?string $returnUrl,
        string $legacySessionId,
    ): self
    {
        return new self(true, $identityId, $displayName, $returnUrl, $legacySessionId, []);
    }

    /**
     * @param array<string, string> $errors
     */
    public static function failure(array $errors): self
    {
        return new self(false, null, null, null, null, $errors);
    }
}
