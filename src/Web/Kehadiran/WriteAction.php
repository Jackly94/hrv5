<?php

declare(strict_types=1);

namespace App\Web\Kehadiran;

use App\Shared\AttendanceService;
use App\Shared\LegacySessionReader;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class WriteAction
{
    private const PAGES = [
        'pengesahan' => ['kind' => 'reason', 'title' => 'Pengesahan Ketidakpatuhan', 'back' => '/kehadiran/senarai_tindakan'],
        'wfh-lulus-tindakan' => ['kind' => 'wfh-approval', 'title' => 'Kelulusan WFH', 'back' => '/kehadiran/wfh-lulus'],
        'delete-wfh' => ['kind' => 'wfh-delete', 'title' => 'Padam Permohonan WFH', 'back' => '/kehadiran/pantau-permohonan-wfh'],
        'verify-thtc-sv' => ['kind' => 'thtc-verify', 'title' => 'Semakan THTC', 'back' => '/kehadiran/absent-list-sv'],
        'approve-thtc' => ['kind' => 'thtc-approve', 'title' => 'Perakuan THTC', 'back' => '/kehadiran/list-kj'],
    ];

    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private AttendanceService $attendanceService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $slug = basename($request->getUri()->getPath());
        if (!isset(self::PAGES[$slug])) {
            return $this->responseFactory->createResponse(404);
        }

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
        $id = is_numeric($query['id'] ?? null) ? (int) $query['id'] : 0;
        if ($id < 1) {
            return $this->responseFactory->createResponse(404);
        }

        $page = self::PAGES[$slug];
        $icno = (string) $attendance['icno'];
        $parsedBody = $request->getParsedBody();
        $body = is_array($parsedBody) ? $parsedBody : [];
        $redirect = $this->safeRedirect((string) (($body['redirect'] ?? null) ?: ($query['redirect'] ?? '') ?: $page['back']), (string) $page['back']);

        if (strtoupper($request->getMethod()) === 'POST') {
            $result = $this->save((string) $page['kind'], $icno, $id, $body);
            $separator = str_contains($redirect, '?') ? '&' : '?';

            return $this->redirect($redirect . $separator . ($result['success'] ? 'updated=1' : 'error=1'));
        }

        $record = $this->record((string) $page['kind'], $icno, $id);
        if ($record === null) {
            return $this->responseFactory->createResponse(404);
        }

        return $this->viewRenderer->render(__DIR__ . '/write-page', [
            'attendance' => $attendance,
            'identityId' => $legacySession['identityId'],
            'page' => [
                'slug' => $slug,
                'kind' => (string) $page['kind'],
                'title' => (string) $page['title'],
                'back' => $redirect,
            ],
            'record' => $record,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function record(string $kind, string $icno, int $id): ?array
    {
        return match ($kind) {
            'reason' => $this->attendanceService->attendanceReasonApproval($icno, $id),
            'wfh-approval' => $this->attendanceService->wfhApproval($icno, $id),
            'wfh-delete' => $this->attendanceService->wfhDeleteRecord($icno, $id),
            'thtc-verify' => $this->attendanceService->thtcReview($icno, $id, 'verify'),
            'thtc-approve' => $this->attendanceService->thtcReview($icno, $id, 'approve'),
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $body
     * @return array{success: bool, message: string}
     */
    private function save(string $kind, string $icno, int $id, array $body): array
    {
        return match ($kind) {
            'reason' => $this->attendanceService->saveAttendanceReasonApproval($icno, $id, $body),
            'wfh-approval' => $this->attendanceService->saveWfhApproval($icno, $id, $body),
            'wfh-delete' => $this->attendanceService->deleteWfh($icno, $id),
            'thtc-verify' => $this->attendanceService->saveThtcReview($icno, $id, 'verify', $body),
            'thtc-approve' => $this->attendanceService->saveThtcReview($icno, $id, 'approve', $body),
            default => ['success' => false, 'message' => 'Tindakan tidak disokong.'],
        };
    }

    private function safeRedirect(string $url, string $fallback): string
    {
        $url = trim($url);
        if ($url !== '' && str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return $url;
        }

        return $fallback;
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
