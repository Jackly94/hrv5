<?php

declare(strict_types=1);

namespace App\Web\Lantikan;

use App\Shared\LantikanService;
use App\Shared\LegacySessionReader;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class SpdpFormAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private LantikanService $lantikanService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->redirect('/login');
        }

        $form = $this->lantikanService->formBlueprintForIdentity($legacySession['identityId'], 'spdp');
        if ($form === null) {
            return $this->redirect('/lantikan/index');
        }

        $result = null;
        if (strtoupper($request->getMethod()) === 'POST') {
            $body = $request->getParsedBody();
            $input = is_array($body) ? $body : [];
            if (isset($input['suggest_email'])) {
                $input['COEmail'] = $this->lantikanService->suggestedEmail((string) ($input['CONm'] ?? ''));
            }

            $result = $this->lantikanService->dryRunAppointment('spdp', $input);
            $form['values'] = $result['values'];
        }

        unset($form['fields']);

        return $this->viewRenderer->render(__DIR__ . '/spdp-form', [
            'form' => $form,
            'result' => $result,
        ]);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}
