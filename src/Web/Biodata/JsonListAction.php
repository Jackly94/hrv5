<?php

declare(strict_types=1);

namespace App\Web\Biodata;

use App\Shared\BiodataProfileService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class JsonListAction
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private BiodataProfileService $profileService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $body = (array) ($request->getParsedBody() ?? []);
        $query = $request->getQueryParams();
        $parents = $body['depdrop_parents'] ?? $query['depdrop_parents'] ?? [];

        if (is_string($parents)) {
            $parents = [$parents];
        }

        $parent = is_array($parents) && isset($parents[0]) ? (string) $parents[0] : '';
        $output = [];

        if ($parent !== '' && str_ends_with($path, '/citylist')) {
            $output = $this->profileService->statesForCountry($parent);
        }

        if ($parent !== '' && str_ends_with($path, '/peringkatcapaiankedua')) {
            $output = $this->profileService->secondAccessLevels($parent);
        }

        $response = $this->responseFactory
            ->createResponse()
            ->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(json_encode(['output' => $output, 'selected' => ''], JSON_THROW_ON_ERROR));

        return $response;
    }
}
