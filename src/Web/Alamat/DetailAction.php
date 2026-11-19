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

final readonly class DetailAction
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

        $query = $request->getQueryParams();
        $id = (string) ($query['id'] ?? '');
        $icno = $this->profileService->icnoFromIdentity($legacySession['identityId']);
        $address = $icno === '' || $id === '' ? null : $this->addressService->detailForIcno($icno, $id);
        $staff = $icno === '' ? null : $this->addressService->listForIcno($icno);

        return $this->viewRenderer
            ->render(__DIR__ . '/detail', [
                'address' => $address,
                'staff' => $staff,
            ])
            ->withStatus($address === null ? 404 : 200);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
