<?php

declare(strict_types=1);

namespace App\Web\Cuti\Individu;

use App\Shared\CutiEntitlementService;
use App\Shared\CutiApplicationService;
use App\Shared\CutiGcrService;
use App\Shared\CutiMenuService;
use App\Shared\CutiRecordService;
use App\Shared\EmployeeRepository;
use App\Shared\LegacyUrl;
use App\Shared\LegacySessionReader;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class PageAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private EmployeeRepository $employees,
        private CutiMenuService $menu,
        private CutiRecordService $records,
        private CutiEntitlementService $entitlements,
        private CutiApplicationService $applications,
        private CutiGcrService $gcr,
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

        if ($path === '/cuti/individu/pilih') {
            return $this->viewRenderer->render(__DIR__ . '/pilih', [
                'title' => 'Pilih Jenis Cuti',
                'leaveTypes' => $this->applications->selectableLeaveTypes($icno),
                'menuSections' => $this->menu->sectionsForStaff($icno),
            ]);
        }

        if ($path === '/cuti/individu/ganti') {
            return $this->viewRenderer->render(__DIR__ . '/ganti', [
                'title' => 'Substitute',
                'rows' => $this->records->pendingSubstituteRecords($icno),
                'menuSections' => $this->menu->sectionsForStaff($icno),
                'legacyUrl' => LegacyUrl::to('cuti/individu/ganti'),
            ]);
        }

        if ($path === '/cuti/individu/substitute-list') {
            $year = isset($query['year']) ? (int) $query['year'] : (int) date('Y');
            return $this->viewRenderer->render(__DIR__ . '/substitute-list', [
                'title' => 'Substitute List',
                'rows' => $this->records->substituteRecords($icno, $year),
                'year' => $year,
                'years' => $this->records->substituteYearsForStaff($icno),
                'menuSections' => $this->menu->sectionsForStaff($icno),
                'legacyUrl' => LegacyUrl::to('cuti/individu/substitute-list'),
            ]);
        }

        if ($path === '/cuti/individu/statement') {
            $year = isset($query['year']) ? (int) $query['year'] : (int) date('Y');
            return $this->viewRenderer->render(__DIR__ . '/statement', [
                'title' => 'Leave Statement',
                'statement' => $this->records->statementForStaff($icno, $year),
                'year' => $year,
                'years' => $this->records->entitlementYearsForStaff($icno),
                'menuSections' => $this->menu->sectionsForStaff($icno),
                'legacyUrl' => LegacyUrl::to('cuti/individu/statement'),
            ]);
        }

        if ($path === '/cuti/individu/entitlement') {
            return $this->viewRenderer->render(__DIR__ . '/entitlement', [
                'title' => 'Leave Entitlement',
                'rows' => $this->entitlements->allForStaff($icno),
                'gcrTotals' => $this->entitlements->gcrTotals($icno),
                'menuSections' => $this->menu->sectionsForStaff($icno),
                'legacyUrl' => LegacyUrl::to('cuti/individu/entitlement'),
            ]);
        }

        if ($path === '/cuti/individu/gcr-list') {
            return $this->viewRenderer->render(__DIR__ . '/gcr-list', [
                'title' => 'My GCR & CBTH Application List',
                'rows' => $this->gcr->applicationsForStaff($icno),
                'openWindow' => $this->gcr->openWindow(),
                'menuSections' => $this->menu->sectionsForStaff($icno),
                'legacyUrl' => LegacyUrl::to('cuti/individu/gcr-list'),
            ]);
        }

        return $this->redirect('/cuti/individu/index');
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
