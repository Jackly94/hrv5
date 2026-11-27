<?php

declare(strict_types=1);

namespace App\Web\PengesahanDataStaf\AkaunBank;

use App\Shared\AuthorizationService;
use App\Shared\EmployeeRepository;
use App\Shared\LegacySessionReader;
use App\Shared\PengesahanDataStaf\AkaunBankVerificationService;
use App\Web\PengesahanDataStaf\ActionSupport;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class VerifyAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private EmployeeRepository $employees,
        private AuthorizationService $authorization,
        private AkaunBankVerificationService $service,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $access = ActionSupport::requireControllerAccess(
            $request,
            $this->responseFactory,
            $this->sessionReader,
            $this->employees,
            $this->authorization,
            'acc-bank-verify',
        );
        if ($access['response'] instanceof ResponseInterface) {
            return $access['response'];
        }

        $id = ActionSupport::id($request);
        $detail = $this->service->detail($id, 'verification', true);
        if ($detail === null) {
            return ActionSupport::redirect($this->responseFactory, '/pengesahan-data-staf/akaun-bank/verification?error=1');
        }

        $errors = [];
        $remarks = '';
        if (strtoupper($request->getMethod()) === 'POST') {
            $body = ActionSupport::body($request);
            $remarks = is_scalar($body['remarks'] ?? null) ? trim((string) $body['remarks']) : '';
            $action = isset($body['accept']) ? 'accept' : (isset($body['reject']) ? 'reject' : '');
            $result = $this->service->verify($id, $action, $remarks, $access['icno']);
            if ($result['success'] === true) {
                return ActionSupport::redirect($this->responseFactory, '/pengesahan-data-staf/akaun-bank/verification?updated=1');
            }

            $errors = $result['errors'];
        }

        return $this->viewRenderer->render(__DIR__ . '/detail', [
            'detail' => $detail,
            'errors' => $errors,
            'remarks' => $remarks,
        ]);
    }
}
