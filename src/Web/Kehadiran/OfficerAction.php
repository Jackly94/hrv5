<?php

declare(strict_types=1);

namespace App\Web\Kehadiran;

use App\Shared\AttendanceService;
use App\Shared\LegacyUrl;
use App\Shared\LegacySessionReader;
use DateTimeImmutable;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class OfficerAction
{
    private const PAGES = [
        'senarai_tindakan' => 'Approval (Reason)',
        'staff-color-card' => 'Card Colour Report',
        'pantau-kehadiran' => 'Attendance report',
        'monthly-summary' => 'Monthly report',
        'wfh-lulus' => 'Senarai Tindakan WFH',
        'pantau-permohonan-wfh-pegawai' => 'Senarai Permohonan WFH',
        'absent-list-kp' => 'Tidak Hadir Tanpa Cuti (THTC)',
    ];

    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private AttendanceService $attendanceService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $slug = basename($request->getUri()->getPath());
        if (!isset(self::PAGES[$slug])) {
            return $this->responseFactory->createResponse(404);
        }

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
                ->render(__DIR__ . '/officer-page', [
                    'attendance' => null,
                    'identityId' => $legacySession['identityId'],
                    'page' => ['slug' => $slug, 'title' => self::PAGES[$slug]],
                ])
                ->withStatus(403);
        }

        $query = $request->getQueryParams();
        $today = new DateTimeImmutable('today');
        $year = $this->intQuery($query, 'tahun', $this->intQuery($query, 'year', (int) $today->format('Y')));
        $month = $this->intQuery($query, 'bulan', (int) $today->format('m'));
        $month = max(1, min(12, $month));
        $date = is_string($query['date'] ?? null) ? (string) $query['date'] : (is_string($query['tarikh'] ?? null) ? (string) $query['tarikh'] : $today->format('Y-m-d'));
        $icno = (string) $attendance['icno'];
        $wfhFilters = [
            'q' => is_string($query['q'] ?? null) ? trim((string) $query['q']) : '',
            'status' => is_string($query['status'] ?? null) ? trim((string) $query['status']) : '',
        ];

        return $this->viewRenderer->render(__DIR__ . '/officer-page', [
            'attendance' => $attendance,
            'identityId' => $legacySession['identityId'],
            'page' => [
                'slug' => $slug,
                'title' => self::PAGES[$slug],
            ],
            'years' => $this->attendanceService->activeYears(),
            'year' => $year,
            'month' => $month,
            'date' => $date,
            'legacyBase' => LegacyUrl::base(),
            'wfhFilters' => $wfhFilters,
            'cardColorReport' => $slug === 'staff-color-card'
                ? $this->attendanceService->officerCardColorReport($icno, $month, $year)
                : null,
            'monthlySummary' => $slug === 'monthly-summary'
                ? $this->attendanceService->officerMonthlySummary($icno, $month, $year)
                : null,
            'dailyAttendance' => $slug === 'pantau-kehadiran'
                ? $this->attendanceService->officerDailyAttendance($icno, $date)
                : null,
            'wfhApplications' => $slug === 'pantau-permohonan-wfh-pegawai'
                ? $this->attendanceService->officerWfhApplications($icno, $wfhFilters)
                : null,
            'pendingWfhApprovals' => $slug === 'wfh-lulus'
                ? $this->attendanceService->officerPendingWfhApprovals($icno)
                : null,
            'pendingReasonApprovals' => $slug === 'senarai_tindakan'
                ? $this->attendanceService->officerPendingReasonApprovals($icno)
                : null,
            'thtcDepartment' => $slug === 'absent-list-kp'
                ? $this->attendanceService->officerThtcDepartmentList($icno)
                : null,
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
