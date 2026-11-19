<?php

declare(strict_types=1);

namespace App\Web\Cuti\Individu;

use App\Shared\CutiRecordService;
use App\Shared\EmployeeRepository;
use App\Shared\LegacySessionReader;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class AgreeAction
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
            return $this->redirect('/login');
        }

        $query = $request->getQueryParams();
        $body = $request->getParsedBody();
        $body = is_array($body) ? $body : [];
        $id = (int) ($body['id'] ?? $query['id'] ?? 0);
        $icno = $this->employees->icnoFromIdentity($legacySession['identityId']);

        if (strtoupper($request->getMethod()) !== 'POST') {
            return $this->redirect('/cuti/individu/ganti');
        }

        $result = $this->records->agreeSubstitute($icno, $id, $this->clientIp($request));

        return $this->redirect('/cuti/individu/ganti?' . ($result['success'] ? 'updated=1' : 'error=1'));
    }

    private function clientIp(ServerRequestInterface $request): string
    {
        $server = $request->getServerParams();
        foreach (['HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            $value = $server[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return substr(trim(explode(',', $value)[0]), 0, 45);
            }
        }

        return 'UNKNOWN';
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
