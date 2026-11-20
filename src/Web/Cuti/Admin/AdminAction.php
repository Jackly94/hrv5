<?php

declare(strict_types=1);

namespace App\Web\Cuti\Admin;

use App\Shared\CutiAdminService;
use App\Shared\CutiMenuService;
use App\Shared\EmployeeRepository;
use App\Shared\LegacySessionReader;
use App\Shared\LegacyUrl;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class AdminAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private EmployeeRepository $employees,
        private CutiMenuService $menu,
        private CutiAdminService $admins,
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

        $authorized = $this->authorized($page['slug'], $icno);
        $queryString = http_build_query($query);

        return $this->viewRenderer
            ->render(__DIR__ . '/admin-page', [
                'page' => $page,
                'rows' => $authorized ? $this->rowsForPage($page['slug'], $icno, $query) : [],
                'authorized' => $authorized,
                'menuSections' => $this->menu->sectionsForStaff($icno),
                'departments' => $authorized ? $this->admins->departments() : [],
                'years' => $authorized ? $this->admins->years() : [],
                'query' => $query,
                'year' => $this->admins->yearParam($query),
                'legacyUrl' => LegacyUrl::to(ltrim($path, '/')) . ($queryString === '' ? '' : '?' . $queryString),
            ])
            ->withStatus($authorized ? 200 : 403);
    }

    /**
     * @return array{slug: string, title: string, subtitle: string, icon: string, iconClass: string}|null
     */
    private function pageForPath(string $path): ?array
    {
        return match ($path) {
            '/cuti/admin/cr2-list' => ['slug' => 'cr2-list', 'title' => 'Verify', 'subtitle' => 'Permohonan menunggu semakan BSM/JTMK.', 'icon' => 'plane', 'iconClass' => 'attendance-icon-warning'],
            '/cuti/admin/behalf-list' => ['slug' => 'behalf-list', 'title' => 'On Behalf Of', 'subtitle' => 'Senarai pegawai tindakan bagi pihak pegawai kuasa.', 'icon' => 'users', 'iconClass' => 'attendance-icon-info'],
            '/cuti/admin/access' => ['slug' => 'access', 'title' => 'Access', 'subtitle' => 'Senarai akses modul cuti.', 'icon' => 'file-text', 'iconClass' => 'attendance-icon-success'],
            '/cuti/admin/gcr-application-list' => ['slug' => 'gcr-application-list', 'title' => 'GCR Application List', 'subtitle' => 'Permohonan GCR/CBTH aktif untuk pentadbir BSM/JTMK.', 'icon' => 'badge-check', 'iconClass' => 'attendance-icon-success'],
            '/cuti/admin/open-application' => ['slug' => 'open-application', 'title' => 'Buka Permohonan GCR & CBTH', 'subtitle' => 'Tetingkap pembukaan permohonan GCR/CBTH.', 'icon' => 'list', 'iconClass' => 'attendance-icon-info'],
            '/cuti/admin/ph-list' => ['slug' => 'ph-list', 'title' => 'Public Holiday Calendar', 'subtitle' => 'Kalendar cuti umum mengikut tahun.', 'icon' => 'calendar', 'iconClass' => 'attendance-icon-info'],
            '/cuti/admin/cb-list' => ['slug' => 'cb-list', 'title' => 'Maternity Leave', 'subtitle' => 'Senarai permohonan cuti bersalin.', 'icon' => 'calendar', 'iconClass' => 'attendance-icon-warning'],
            '/cuti/admin/admin-index' => ['slug' => 'admin-index', 'title' => 'Add Apply GCR After Closed Date', 'subtitle' => 'Allowlist staf untuk permohonan GCR/CBTH selepas tarikh tutup.', 'icon' => 'list', 'iconClass' => 'attendance-icon-success'],
            '/cuti/admin/staff-list' => ['slug' => 'staff-list', 'title' => 'Ketidakhadiran (Absent)', 'subtitle' => 'Senarai staf untuk laporan ketidakhadiran.', 'icon' => 'list', 'iconClass' => 'attendance-icon-warning'],
            '/cuti/admin/report' => ['slug' => 'report', 'title' => 'Report by Department', 'subtitle' => 'Senarai permohonan cuti dengan tapisan jabatan dan tahun.', 'icon' => 'bar-chart-3', 'iconClass' => 'attendance-icon-info'],
            '/cuti/admin/report-leave-list' => ['slug' => 'report-leave-list', 'title' => 'Statistic by Leave Type', 'subtitle' => 'Jumlah rekod dan hari mengikut jenis cuti.', 'icon' => 'bar-chart-3', 'iconClass' => 'attendance-icon-success'],
            default => null,
        };
    }

    private function authorized(string $slug, string $icno): bool
    {
        if ($slug === 'access') {
            return $this->admins->isAdmin($icno) || $this->admins->isSpecialAdmin($icno);
        }
        if (in_array($slug, ['gcr-application-list', 'open-application'], true)) {
            return $this->admins->isGcrAdmin($icno);
        }

        return $this->admins->isAdmin($icno);
    }

    /**
     * @param array<string, mixed> $query
     * @return list<array<string, mixed>>
     */
    private function rowsForPage(string $slug, string $icno, array $query): array
    {
        return match ($slug) {
            'cr2-list' => $this->admins->cr2Queue($icno, $query),
            'behalf-list' => $this->admins->behalfList(),
            'access' => $this->admins->accessList($icno, $query),
            'gcr-application-list' => $this->admins->gcrApplications($query),
            'open-application' => $this->admins->openApplications(),
            'ph-list' => $this->admins->publicHolidays($query),
            'cb-list' => $this->admins->maternityLeave($query),
            'admin-index' => $this->admins->gcrAllowlist(),
            'staff-list' => $this->admins->staffList($query),
            'report' => $this->admins->departmentReport($query),
            'report-leave-list' => $this->admins->leaveTypeStatistic($query),
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
