<?php

declare(strict_types=1);

namespace App\Web\Biodata\Sections\Alamat;

use App\Shared\Biodata\Sections\AlamatService;
use App\Shared\BiodataProfileService;
use App\Shared\LegacySessionReader;
use App\Web\Biodata\Sections\SectionTemplatePath;
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
        private AlamatService $alamatService,
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
        $address = $icno === '' || $id === '' ? null : $this->alamatService->detailForIcno($icno, $id);
        $staff = $icno === '' ? null : $this->alamatService->listForIcno($icno);

        return $this->viewRenderer
            ->render(SectionTemplatePath::resolve($request, __DIR__, 'detail'), [
                'address' => $address,
                'staff' => $staff,
                'backUrl' => $targetIcno !== '' ? '/biodata/alamat/adminview?icno=' . rawurlencode($targetIcno) : '/biodata/alamat/view',
                'updateUrl' => $this->appendQuery('/biodata/alamat/update?id=' . rawurlencode($id), $targetIcno !== '' ? ['icno' => $targetIcno] : []),
            ])
            ->withStatus($address === null ? 404 : 200);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
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
