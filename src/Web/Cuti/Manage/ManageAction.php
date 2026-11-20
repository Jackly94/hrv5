<?php

declare(strict_types=1);

namespace App\Web\Cuti\Manage;

use App\Shared\CutiMenuService;
use App\Shared\CutiSectorHrService;
use App\Shared\EmployeeRepository;
use App\Shared\LegacySessionReader;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class ManageAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private EmployeeRepository $employees,
        private CutiMenuService $menu,
        private CutiSectorHrService $sectorHr,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->redirect('/login');
        }

        $icno = $this->employees->icnoFromIdentity($legacySession['identityId']);
        $authorized = $this->sectorHr->isSectorHrUser($icno);

        return $this->viewRenderer
            ->render(__DIR__ . '/list', [
                'rows' => $authorized ? $this->sectorHr->managementRows() : [],
                'authorized' => $authorized,
                'menuSections' => $this->menu->sectionsForStaff($icno),
            ])
            ->withStatus($authorized ? 200 : 403);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}
