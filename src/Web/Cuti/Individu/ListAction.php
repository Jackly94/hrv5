<?php

declare(strict_types=1);

namespace App\Web\Cuti\Individu;

use App\Shared\CutiMenuService;
use App\Shared\CutiRecordService;
use App\Shared\EmployeeRepository;
use App\Shared\LegacySessionReader;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class ListAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private EmployeeRepository $employees,
        private CutiMenuService $menu,
        private CutiRecordService $records,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->redirect('/login');
        }

        $query = $request->getQueryParams();
        $year = isset($query['year']) ? (int) $query['year'] : (int) date('Y');
        $type = isset($query['jenis_cuti_id']) && $query['jenis_cuti_id'] !== '' ? (int) $query['jenis_cuti_id'] : null;
        $icno = $this->employees->icnoFromIdentity($legacySession['identityId']);

        return $this->viewRenderer->render(__DIR__ . '/list', [
            'title' => 'Senarai Permohonan Cuti',
            'rows' => $this->records->recordsForStaff($icno, $year, $type),
            'years' => $this->records->yearsForStaff($icno),
            'leaveTypes' => $this->records->leaveTypesForStaff($icno),
            'year' => $year,
            'leaveTypeId' => $type,
            'menuSections' => $this->menu->sectionsForStaff($icno),
        ]);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
