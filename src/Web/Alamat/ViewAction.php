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

final readonly class ViewAction
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
        $addresses = $icno === '' ? null : $this->addressService->listForIcno($icno);
        $query = $request->getQueryParams();
        $status = '';
        foreach (['created', 'updated', 'deleted'] as $key) {
            if (($query[$key] ?? '') === '1') {
                $status = $key;
                break;
            }
        }

        return $this->viewRenderer
            ->render(__DIR__ . '/view', [
                'addresses' => $addresses,
                'status' => $status,
            ])
            ->withStatus($addresses === null ? 404 : 200);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
