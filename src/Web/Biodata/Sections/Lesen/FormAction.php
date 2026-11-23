<?php

declare(strict_types=1);

namespace App\Web\Biodata\Sections\Lesen;

use App\Shared\BiodataProfileService;
use App\Shared\LegacySessionReader;
use App\Shared\Biodata\Sections\LesenCrudService;
use App\Web\Biodata\Sections\SectionTemplatePath;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class FormAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private BiodataProfileService $profileService,
        private LesenCrudService $lesenCrudService,
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
        $staff = $icno === '' ? null : $this->lesenCrudService->staffForIcno($icno);
        if ($staff === null) {
            return $this->render($request, null, $this->lesenCrudService->defaultValues($icno), [], 404);
        }

        $path = $request->getUri()->getPath();
        $isUpdate = str_ends_with($path, '/update');
        $id = $isUpdate ? (string) ($query['id'] ?? '') : null;
        $values = $isUpdate
            ? ($id === null || $id === '' ? null : $this->lesenCrudService->valuesForEdit($icno, $id))
            : $this->lesenCrudService->defaultValues($icno);

        if ($values === null) {
            return $this->render($request, $staff, $this->lesenCrudService->defaultValues($icno), ['general' => 'Rekod lesen tidak dijumpai.'], 404);
        }

        if (strtoupper($request->getMethod()) === 'POST') {
            $body = $request->getParsedBody();
            $body = is_array($body) ? $body : [];
            $result = $this->lesenCrudService->save(
                $icno,
                $body,
                $id,
                $this->uploadedFile($request, 'file'),
                $this->clientIp($request),
            );

            if ($result['success'] === true) {
                return $this->redirect($this->appendQuery($this->backUrl($targetIcno), [$isUpdate ? 'updated' : 'created' => '1']));
            }

            return $this->render($request, $staff, $result['values'], $result['errors']);
        }

        return $this->render($request, $staff, $values);
    }

    /**
     * @param array{name: string}|null $staff
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
        $formAction = $isUpdate
            ? '/biodata/lesen/update?id=' . rawurlencode((string) ($values['id'] ?? ''))
            : '/biodata/lesen/tambahlesen';
        $formAction = $this->appendQuery($formAction, $targetIcno !== '' ? ['icno' => $targetIcno] : []);

        return $this->viewRenderer
            ->render(SectionTemplatePath::resolve($request, __DIR__, 'form'), [
                'staff' => $staff,
                'mode' => $isUpdate ? 'update' : 'create',
                'options' => $this->lesenCrudService->formOptions(),
                'values' => $values,
                'errors' => $errors,
                'backUrl' => $this->backUrl($targetIcno),
                'formAction' => $formAction,
            ])
            ->withStatus($status);
    }

    private function uploadedFile(ServerRequestInterface $request, string $field): ?UploadedFileInterface
    {
        $file = $request->getUploadedFiles()[$field] ?? null;

        return $file instanceof UploadedFileInterface ? $file : null;
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
        return $icno !== '' ? '/biodata/lesen/adminview?icno=' . rawurlencode($icno) : '/biodata/lesen/view';
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
