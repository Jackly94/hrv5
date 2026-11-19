<?php

declare(strict_types=1);

namespace App\Web\StaffRecord;

use App\Shared\BiodataProfileService;
use App\Shared\LegacySessionReader;
use App\Shared\StaffRecordSectionCrudService;
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
        private StaffRecordSectionCrudService $crudService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->redirect('/login');
        }

        $path = $request->getUri()->getPath();
        $key = $this->crudService->keyFromPath($path);
        if ($key === null) {
            return $this->responseFactory->createResponse(404);
        }

        $query = $request->getQueryParams();
        $isUpdate = str_ends_with($path, '/update');
        $id = $isUpdate ? (string) ($query['id'] ?? '') : null;
        $icno = $this->profileService->icnoFromIdentity($legacySession['identityId']);
        $form = $icno === '' ? null : $this->crudService->form($key, $icno, $id);
        if ($form === null) {
            return $this->render($key, null, [], ['general' => 'Rekod tidak dijumpai.'], $isUpdate ? 'update' : 'create', 404);
        }

        if (strtoupper($request->getMethod()) === 'POST') {
            $body = $request->getParsedBody();
            $body = is_array($body) ? $body : [];
            $result = $this->crudService->save($key, $icno, $body, $id, $this->clientIp($request));
            if ($result['success'] === true) {
                return $this->redirect($form['backUrl'] . '?' . ($isUpdate ? 'updated=1' : 'created=1'));
            }

            $form['values'] = $result['values'];

            return $this->render($key, $form, $result['values'], $result['errors'], $isUpdate ? 'update' : 'create');
        }

        return $this->render($key, $form, $form['values'], [], $isUpdate ? 'update' : 'create');
    }

    /**
     * @param array<string, mixed>|null $form
     * @param array<string, string> $values
     * @param array<string, string> $errors
     */
    private function render(string $key, ?array $form, array $values, array $errors, string $mode, int $status = 200): ResponseInterface
    {
        return $this->viewRenderer
            ->render(__DIR__ . '/form', [
                'key' => $key,
                'mode' => $mode,
                'form' => $form,
                'values' => $values,
                'errors' => $errors,
            ])
            ->withStatus($status);
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
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
