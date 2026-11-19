<?php

declare(strict_types=1);

namespace App\Web\StaffRecord;

use App\Shared\BiodataProfileService;
use App\Shared\LegacySessionReader;
use App\Shared\StaffRecordSectionCrudService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class DeleteAction
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private BiodataProfileService $profileService,
        private StaffRecordSectionCrudService $crudService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->redirect('/login');
        }

        $path = $request->getUri()->getPath();
        $key = $this->crudService->keyFromPath($path);
        if ($key === null) {
            return $this->responseFactory->createResponse(404);
        }

        $query = $request->getQueryParams();
        $id = (string) ($query['id'] ?? '');
        $icno = $this->profileService->icnoFromIdentity($legacySession['identityId']);
        if ($id !== '' && $icno !== '') {
            $this->crudService->delete($key, $icno, $id, $this->clientIp($request));
        }

        return $this->redirect($this->crudService->backUrl($key) . '?deleted=1');
    }

    private function clientIp(ServerRequestInterface $request): string
    {
        $server = $request->getServerParams();
        foreach (['HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            $value = $server[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return trim(explode(',', $value)[0]);
            }
        }

        return 'UNKNOWN';
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
