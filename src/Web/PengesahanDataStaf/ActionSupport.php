<?php

declare(strict_types=1);

namespace App\Web\PengesahanDataStaf;

use App\Shared\AuthorizationService;
use App\Shared\EmployeeRepository;
use App\Shared\LegacySessionReader;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ActionSupport
{
    private const CONTROLLER_ID = 'pengesahan-data-staf';

    public static function redirect(ResponseFactoryInterface $responseFactory, string $url): ResponseInterface
    {
        return $responseFactory->createResponse(302)->withHeader('Location', $url);
    }

    /**
     * @return array{response: ResponseInterface|null, icno: string}
     */
    public static function requireControllerAccess(
        ServerRequestInterface $request,
        ResponseFactoryInterface $responseFactory,
        LegacySessionReader $sessionReader,
        EmployeeRepository $employees,
        AuthorizationService $authorization,
        string $actionId,
    ): array {
        $legacySession = $sessionReader->read($request);
        if ($legacySession === null) {
            return ['response' => self::redirect($responseFactory, '/login'), 'icno' => ''];
        }

        $icno = $employees->icnoFromIdentity($legacySession['identityId']);
        if (!$authorization->canAccessControllerAction($icno, self::CONTROLLER_ID, $actionId)) {
            return ['response' => $responseFactory->createResponse(403), 'icno' => $icno];
        }

        return ['response' => null, 'icno' => $icno];
    }

    public static function search(ServerRequestInterface $request): string
    {
        $query = $request->getQueryParams();
        $value = $query['icno'] ?? $query['q'] ?? '';

        return is_scalar($value) ? trim((string) $value) : '';
    }

    public static function id(ServerRequestInterface $request): string
    {
        $query = $request->getQueryParams();
        $value = $query['id'] ?? '';

        return is_scalar($value) ? trim((string) $value) : '';
    }

    public static function page(ServerRequestInterface $request, string $name): int
    {
        $query = $request->getQueryParams();
        $value = $query[$name] ?? 1;
        if (!is_scalar($value)) {
            return 1;
        }

        return max(1, (int) $value);
    }

    /**
     * @return array<string, mixed>
     */
    public static function body(ServerRequestInterface $request): array
    {
        $body = $request->getParsedBody();

        return is_array($body) ? $body : [];
    }

    /**
     * @param array<string, string> $query
     */
    public static function appendQuery(string $url, array $query): string
    {
        $queryString = http_build_query($query);
        if ($url === '' || $queryString === '') {
            return $url;
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . $queryString;
    }
}
