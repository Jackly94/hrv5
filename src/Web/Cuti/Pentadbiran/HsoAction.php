<?php

declare(strict_types=1);

namespace App\Web\Cuti\Pentadbiran;

use App\Shared\CutiMenuService;
use App\Shared\CutiPentadbiranService;
use App\Shared\EmployeeRepository;
use App\Shared\LegacySessionReader;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class HsoAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private EmployeeRepository $employees,
        private CutiMenuService $menu,
        private CutiPentadbiranService $pentadbiran,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->redirect('/login');
        }

        $icno = $this->employees->icnoFromIdentity($legacySession['identityId']);
        $query = $request->getQueryParams();
        $authorized = $this->pentadbiran->isDepartmentChiefOrPp($icno);

        return $this->viewRenderer
            ->render(__DIR__ . '/hso', [
                'rows' => $authorized ? $this->pentadbiran->hsoApplications($icno, $query) : [],
                'authorized' => $authorized,
                'menuSections' => $this->menu->sectionsForStaff($icno),
                'query' => $query,
            ])
            ->withStatus($authorized ? 200 : 403);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}
