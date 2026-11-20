<?php

declare(strict_types=1);

namespace App\Web\Cuti\Observer;

use App\Shared\CutiMenuService;
use App\Shared\CutiObserverService;
use App\Shared\EmployeeRepository;
use App\Shared\LegacySessionReader;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class ObserverAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private EmployeeRepository $employees,
        private CutiMenuService $menu,
        private CutiObserverService $observers,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->redirect('/login');
        }

        $icno = $this->employees->icnoFromIdentity($legacySession['identityId']);
        $query = $request->getQueryParams();
        $authorized = $this->observers->isObserver($icno);

        return $this->viewRenderer
            ->render(__DIR__ . '/my-staff', [
                'rows' => $authorized ? $this->observers->observedStaff($icno, $query) : [],
                'authorized' => $authorized,
                'menuSections' => $this->menu->sectionsForStaff($icno),
                'departments' => $authorized ? $this->observers->departmentsFor($icno) : [],
                'campuses' => $authorized ? $this->observers->campusesFor($icno) : [],
                'query' => $query,
            ])
            ->withStatus($authorized ? 200 : 403);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
