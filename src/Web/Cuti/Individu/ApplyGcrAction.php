<?php

declare(strict_types=1);

namespace App\Web\Cuti\Individu;

use App\Shared\CutiGcrService;
use App\Shared\CutiMenuService;
use App\Shared\EmployeeRepository;
use App\Shared\LegacyUrl;
use App\Shared\LegacySessionReader;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class ApplyGcrAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private EmployeeRepository $employees,
        private CutiMenuService $menu,
        private CutiGcrService $gcr,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->redirect('/login');
        }

        $icno = $this->employees->icnoFromIdentity($legacySession['identityId']);

        if (strtoupper($request->getMethod()) === 'POST') {
            $body = $request->getParsedBody();
            $body = is_array($body) ? $body : [];
            $result = $this->gcr->submit($icno, $body, $this->clientIp($request));
            if ($result['success']) {
                return $this->redirect('/cuti/individu/gcr-list?created=1');
            }

            return $this->render($icno, $this->gcr->formData($icno, $result['values'], $result['errors']));
        }

        return $this->render($icno, $this->gcr->formData($icno));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function render(string $icno, array $data): ResponseInterface
    {
        return $this->viewRenderer->render(__DIR__ . '/apply-gcr', [
            ...$data,
            'title' => 'GCR & CBTH Application',
            'menuSections' => $this->menu->sectionsForStaff($icno),
            'legacyUrl' => LegacyUrl::to('cuti/individu/apply-gcr'),
        ]);
    }

    private function clientIp(ServerRequestInterface $request): string
    {
        $server = $request->getServerParams();
        foreach (['HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            $value = $server[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return substr(trim(explode(',', $value)[0]), 0, 45);
            }
        }

        return 'UNKNOWN';
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
