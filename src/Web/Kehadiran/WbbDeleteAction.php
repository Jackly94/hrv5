<?php

declare(strict_types=1);

namespace App\Web\Kehadiran;

use App\Shared\AttendanceService;
use App\Shared\LegacySessionReader;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class WbbDeleteAction
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private AttendanceService $attendanceService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->redirect('/login');
        }

        $serverParams = $request->getServerParams();
        $attendance = $this->attendanceService->landingForIdentity(
            $legacySession['identityId'],
            is_string($serverParams['REMOTE_ADDR'] ?? null) ? $serverParams['REMOTE_ADDR'] : null,
        );
        if ($attendance === null) {
            return $this->responseFactory->createResponse(403);
        }

        $parsedBody = $request->getParsedBody();
        $body = is_array($parsedBody) ? $parsedBody : [];
        $id = is_numeric($body['id'] ?? null) ? (int) $body['id'] : 0;
        if ($id <= 0) {
            return $this->redirect('/kehadiran/staff-wbb?error=' . rawurlencode('Rekod WBB tidak sah.'));
        }

        $result = $this->attendanceService->deleteSupervisorWbb((string) $attendance['icno'], $id);
        $staffIcno = $result['staffIcno'] !== '' ? $result['staffIcno'] : (string) ($body['staff_icno'] ?? '');
        $target = $staffIcno === '' ? '/kehadiran/staff-wbb' : '/kehadiran/wbb-list?id=' . rawurlencode($staffIcno);
        $target .= '&' . ($result['success'] ? 'updated=' : 'error=') . rawurlencode($result['message']);

        return $this->redirect($target);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
