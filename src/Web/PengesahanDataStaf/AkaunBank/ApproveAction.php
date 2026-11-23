<?php

declare(strict_types=1);

namespace App\Web\PengesahanDataStaf\AkaunBank;

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
        private AkaunBankVerificationService $service,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return ActionSupport::redirect($this->responseFactory, '/login');
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
            $actorIcno = $this->employees->icnoFromIdentity($legacySession['identityId']);
            $result = $this->service->approve($id, $action, $remarks, $actorIcno);
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
