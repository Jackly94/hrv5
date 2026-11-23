<?php

declare(strict_types=1);

namespace App\Web\Biodata\Sections\Alamat;

use App\Shared\Biodata\Sections\AlamatCrudService;
use App\Shared\BiodataProfileService;
use App\Shared\LegacySessionReader;
use App\Web\Biodata\Sections\SectionTemplatePath;
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
        private AlamatCrudService $alamatCrudService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->redirect('/login');
        }

        $query = $request->getQueryParams();
        $targetIcno = $this->cleanQueryValue((string) ($query['icno'] ?? ''));
        $icno = $targetIcno !== ''
            ? $targetIcno
            : $this->profileService->icnoFromIdentity($legacySession['identityId']);
        $staff = $icno === '' ? null : $this->alamatCrudService->listForIcno($icno);
        if ($staff === null) {
            return $this->render($request, null, $this->alamatCrudService->defaultValues($icno), [], 404);
        }

        $path = $request->getUri()->getPath();
        $isUpdate = str_ends_with($path, '/update');
        $id = $isUpdate ? (string) ($query['id'] ?? '') : null;

        if ($isUpdate) {
            $values = $id === null || $id === '' ? null : $this->alamatCrudService->valuesForEdit($icno, $id);
            if ($values === null) {
                return $this->render($request, $staff, $this->alamatCrudService->defaultValues($icno), ['general' => 'Rekod alamat tidak dijumpai.'], 404);
            }
        } else {
            $values = $this->alamatCrudService->defaultValues($icno);
        }

        if (!$isUpdate && strtoupper($request->getMethod()) === 'GET' && isset($query['samadengan'], $query['tambah'])) {
            $result = $this->alamatCrudService->copyAddress(
                $icno,
                (string) $query['samadengan'],
                (string) $query['tambah'],
                $this->clientIp($request),
            );

            if ($result['success'] === true) {
                return $this->redirect($this->appendQuery($this->backUrl($targetIcno), ['created' => '1']));
            }

            return $this->render($request, $staff, $result['values'], $result['errors']);
        }

        if (strtoupper($request->getMethod()) === 'POST') {
            $body = $request->getParsedBody();
            $body = is_array($body) ? $body : [];
            $result = $this->alamatCrudService->save($icno, $body, $id, $this->clientIp($request));

            if ($result['success'] === true) {
                return $this->redirect($this->appendQuery($this->backUrl($targetIcno), [$isUpdate ? 'updated' : 'created' => '1']));
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
        $query = $request->getQueryParams();
        $targetIcno = $this->cleanQueryValue((string) ($query['icno'] ?? ''));
        $backUrl = $this->backUrl($targetIcno);
        $formAction = $isUpdate
            ? '/biodata/alamat/update?id=' . rawurlencode((string) ($values['id'] ?? ''))
            : '/biodata/alamat/tambahalamat';
        $formAction = $this->appendQuery($formAction, $targetIcno !== '' ? ['icno' => $targetIcno] : []);

        return $this->viewRenderer
            ->render(SectionTemplatePath::resolve($request, __DIR__, 'form'), [
                'staff' => $staff,
                'mode' => $isUpdate ? 'update' : 'create',
                'options' => $this->alamatCrudService->formOptions(),
                'values' => $values,
                'errors' => $errors,
                'backUrl' => $backUrl,
                'formAction' => $formAction,
                'copyAddressUrl' => $this->appendQuery('/biodata/alamat/tambahalamat?samadengan=01&tambah=03', $targetIcno !== '' ? ['icno' => $targetIcno] : []),
                'hasCurrentAddress' => isset($values['icno']) && $values['icno'] !== ''
                    ? $this->alamatCrudService->hasCurrentAddress($values['icno'])
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

    private function backUrl(string $icno): string
    {
        return $icno !== '' ? '/biodata/alamat/adminview?icno=' . rawurlencode($icno) : '/biodata/alamat/view';
    }

    /**
     * @param array<string, string> $query
     */
    private function appendQuery(string $url, array $query): string
    {
        $queryString = http_build_query($query);
        if ($url === '' || $queryString === '') {
            return $url;
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . $queryString;
    }

    private function cleanQueryValue(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return trim(strtok($value, '?&') ?: $value);
    }
}
