<?php

declare(strict_types=1);

namespace App\Web\Cuti\Supervisor;

use App\Shared\CutiMenuService;
use App\Shared\CutiSupervisorService;
use App\Shared\EmployeeRepository;
use App\Shared\LegacySessionReader;
use App\Shared\LegacyUrl;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class SupervisorAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private EmployeeRepository $employees,
        private CutiMenuService $menu,
        private CutiSupervisorService $supervisors,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->redirect('/login');
        }

        $icno = $this->employees->icnoFromIdentity($legacySession['identityId']);
        $path = $request->getUri()->getPath();
        $query = $request->getQueryParams();
        $page = $this->pageForPath($path);
        if ($page === null) {
            return $this->redirect('/cuti/individu/index');
        }

        $authorized = $this->supervisors->isSupervisor($icno);
        $rows = $authorized ? $this->rowsForPage($page['slug'], $icno, $query) : [];
        $queryString = http_build_query($query);

        return $this->viewRenderer
            ->render(__DIR__ . '/supervisor-page', [
                'page' => $page,
                'rows' => $rows,
                'authorized' => $authorized,
                'menuSections' => $this->menu->sectionsForStaff($icno),
                'legacyUrl' => LegacyUrl::to(ltrim($path, '/')) . ($queryString === '' ? '' : '?' . $queryString),
                'departments' => $authorized ? $this->supervisors->departmentsFor($icno) : [],
                'campuses' => $authorized ? $this->supervisors->campusesFor($icno) : [],
                'leaveTypes' => $authorized ? $this->supervisors->leaveTypes() : [],
                'query' => $query,
                'year' => $this->supervisors->yearParam($query),
                'month' => $this->supervisors->monthParam($query),
            ])
            ->withStatus($authorized ? 200 : 403);
    }

    /**
     * @return array{slug: string, title: string, subtitle: string, icon: string, iconClass: string}|null
     */
    private function pageForPath(string $path): ?array
    {
        return match ($path) {
            '/cuti/supervisor/sick-leave-list' => ['slug' => 'sick-leave-list', 'title' => 'Sick Leave List', 'subtitle' => 'Permohonan cuti sakit menunggu semakan atau kelulusan penyelia.', 'icon' => 'plane', 'iconClass' => 'attendance-icon-warning'],
            '/cuti/supervisor/supervised-staff' => ['slug' => 'supervised-staff', 'title' => 'Supervised Staff', 'subtitle' => 'Kakitangan dalam skop jabatan dan kampus penyelia cuti.', 'icon' => 'users', 'iconClass' => 'attendance-icon-info'],
            '/cuti/supervisor/leave-statement-report' => ['slug' => 'leave-statement-report', 'title' => 'Monthly Statement', 'subtitle' => 'Ringkasan cuti bulanan mengikut jabatan, bulan, tahun, dan jenis cuti.', 'icon' => 'file-text', 'iconClass' => 'attendance-icon-success'],
            '/cuti/supervisor/leave-monitoring' => ['slug' => 'leave-monitoring', 'title' => 'Monitor Staff Leave', 'subtitle' => 'Pemantauan status permohonan cuti dalam skop penyelia.', 'icon' => 'monitor', 'iconClass' => 'attendance-icon-warning'],
            '/cuti/supervisor/calender' => ['slug' => 'calender', 'title' => 'Leave Calendar', 'subtitle' => 'Cuti tahunan yang diluluskan untuk bulan dan jabatan terpilih.', 'icon' => 'calendar', 'iconClass' => 'attendance-icon-info'],
            '/cuti/supervisor/gcr-list' => ['slug' => 'gcr-list', 'title' => 'GCR & CBTH Application List', 'subtitle' => 'Permohonan GCR/CBTH menunggu semakan penyelia.', 'icon' => 'list-checks', 'iconClass' => 'attendance-icon-success'],
            '/cuti/supervisor/gcr-list-checked' => ['slug' => 'gcr-list-checked', 'title' => 'Reset GCR & CBTH Application', 'subtitle' => 'Senarai permohonan GCR/CBTH aktif untuk semakan/reset legacy.', 'icon' => 'rotate-ccw', 'iconClass' => 'attendance-icon-warning'],
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $query
     * @return list<array<string, mixed>>
     */
    private function rowsForPage(string $slug, string $icno, array $query): array
    {
        return match ($slug) {
            'sick-leave-list' => $this->supervisors->sickLeaveList($icno),
            'supervised-staff' => $this->supervisors->supervisedStaff($icno, $query),
            'leave-statement-report' => $this->supervisors->monthlyStatement($icno, $query),
            'leave-monitoring' => $this->supervisors->leaveMonitoring($icno, $query),
            'calender' => $this->supervisors->leaveCalendar($icno, $query),
            'gcr-list' => $this->supervisors->gcrList($icno),
            'gcr-list-checked' => $this->supervisors->gcrResetList($icno),
            default => [],
        };
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
