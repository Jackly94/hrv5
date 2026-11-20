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

final readonly class AdminAction
{
    private const PAGES = [
        'staff-all' => 'Staff lists',
        'admin-mth-rpt' => 'Statistic (JFPIU)',
        'color-rpt' => 'Warning Letters',
        'index' => 'Raw Data',
        'pantau-permohonan-wfh-admin' => 'Pantau Permohonan JAFPIB',
        'wfh-jadual-admin' => 'Jadual JAFPIB',
        'statistik-wfh' => 'Statistik WFH',
        'admin-post-list' => 'Admin Post (Akad)',
        'absent-list-bpsm' => 'Tidak Hadir Tanpa Cuti (THTC)',
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
                ->render(__DIR__ . '/admin-page', [
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
        $statFilters = [
            'date' => $this->stringQuery($query, 'date'),
            'department' => $this->stringQuery($query, 'department'),
            'month' => $this->stringQuery($query, 'bulan'),
            'year' => $this->stringQuery($query, 'tahun'),
        ];
        $rawFilters = [
            'q' => $this->stringQuery($query, 'q'),
            'date' => $this->stringQuery($query, 'date'),
        ];
        $thtcFilters = [
            'q' => $this->stringQuery($query, 'q'),
            'deptId' => $departmentId,
            'status' => $this->stringQuery($query, 'status'),
        ];
        $color = strtoupper($this->stringQuery($query, 'color') ?: $this->stringQuery($query, 'warna') ?: 'RED');

        return $this->viewRenderer->render(__DIR__ . '/admin-page', [
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
            'statFilters' => $statFilters,
            'rawFilters' => $rawFilters,
            'thtcFilters' => $thtcFilters,
            'adminScope' => $this->attendanceService->supervisorAccessScope($icno),
            'staffList' => $slug === 'staff-all'
                ? $this->attendanceService->administratorStaffList($icno, $staffFilters)
                : null,
            'monthlyReport' => $slug === 'admin-mth-rpt'
                ? $this->attendanceService->supervisorMonthlyReport($icno, $month, $year, $departmentId)
                : null,
            'warningLetters' => $slug === 'color-rpt'
                ? $this->attendanceService->administratorWarningLetters($icno, $month, $year, $color)
                : null,
            'rawAttendance' => $slug === 'index'
                ? $this->attendanceService->administratorRawAttendance($icno, $rawFilters)
                : null,
            'wfhApplications' => $slug === 'pantau-permohonan-wfh-admin'
                ? $this->attendanceService->administratorWfhApplications($icno, $wfhFilters)
                : null,
            'wfhSchedule' => $slug === 'wfh-jadual-admin'
                ? $this->attendanceService->supervisorWfhSchedule($icno, $month, $year, $departmentId)
                : null,
            'wfhStatistics' => $slug === 'statistik-wfh'
                ? $this->attendanceService->administratorWfhStatistics($icno, $statFilters)
                : null,
            'adminPosts' => $slug === 'admin-post-list'
                ? $this->attendanceService->administratorAdminPostList($icno, ['q' => $staffFilters['q']])
                : null,
            'thtcOverview' => $slug === 'absent-list-bpsm'
                ? $this->attendanceService->administratorThtcBpsmOverview($icno, $thtcFilters)
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
