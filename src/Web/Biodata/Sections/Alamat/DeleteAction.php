<?php

declare(strict_types=1);

namespace App\Web\Biodata\Sections\Alamat;

use App\Shared\Biodata\Sections\AlamatCrudService;
use App\Shared\BiodataProfileService;
use App\Shared\LegacySessionReader;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class DeleteAction
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private BiodataProfileService $profileService,
        private AlamatCrudService $alamatCrudService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->redirect('/login');
        }

        $query = $request->getQueryParams();
        $id = (string) ($query['id'] ?? '');
        $targetIcno = $this->cleanQueryValue((string) ($query['icno'] ?? ''));
        $icno = $targetIcno !== ''
            ? $targetIcno
            : $this->profileService->icnoFromIdentity($legacySession['identityId']);
        if ($icno !== '' && $id !== '') {
            $this->alamatCrudService->delete($icno, $id, $this->clientIp($request));
        }

        $backUrl = $targetIcno !== '' ? '/biodata/alamat/adminview?icno=' . rawurlencode($targetIcno) : '/biodata/alamat/view';

        return $this->redirect($backUrl . (str_contains($backUrl, '?') ? '&' : '?') . 'deleted=1');
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

    private function cleanQueryValue(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return trim(strtok($value, '?&') ?: $value);
    }
}
