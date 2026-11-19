<?php

declare(strict_types=1);

namespace App\Web\Cuti\Individu;

use App\Shared\CutiRecordService;
use App\Shared\EmployeeRepository;
use App\Shared\LegacySessionReader;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class JsonCalendarAction
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private EmployeeRepository $employees,
        private CutiRecordService $records,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->json([], 401);
        }

        $icno = $this->employees->icnoFromIdentity($legacySession['identityId']);

        return $this->json($this->records->calendarEvents($icno));
    }

    /**
     * @param mixed $payload
     */
    private function json(mixed $payload, int $status = 200): ResponseInterface
    {
        $response = $this->responseFactory
            ->createResponse($status)
            ->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(json_encode($payload, JSON_THROW_ON_ERROR));

        return $response;
    }
}
