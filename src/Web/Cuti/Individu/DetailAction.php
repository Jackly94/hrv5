<?php

declare(strict_types=1);

namespace App\Web\Cuti\Individu;

use App\Shared\CutiRecordService;
use App\Shared\EmployeeRepository;
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
        private EmployeeRepository $employees,
        private CutiRecordService $records,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->redirect('/login');
        }

        $query = $request->getQueryParams();
        $id = isset($query['id']) ? (int) $query['id'] : 0;
        $icno = $this->employees->icnoFromIdentity($legacySession['identityId']);
        $record = $id > 0 ? $this->records->recordForStaff($icno, $id) : null;

        return $this->viewRenderer
            ->render(__DIR__ . '/detail', ['record' => $record])
            ->withStatus($record === null ? 404 : 200);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
