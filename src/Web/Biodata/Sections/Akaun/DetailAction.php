<?php

declare(strict_types=1);

namespace App\Web\Biodata\Sections\Akaun;

use App\Shared\Biodata\Sections\AkaunService;
use App\Shared\BiodataProfileService;
use App\Shared\LegacySessionReader;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class DetailAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private BiodataProfileService $profileService,
        private AkaunService $akaunService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->redirect('/login');
        }

        $query = $request->getQueryParams();
        $id = (string) ($query['id'] ?? '');
        $targetIcno = $this->cleanQueryValue((string) ($query['icno'] ?? ''));
        $icno = $targetIcno !== ''
            ? $targetIcno
            : $this->profileService->icnoFromIdentity($legacySession['identityId']);
        $adminMode = $targetIcno !== '';
        $detail = $icno === '' ? null : $this->akaunService->detail($id, $icno, $adminMode);

        if ($detail !== null && $targetIcno !== '' && ($detail['updateUrl'] ?? '') !== '') {
            $detail['updateUrl'] = $this->appendQuery((string) $detail['updateUrl'], ['icno' => $targetIcno]);
        }

        return $this->viewRenderer
            ->render(__DIR__ . '/detail', [
                'detail' => $detail,
                'backUrl' => $targetIcno !== ''
                    ? '/biodata/akaun/admin-senarai-akaun?icno=' . rawurlencode($targetIcno)
                    : '/biodata/akaun/senarai-akaun',
            ])
            ->withStatus($detail === null ? 404 : 200);
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

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
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
