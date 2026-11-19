<?php

declare(strict_types=1);

namespace App\Web\StaffRecord;

use App\Shared\BiodataProfileService;
use App\Shared\LegacySessionReader;
use App\Shared\StaffRecordSectionService;
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
        private StaffRecordSectionService $recordService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->redirect('/login');
        }

        $sectionKey = $this->recordService->sectionKeyFromPath($request->getUri()->getPath());
        if ($sectionKey === null) {
            return $this->responseFactory->createResponse(404);
        }

        $icno = $this->profileService->icnoFromIdentity($legacySession['identityId']);
        $section = $icno === '' ? null : $this->recordService->listForIcno($sectionKey, $icno);
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
                'section' => $section,
                'status' => $status,
            ])
            ->withStatus($section === null ? 404 : 200);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
