<?php

declare(strict_types=1);

namespace App\Web\Kehadiran;

use App\Shared\AttendanceService;
use App\Shared\LegacySessionReader;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class WbbListAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private AttendanceService $attendanceService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->redirect('/login');
        }

        $serverParams = $request->getServerParams();
        $attendance = $this->attendanceService->landingForIdentity(
            $legacySession['identityId'],
            is_string($serverParams['REMOTE_ADDR'] ?? null) ? $serverParams['REMOTE_ADDR'] : null,
        );
        if ($attendance === null) {
            return $this->responseFactory->createResponse(403);
        }

        $query = $request->getQueryParams();
        $staffIcno = $this->stringQuery($query, 'id');
        if ($staffIcno === '') {
            return $this->responseFactory->createResponse(404);
        }

        $wpId = $this->positiveIntQuery($query, 'wp_id');
        $message = '';
        $messageType = '';

        if (strtoupper($request->getMethod()) === 'POST') {
            $parsedBody = $request->getParsedBody();
            $body = is_array($parsedBody) ? $parsedBody : [];
            $result = $this->attendanceService->saveSupervisorWbb((string) $attendance['icno'], $staffIcno, $body, $wpId);

            $params = ['id' => $staffIcno];
            $params[$result['success'] ? 'updated' : 'error'] = $result['message'];

            return $this->redirect('/kehadiran/wbb-list?' . http_build_query($params));
        }

        $data = $this->attendanceService->supervisorWbbList((string) $attendance['icno'], $staffIcno, $wpId);
        if ($data === null) {
            return $this->responseFactory->createResponse(404);
        }

        if (is_string($query['updated'] ?? null)) {
            $message = (string) $query['updated'];
            $messageType = 'success';
        } elseif (is_string($query['error'] ?? null)) {
            $message = (string) $query['error'];
            $messageType = 'error';
        }

        return $this->viewRenderer->render(__DIR__ . '/wbb-list', [
            'attendance' => $attendance,
            'identityId' => $legacySession['identityId'],
            'page' => ['title' => 'Senarai WBB Kakitangan'],
            'data' => $data,
            'staffIcno' => $staffIcno,
            'wpId' => $wpId,
            'message' => $message,
            'messageType' => $messageType,
        ]);
    }

    /**
     * @param array<array-key, mixed> $query
     */
    private function stringQuery(array $query, string $key): string
    {
        $value = $query[$key] ?? '';

        return is_string($value) ? trim($value) : '';
    }

    /**
     * @param array<array-key, mixed> $query
     */
    private function positiveIntQuery(array $query, string $key): ?int
    {
        $value = $query[$key] ?? null;

        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
