<?php

declare(strict_types=1);

namespace App\Web\RecordAction;

use App\Shared\LegacySessionReader;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class Action
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->sessionReader->read($request) === null) {
            return $this->redirect('/login');
        }

        $path = $request->getUri()->getPath();
        $query = $request->getQueryParams();

        return $this->viewRenderer->render(__DIR__ . '/template', [
            'page' => $this->page($path),
            'id' => is_scalar($query['id'] ?? null) ? (string) $query['id'] : '',
        ]);
    }

    /**
     * @return array{title: string, module: string, mode: string, backUrl: string}
     */
    private function page(string $path): array
    {
        $module = $this->moduleTitle($path);
        $mode = $this->mode($path);

        return [
            'title' => $mode . ' ' . $module,
            'module' => $module,
            'mode' => $mode,
            'backUrl' => $this->backUrl($path),
        ];
    }

    private function moduleTitle(string $path): string
    {
        return match (true) {
            str_starts_with($path, '/biodata/') => 'Biodata',
            str_starts_with($path, '/gambar-profile/') => 'Gambar Profil',
            str_starts_with($path, '/pendidikan/') => 'Pendidikan',
            str_starts_with($path, '/pasport-permit/') && str_contains($path, 'permitkerja') => 'Permit Kerja',
            str_starts_with($path, '/pasport-permit/') => 'Paspot',
            str_starts_with($path, '/akaun/') => 'Akaun',
            str_starts_with($path, '/keluarga/') => 'Keluarga',
            str_starts_with($path, '/bidangkepakaran/') => 'Bidang Kepakaran',
            str_starts_with($path, '/vaksinasi/') => 'Vaksinasi',
            str_starts_with($path, '/kelayakan-profesional/') => 'Kelayakan Profesional',
            str_starts_with($path, '/bahasa/') => 'Bahasa',
            str_starts_with($path, '/anugerah/') => 'Anugerah',
            str_starts_with($path, '/pengalamankerja/') => 'Pengalaman Kerja',
            str_starts_with($path, '/sejarahperubatan/') => 'Sejarah Perubatan',
            str_starts_with($path, '/badan-profesional/') => 'Keahlian',
            default => 'Rekod',
        };
    }

    private function mode(string $path): string
    {
        return match (true) {
            str_contains($path, 'update') || str_contains($path, 'kemaskini') || str_contains($path, 'penukaran') || str_contains($path, 'vaksinasi') => 'Kemaskini',
            str_contains($path, 'lihat') || str_contains($path, '/view-') => 'Lihat',
            str_contains($path, 'delete') || str_contains($path, 'padam') => 'Padam',
            str_contains($path, 'tambah') || str_contains($path, 'add') || str_contains($path, 'ums-nonums') => 'Tambah',
            default => 'Urus',
        };
    }

    private function backUrl(string $path): string
    {
        return match (true) {
            str_starts_with($path, '/biodata/') => '/biodata/lihatbiodata',
            str_starts_with($path, '/gambar-profile/') => '/biodata/userview',
            str_starts_with($path, '/pendidikan/') => '/pendidikan/view',
            str_starts_with($path, '/pasport-permit/') => '/pasport-permit/view',
            str_starts_with($path, '/akaun/') => '/akaun/senarai-akaun',
            str_starts_with($path, '/keluarga/') => '/keluarga/view',
            str_starts_with($path, '/bidangkepakaran/') => '/bidangkepakaran/view',
            str_starts_with($path, '/vaksinasi/') => '/vaksinasi/index',
            str_starts_with($path, '/kelayakan-profesional/') => '/kelayakan-profesional/view',
            str_starts_with($path, '/bahasa/') => '/bahasa/view',
            str_starts_with($path, '/anugerah/') => '/anugerah/view',
            str_starts_with($path, '/pengalamankerja/') => '/pengalamankerja/view',
            str_starts_with($path, '/sejarahperubatan/') => '/sejarahperubatan/view',
            str_starts_with($path, '/badan-profesional/') => '/badan-profesional/view',
            default => '/biodata/userview',
        };
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
