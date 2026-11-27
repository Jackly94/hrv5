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

final readonly class ApproveAction
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
            'acc-bank-approve',
        );
        if ($access['response'] instanceof ResponseInterface) {
            return $access['response'];
        }

        $id = ActionSupport::id($request);
        $detail = $this->service->detail($id, 'approval', true);
        if ($detail === null) {
            return ActionSupport::redirect($this->responseFactory, '/pengesahan-data-staf/akaun-bank/approval?error=1');
        }

        $errors = [];
        $remarks = '';
        if (strtoupper($request->getMethod()) === 'POST') {
            $body = ActionSupport::body($request);
            $remarks = is_scalar($body['remarks'] ?? null) ? trim((string) $body['remarks']) : '';
            $action = isset($body['approve']) ? 'approve' : (isset($body['reject']) ? 'reject' : '');
            $result = $this->service->approve($id, $action, $remarks, $access['icno']);
            if ($result['success'] === true) {
                return ActionSupport::redirect($this->responseFactory, '/pengesahan-data-staf/akaun-bank/approval?updated=1');
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
