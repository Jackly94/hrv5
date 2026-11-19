<?php

declare(strict_types=1);

namespace App\Web\BadanProfesional;

use App\Shared\BiodataProfileService;
use App\Shared\LegacySessionReader;
use App\Shared\ProfessionalMembershipService;
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
        private ProfessionalMembershipService $membershipService,
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
        $staff = $icno === '' ? null : $this->membershipService->listForIcno($icno);
        $membership = $icno === '' || $id === '' ? null : $this->membershipService->detailForIcno($icno, $id);

        return $this->viewRenderer
            ->render(__DIR__ . '/detail', [
                'staff' => $staff,
                'membership' => $membership,
            ])
            ->withStatus($staff === null || $membership === null ? 404 : 200);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
