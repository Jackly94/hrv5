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

final readonly class HeadDepartmentAction
{
    private const PAGES = [
        'kj-pantau-kehadiran' => 'Staff attendance',
        'wfh-jadual-kj' => 'Jadual WFH',
        'list-kj' => 'Tidak Hadir Tanpa Cuti (THTC)',
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
                ->render(__DIR__ . '/head-page', [
                    'attendance' => null,
                    'identityId' => $legacySession['identityId'],
                    'page' => ['slug' => $slug, 'title' => self::PAGES[$slug]],
                ])
                ->withStatus(403);
        }

        $query = $request->getQueryParams();
        $today = new DateTimeImmutable('today');
        $year = $this->intQuery($query, 'tahun', (int) $today->format('Y'));
        $month = max(1, min(12, $this->intQuery($query, 'bulan', (int) $today->format('m'))));
        $date = is_string($query['date'] ?? null) ? (string) $query['date'] : (is_string($query['tarikh'] ?? null) ? (string) $query['tarikh'] : $today->format('Y-m-d'));
        $icno = (string) $attendance['icno'];

        return $this->viewRenderer->render(__DIR__ . '/head-page', [
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
            'scope' => $this->attendanceService->departmentChiefScope($icno),
            'dailyAttendance' => $slug === 'kj-pantau-kehadiran'
                ? $this->attendanceService->departmentChiefDailyAttendance($icno, $date)
                : null,
            'wfhSchedule' => $slug === 'wfh-jadual-kj'
                ? $this->attendanceService->departmentChiefWfhSchedule($icno, $month, $year)
                : null,
            'thtcList' => $slug === 'list-kj'
                ? $this->attendanceService->departmentChiefThtcList($icno)
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
