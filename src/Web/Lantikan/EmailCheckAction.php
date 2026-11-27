<?php

declare(strict_types=1);

namespace App\Web\Lantikan;

use App\Shared\LantikanService;
use App\Shared\LegacySessionReader;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class EmailCheckAction
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private LantikanService $lantikanService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->json(['available' => false, 'tone' => 'danger', 'message' => 'Sesi tamat. Sila login semula.'], 401);
        }

        $email = trim((string) ($request->getQueryParams()['email'] ?? ''));
        $result = $this->lantikanService->emailAvailabilityForIdentity($legacySession['identityId'], $email);
        if ($result === null) {
            return $this->json(['available' => false, 'tone' => 'danger', 'message' => 'Anda tidak dibenarkan membuat semakan Lantikan.'], 403);
        }

        return $this->json($result);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function json(array $payload, int $status = 200): ResponseInterface
    {
        $response = $this->responseFactory
            ->createResponse($status)
            ->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(json_encode($payload, JSON_THROW_ON_ERROR));

        return $response;
    }
}
