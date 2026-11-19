<?php

declare(strict_types=1);

namespace App\Web\Alamat;

use App\Shared\AddressRecordService;
use App\Shared\BiodataProfileService;
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
        private AddressRecordService $addressService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->redirect('/login');
        }

        $icno = $this->profileService->icnoFromIdentity($legacySession['identityId']);
        $staff = $icno === '' ? null : $this->addressService->listForIcno($icno);
        if ($staff === null) {
            return $this->render($request, null, $this->addressService->defaultValues($icno), [], 404);
        }

        $query = $request->getQueryParams();
        $path = $request->getUri()->getPath();
        $isUpdate = str_ends_with($path, '/update');
        $id = $isUpdate ? (string) ($query['id'] ?? '') : null;

        if ($isUpdate) {
            $values = $id === null || $id === '' ? null : $this->addressService->valuesForEdit($icno, $id);
            if ($values === null) {
                return $this->render($request, $staff, $this->addressService->defaultValues($icno), ['general' => 'Rekod alamat tidak dijumpai.'], 404);
            }
        } else {
            $values = $this->addressService->defaultValues($icno);
        }

        if (!$isUpdate && strtoupper($request->getMethod()) === 'GET' && isset($query['samadengan'], $query['tambah'])) {
            $result = $this->addressService->copyAddress(
                $icno,
                (string) $query['samadengan'],
                (string) $query['tambah'],
                $this->clientIp($request),
            );

            if ($result['success'] === true) {
                return $this->redirect('/alamat/view?created=1');
            }

            return $this->render($request, $staff, $result['values'], $result['errors']);
        }

        if (strtoupper($request->getMethod()) === 'POST') {
            $body = $request->getParsedBody();
            $body = is_array($body) ? $body : [];
            $result = $this->addressService->save($icno, $body, $id, $this->clientIp($request));

            if ($result['success'] === true) {
                return $this->redirect('/alamat/view?' . ($isUpdate ? 'updated=1' : 'created=1'));
            }

            $values = $result['values'];
            $errors = $result['errors'];

            return $this->render($request, $staff, $values, $errors);
        }

        return $this->render($request, $staff, $values);
    }

    /**
     * @param array{name: string, rows: list<array<string, string>>}|null $staff
     * @param array<string, string> $values
     * @param array<string, string> $errors
     */
    private function render(
        ServerRequestInterface $request,
        ?array $staff,
        array $values,
        array $errors = [],
        int $status = 200,
    ): ResponseInterface {
        $isUpdate = str_ends_with($request->getUri()->getPath(), '/update');

        return $this->viewRenderer
            ->render(__DIR__ . '/form', [
                'staff' => $staff,
                'mode' => $isUpdate ? 'update' : 'create',
                'options' => $this->addressService->formOptions(),
                'values' => $values,
                'errors' => $errors,
                'hasCurrentAddress' => isset($values['icno']) && $values['icno'] !== ''
                    ? $this->addressService->hasCurrentAddress($values['icno'])
                    : false,
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
