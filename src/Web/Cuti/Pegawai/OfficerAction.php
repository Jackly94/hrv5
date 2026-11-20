<?php

declare(strict_types=1);

namespace App\Web\Cuti\Pegawai;

use App\Shared\CutiMenuService;
use App\Shared\CutiOfficerService;
use App\Shared\EmployeeRepository;
use App\Shared\LegacySessionReader;
use App\Shared\LegacyUrl;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class OfficerAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private EmployeeRepository $employees,
        private CutiMenuService $menu,
        private CutiOfficerService $officers,
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

        $authorized = $page['slug'] === 'sort'
            ? $this->officers->isDepartment230Chief($icno)
            : $this->officers->isOfficer($icno);
        $rows = $authorized ? $this->rowsForPage($page['slug'], $icno, $query) : [];

        return $this->viewRenderer
            ->render(__DIR__ . '/officer-page', [
                'page' => $page,
                'rows' => $rows,
                'authorized' => $authorized,
                'menuSections' => $this->menu->sectionsForStaff($icno),
                'legacyUrl' => LegacyUrl::to(ltrim($path, '/')),
                'year' => isset($query['year']) ? (int) $query['year'] : (int) date('Y'),
                'month' => isset($query['month']) && $query['month'] !== '' ? (int) $query['month'] : null,
            ])
            ->withStatus($authorized ? 200 : 403);
    }

    /**
     * @return array{slug: string, title: string, subtitle: string, icon: string, iconClass: string}|null
     */
    private function pageForPath(string $path): ?array
    {
        return match ($path) {
            '/cuti/pegawai/senarai-peraku' => ['slug' => 'senarai-peraku', 'title' => 'Pending Verification List', 'subtitle' => 'Permohonan menunggu perakuan pegawai.', 'icon' => 'square-pen', 'iconClass' => 'attendance-icon-warning'],
            '/cuti/pegawai/senarai-lulus' => ['slug' => 'senarai-lulus', 'title' => 'Pending Approval List', 'subtitle' => 'Permohonan menunggu kelulusan pegawai.', 'icon' => 'check-square', 'iconClass' => 'attendance-icon-success'],
            '/cuti/pegawai/gcr-list-checked' => ['slug' => 'gcr-list-checked', 'title' => 'GCR & CBTH Application List', 'subtitle' => 'Permohonan GCR/CBTH menunggu tindakan pegawai.', 'icon' => 'list-checks', 'iconClass' => 'attendance-icon-info'],
            '/cuti/pegawai/list' => ['slug' => 'list', 'title' => 'Approved Leave List', 'subtitle' => 'Senarai cuti diluluskan di bawah seliaan pegawai.', 'icon' => 'list', 'iconClass' => 'attendance-icon-success'],
            '/cuti/pegawai/staff-leave' => ['slug' => 'staff-leave', 'title' => 'Staff Under My Supervision', 'subtitle' => 'Kakitangan yang menggunakan pegawai semakan atau kelulusan ini.', 'icon' => 'users', 'iconClass' => 'attendance-icon-info'],
            '/cuti/pegawai/sort' => ['slug' => 'sort', 'title' => 'GCR & CBTH Application List (Ketua Jabatan BSM)', 'subtitle' => 'Senarai jabatan dengan permohonan GCR/CBTH menunggu kelulusan BSM.', 'icon' => 'building-2', 'iconClass' => 'attendance-icon-warning'],
            '/cuti/pegawai/bod-list' => ['slug' => 'bod-list', 'title' => 'Back On Duty', 'subtitle' => 'Permohonan kembali bertugas menunggu tindakan pelulus.', 'icon' => 'calendar-check', 'iconClass' => 'attendance-icon-success'],
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
            'senarai-peraku' => $this->officers->pendingVerification($icno),
            'senarai-lulus' => $this->officers->pendingApproval($icno),
            'gcr-list-checked' => $this->officers->gcrCheckedList($icno),
            'list' => $this->officers->approvedLeaveList(
                $icno,
                isset($query['year']) ? (int) $query['year'] : (int) date('Y'),
                isset($query['month']) && $query['month'] !== '' ? (int) $query['month'] : null,
            ),
            'staff-leave' => $this->officers->staffUnderSupervision($icno),
            'sort' => $this->officers->bsmGcrDepartments($icno),
            'bod-list' => $this->officers->backOnDutyList($icno),
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
