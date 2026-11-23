<?php

declare(strict_types=1);

namespace App\Web\Biodata\Sections\SejarahPerubatan;

use App\Shared\BiodataProfileService;
use App\Shared\LegacySessionReader;
use App\Shared\Biodata\Sections\SejarahPerubatanCrudService;
use App\Web\Biodata\Sections\StaffRecordSectionActionSupport as Support;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class FormAction
{
    private const KEY = 'sejarahperubatan';

    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private BiodataProfileService $profileService,
        private SejarahPerubatanCrudService $sejarahPerubatanCrudService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return Support::redirect($this->responseFactory, '/login');
        }

        $path = $request->getUri()->getPath();
        $query = $request->getQueryParams();
        $isUpdate = str_ends_with($path, '/update');
        $id = $isUpdate ? (string) ($query['id'] ?? '') : null;
        $targetIcno = Support::cleanQueryValue((string) ($query['icno'] ?? ''));
        $icno = $targetIcno !== ''
            ? $targetIcno
            : $this->profileService->icnoFromIdentity($legacySession['identityId']);
        $form = $icno === '' ? null : $this->sejarahPerubatanCrudService->form($icno, $id);
        if ($form === null) {
            return $this->render(null, [], ['general' => 'Rekod tidak dijumpai.'], $isUpdate ? 'update' : 'create', 404);
        }
        if ($targetIcno !== '') {
            $form['backUrl'] = '/biodata/' . self::KEY . '/adminview?icno=' . rawurlencode($targetIcno);
            $form['action'] = Support::appendQuery((string) $form['action'], ['icno' => $targetIcno]);
        }

        if (strtoupper($request->getMethod()) === 'POST') {
            $body = $request->getParsedBody();
            $body = is_array($body) ? $body : [];
            $result = $this->sejarahPerubatanCrudService->save($icno, $body, $id, Support::clientIp($request));
            if ($result['success'] === true) {
                return Support::redirect($this->responseFactory, Support::appendQuery((string) $form['backUrl'], [$isUpdate ? 'updated' : 'created' => '1']));
            }

            $form['values'] = $result['values'];

            return $this->render($form, $result['values'], $result['errors'], $isUpdate ? 'update' : 'create');
        }

        return $this->render($form, $form['values'], [], $isUpdate ? 'update' : 'create');
    }

    /**
     * @param array<string, mixed>|null $form
     * @param array<string, string> $values
     * @param array<string, string> $errors
     */
    private function render(?array $form, array $values, array $errors, string $mode, int $status = 200): ResponseInterface
    {
        return $this->viewRenderer
            ->render(__DIR__ . '/form', [
                'key' => self::KEY,
                'mode' => $mode,
                'form' => $form,
                'values' => $values,
                'errors' => $errors,
            ])
            ->withStatus($status);
    }
}
