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

final readonly class ViewAction
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

        $icno = $this->profileService->icnoFromIdentity($legacySession['identityId']);
        $memberships = $icno === '' ? null : $this->membershipService->listForIcno($icno);
        $query = $request->getQueryParams();
        $status = '';
        foreach (['created', 'updated', 'deleted'] as $key) {
            if (array_key_exists($key, $query)) {
                $status = $key;
                break;
            }
        }

        return $this->viewRenderer
            ->render(__DIR__ . '/view', [
                'memberships' => $memberships,
                'status' => $status,
            ])
            ->withStatus($memberships === null ? 404 : 200);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
