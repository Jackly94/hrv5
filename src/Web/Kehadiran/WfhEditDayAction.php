<?php

declare(strict_types=1);

namespace App\Web\Kehadiran;

use App\Shared\AttendanceService;
use App\Shared\LegacySessionReader;
use DateTimeImmutable;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class WfhEditDayAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
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

        $query = $request->getQueryParams();
        $date = $this->dateQuery($query, 'date');
        if ($date === '') {
            return $this->responseFactory->createResponse(404);
        }

        $departmentId = $this->positiveIntQuery($query, 'dept_id');
        $icno = (string) $attendance['icno'];

        if (strtoupper($request->getMethod()) === 'POST') {
            $parsedBody = $request->getParsedBody();
            $body = is_array($parsedBody) ? $parsedBody : [];
            $result = $this->attendanceService->saveSupervisorWfhDay($icno, $date, $body, $departmentId);
            $params = ['date' => $date];
            if ($departmentId !== null) {
                $params['dept_id'] = (string) $departmentId;
            }
            $params[$result['success'] ? 'updated' : 'error'] = '1';

            return $this->redirect('/kehadiran/wfh-edit-day?' . http_build_query($params));
        }

        $day = $this->attendanceService->supervisorWfhDay($icno, $date, $departmentId);
        if ($day === null) {
            return $this->responseFactory->createResponse(404);
        }

        return $this->viewRenderer->render(__DIR__ . '/wfh-edit-day', [
            'attendance' => $attendance,
            'identityId' => $legacySession['identityId'],
            'page' => ['title' => 'Set WFH Harian'],
            'day' => $day,
            'departmentId' => $departmentId,
            'message' => (($query['updated'] ?? null) === '1') ? 'Rekod WFH telah dikemaskini.' : ((($query['error'] ?? null) === '1') ? 'Rekod WFH tidak dikemaskini. Sila semak pilihan.' : ''),
            'messageType' => (($query['updated'] ?? null) === '1') ? 'success' : ((($query['error'] ?? null) === '1') ? 'error' : ''),
        ]);
    }

    /**
     * @param array<array-key, mixed> $query
     */
    private function positiveIntQuery(array $query, string $key): ?int
    {
        $value = $query[$key] ?? null;

        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    /**
     * @param array<array-key, mixed> $query
     */
    private function dateQuery(array $query, string $key): string
    {
        $value = $query[$key] ?? null;
        if (!is_string($value) || trim($value) === '') {
            return '';
        }

        try {
            return (new DateTimeImmutable(trim($value)))->format('Y-m-d');
        } catch (\Throwable) {
            return '';
        }
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
