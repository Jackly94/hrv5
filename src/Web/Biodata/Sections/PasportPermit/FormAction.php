<?php

declare(strict_types=1);

namespace App\Web\Biodata\Sections\PasportPermit;

use App\Shared\Biodata\Sections\PasportPermitCrudService;
use App\Shared\BiodataProfileService;
use App\Shared\LegacySessionReader;
use App\Web\Biodata\Sections\StaffRecordSectionActionSupport as Support;
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
        private PasportPermitCrudService $pasportPermitCrudService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return Support::redirect($this->responseFactory, '/login');
        }

        $recordKey = $this->pasportPermitCrudService->keyFromPath($request->getUri()->getPath());
        if ($recordKey === null) {
            return Support::redirect($this->responseFactory, '/biodata/userview?error=1');
        }

        $query = $request->getQueryParams();
        $id = (string) ($query['id'] ?? '');
        $targetIcno = Support::cleanQueryValue((string) ($query['icno'] ?? ''));
        $icno = $targetIcno !== ''
            ? $targetIcno
            : $this->profileService->icnoFromIdentity($legacySession['identityId']);
        $adminMode = $targetIcno !== '';
        $form = $icno === '' ? null : $this->pasportPermitCrudService->form($recordKey, $icno, $id !== '' ? $id : null);
        if ($form === null) {
            return Support::redirect($this->responseFactory, $adminMode ? '/biodata/adminview?id=' . rawurlencode($icno) . '&error=1' : '/biodata/userview?error=1');
        }
        if ($adminMode) {
            $form = $this->withAdminContext($form, $icno);
        }

        if (strtoupper($request->getMethod()) === 'POST') {
            $body = $request->getParsedBody();
            $body = is_array($body) ? $body : [];
            $result = $this->pasportPermitCrudService->save(
                $recordKey,
                $icno,
                $body,
                Support::uploadedFiles($request),
                $id !== '' ? $id : null,
                Support::clientIp($request),
            );

            if ($result['success'] === true) {
                return Support::redirect($this->responseFactory, Support::appendQuery((string) $form['backUrl'], [$id !== '' ? 'updated' : 'created' => '1']));
            }

            $form['values'] = $result['values'];
            return $this->render($form, $result['errors']);
        }

        return $this->render($form);
    }

    /**
     * @param array<string, mixed> $form
     * @return array<string, mixed>
     */
    private function withAdminContext(array $form, string $icno): array
    {
        $form['backUrl'] = '/biodata/pasport-permit/adminview?icno=' . rawurlencode($icno);
        $form['action'] = Support::appendQuery((string) ($form['action'] ?? ''), ['icno' => $icno]);

        return $form;
    }

    /**
     * @param array<string, mixed> $form
     * @param array<string, string> $errors
     */
    private function render(array $form, array $errors = []): ResponseInterface
    {
        return $this->viewRenderer->render(__DIR__ . '/form', [
            'form' => $form,
            'errors' => $errors,
        ]);
    }
}
