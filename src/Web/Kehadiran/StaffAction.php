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

final readonly class StaffAction
{
    private const PAGES = [
        'laporan-kehadiran' => 'Attendance report',
        'tindakan_ketidakpatuhan' => 'Pending for reason',
        'staf-color-list' => 'Card Color report',
        'kad-warna' => 'Card Colour info',
        'kad_warna' => 'Card Colour info',
        'wfh-list' => 'Senarai WFH / WFO',
        'absent-list' => 'Tidak Hadir Tanpa Cuti (THTC)',
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
                ->render(__DIR__ . '/staff-page', [
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
        $date = is_string($query['tarikh'] ?? null) ? $query['tarikh'] : null;
        $icno = (string) $attendance['icno'];

        return $this->viewRenderer->render(__DIR__ . '/staff-page', [
            'attendance' => $attendance,
            'identityId' => $legacySession['identityId'],
            'page' => [
                'slug' => $slug,
                'title' => self::PAGES[$slug],
            ],
            'years' => $this->attendanceService->activeYears(),
            'year' => $year,
            'month' => max(1, min(12, $month)),
            'date' => $date,
            'legacyBase' => LegacyUrl::base(),
            'report' => $slug === 'laporan-kehadiran'
                ? $this->attendanceService->attendanceReport($icno, $year, max(1, min(12, $month)))
                : null,
            'pendingReasons' => $slug === 'tindakan_ketidakpatuhan'
                ? $this->attendanceService->pendingReasonRecords($icno)
                : null,
            'cardColorList' => $slug === 'staf-color-list'
                ? $this->attendanceService->staffCardColorList($icno, $year)
                : null,
            'wfhRows' => $slug === 'wfh-list'
                ? $this->attendanceService->wfhList($icno)
                : null,
            'absentRows' => $slug === 'absent-list'
                ? $this->attendanceService->absentList($icno, $year, $date)
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
