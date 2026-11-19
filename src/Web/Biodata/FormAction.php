<?php

declare(strict_types=1);

namespace App\Web\Biodata;

use App\Shared\BiodataProfileService;
use App\Shared\BiodataUpdateService;
use App\Shared\LegacySessionReader;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class FormAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private BiodataProfileService $profileService,
        private BiodataUpdateService $updateService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->redirect('/login');
        }

        $loggedInIcno = $this->profileService->icnoFromIdentity($legacySession['identityId']);
        $path = $request->getUri()->getPath();
        $query = $request->getQueryParams();
        $adminMode = $path === '/biodata/kemaskinikakitangan';
        $targetIcno = $adminMode ? (string) ($query['id'] ?? $query['icno'] ?? '') : $loggedInIcno;

        $form = $this->updateService->form($loggedInIcno, $targetIcno, $adminMode);
        if ($form === null) {
            return $this->redirect('/biodata/lihatbiodata?error=1');
        }

        if (strtoupper($request->getMethod()) === 'POST') {
            $body = $request->getParsedBody();
            $result = $this->updateService->save(
                $loggedInIcno,
                $targetIcno,
                $adminMode,
                is_array($body) ? $body : [],
                $this->clientIp($request),
            );

            if ($result['success'] === true) {
                return $this->redirect(($adminMode ? '/biodata/adminview?id=' . rawurlencode($targetIcno) : '/biodata/lihatbiodata') . '?updated=1');
            }

            $form['values'] = $result['values'] === [] ? $form['values'] : $result['values'];
            return $this->render($form, $result['errors']);
        }

        return $this->render($form);
    }

    /**
     * @param array<string, mixed> $form
     * @param array<string, string> $errors
     */
    private function render(array $form, array $errors = []): ResponseInterface
    {
        return $this->viewRenderer->render(__DIR__ . '/form', [
            'form' => $form,
            'errors' => $errors,
        ]);
    }

    private function clientIp(ServerRequestInterface $request): string
    {
        $server = $request->getServerParams();

        foreach (['HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            $value = $server[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return trim(explode(',', $value)[0]);
            }
        }

        return 'UNKNOWN';
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}
