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

final readonly class SupervisorStaffAttendanceReportAction
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
            return $this->viewRenderer
                ->render(__DIR__ . '/supervisor-staff-attendance-report', [
                    'attendance' => null,
                    'identityId' => $legacySession['identityId'],
                    'page' => ['title' => 'Supervisor Staff Attendance Report'],
                ])
                ->withStatus(403);
        }

        $query = $request->getQueryParams();
        $today = new DateTimeImmutable('today');
        $year = $this->intQuery($query, 'tahun', $this->intQuery($query, 'year', (int) $today->format('Y')));
        $month = max(1, min(12, $this->intQuery($query, 'bulan', (int) $today->format('m'))));
        $staffIcno = is_string($query['icno'] ?? null) ? trim($query['icno']) : '';

        $report = $this->attendanceService->supervisorStaffAttendanceReport(
            (string) $attendance['icno'],
            $staffIcno,
            $year,
            $month,
        );

        if ($report === null) {
            return $this->viewRenderer
                ->render(__DIR__ . '/supervisor-staff-attendance-report', [
                    'attendance' => $attendance,
                    'identityId' => $legacySession['identityId'],
                    'page' => ['title' => 'Supervisor Staff Attendance Report'],
                    'years' => $this->attendanceService->activeYears(),
                    'year' => $year,
                    'month' => $month,
                    'staffIcno' => $staffIcno,
                    'report' => null,
                ])
                ->withStatus(403);
        }

        return $this->viewRenderer->render(__DIR__ . '/supervisor-staff-attendance-report', [
            'attendance' => $attendance,
            'identityId' => $legacySession['identityId'],
            'page' => ['title' => 'Supervisor Staff Attendance Report'],
            'years' => $this->attendanceService->activeYears(),
            'year' => $year,
            'month' => $month,
            'staffIcno' => $staffIcno,
            'report' => $report,
        ]);
    }

    /**
     * @param array<array-key, mixed> $query
     */
    private function intQuery(array $query, string $key, int $fallback): int
    {
        $value = $query[$key] ?? null;

        return is_numeric($value) ? (int) $value : $fallback;
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
