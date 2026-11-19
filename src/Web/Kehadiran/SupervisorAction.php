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

final readonly class SupervisorAction
{
    private const PAGES = [
        'senarai_kakitangan' => 'Staff lists',
        'staff-wbb' => 'Staff Working hours',
        'sup-mth-rpt' => 'Monthly Reports',
        'sup-year-rpt' => 'Yearly Reports',
        'senarai-warna-kad' => 'Card Colour Report',
        'sup-year-performance-rpt' => 'Card Colour Performance',
        'pantau-staf' => 'Pantau Staf',
        'shield-status-list' => 'Shields Status',
        'staff-shift-list' => 'Staff Shift List',
        'shift-setup' => 'Shift Setup',
        'manage-shift-supervisor' => 'Manage Supervisor/Staff',
        'wfh-by-mth' => 'Senarai Bulanan WFH',
        'wfh-jadual' => 'Jadual WFH',
        'pantau-permohonan-wfh' => 'Pantau Permohonan WFH',
        'absent-list-sv' => 'Tidak Hadir Tanpa Cuti (THTC)',
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
                ->render(__DIR__ . '/supervisor-page', [
                    'attendance' => null,
                    'identityId' => $legacySession['identityId'],
                    'page' => ['slug' => $slug, 'title' => self::PAGES[$slug]],
                ])
                ->withStatus(403);
        }

        $query = $request->getQueryParams();
        $today = new DateTimeImmutable('today');
        $year = $this->intQuery($query, 'tahun', $this->intQuery($query, 'year', (int) $today->format('Y')));
        $month = max(1, min(12, $this->intQuery($query, 'bulan', (int) $today->format('m'))));
        $listPage = max(1, $this->intQuery($query, 'page', 1));
        $departmentId = $this->positiveIntQuery($query, 'dept_id');
        $icno = (string) $attendance['icno'];
        $staffFilters = [
            'q' => $this->stringQuery($query, 'q'),
            'deptId' => $departmentId,
            'grade' => $this->stringQuery($query, 'grade'),
        ];
        $wfhFilters = [
            'q' => $this->stringQuery($query, 'q'),
            'status' => $this->stringQuery($query, 'status'),
        ];

        return $this->viewRenderer->render(__DIR__ . '/supervisor-page', [
            'attendance' => $attendance,
            'identityId' => $legacySession['identityId'],
            'page' => [
                'slug' => $slug,
                'title' => self::PAGES[$slug],
            ],
            'years' => $this->attendanceService->activeYears(),
            'year' => $year,
            'month' => $month,
            'listPage' => $listPage,
            'pageSize' => 15,
            'departmentId' => $departmentId,
            'staffFilters' => $staffFilters,
            'wfhFilters' => $wfhFilters,
            'supervisorScope' => $this->attendanceService->supervisorAccessScope($icno),
            'staffList' => $slug === 'senarai_kakitangan'
                ? $this->attendanceService->supervisorStaffList($icno, $staffFilters)
                : null,
            'staffWbb' => $slug === 'staff-wbb'
                ? $this->attendanceService->supervisorStaffWbb($icno, $staffFilters)
                : null,
            'monthlyReport' => $slug === 'sup-mth-rpt'
                ? $this->attendanceService->supervisorMonthlyReport($icno, $month, $year, $departmentId)
                : null,
            'yearlyReport' => $slug === 'sup-year-rpt'
                ? $this->attendanceService->supervisorYearlyReport($icno, $year, $staffFilters)
                : null,
            'cardColorReport' => $slug === 'senarai-warna-kad'
                ? $this->attendanceService->supervisorCardColorReport($icno, $month, $year, $staffFilters)
                : null,
            'cardPerformanceReport' => $slug === 'sup-year-performance-rpt'
                ? $this->attendanceService->supervisorCardPerformanceReport($icno, $year, $staffFilters)
                : null,
            'pendingStaff' => $slug === 'pantau-staf'
                ? $this->attendanceService->supervisorPendingStaff($icno, $staffFilters)
                : null,
            'shieldStatus' => $slug === 'shield-status-list'
                ? $this->attendanceService->supervisorShieldStatus($icno, $staffFilters)
                : null,
            'shiftStaff' => $slug === 'staff-shift-list'
                ? $this->attendanceService->supervisorShiftStaff($icno, ['q' => $staffFilters['q']])
                : null,
            'shiftSetup' => $slug === 'shift-setup'
                ? $this->attendanceService->supervisorShiftSetup($icno, $month, $year)
                : null,
            'manageShiftSupervisor' => $slug === 'manage-shift-supervisor'
                ? $this->attendanceService->supervisorManageShiftSupervisor($icno, ['q' => $staffFilters['q']])
                : null,
            'wfhMonth' => $slug === 'wfh-by-mth'
                ? $this->attendanceService->supervisorWfhByMonth($icno, $month, $year, $departmentId)
                : null,
            'wfhSchedule' => $slug === 'wfh-jadual'
                ? $this->attendanceService->supervisorWfhSchedule($icno, $month, $year, $departmentId)
                : null,
            'wfhApplications' => $slug === 'pantau-permohonan-wfh'
                ? $this->attendanceService->supervisorWfhApplications($icno, $wfhFilters)
                : null,
            'thtcList' => $slug === 'absent-list-sv'
                ? $this->attendanceService->supervisorThtcList($icno)
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
    private function stringQuery(array $query, string $key): string
    {
        $value = $query[$key] ?? '';

        return is_string($value) ? trim($value) : '';
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
