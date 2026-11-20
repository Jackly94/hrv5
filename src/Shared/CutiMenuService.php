<?php

declare(strict_types=1);

namespace App\Shared;

final readonly class CutiMenuService
{
    private const LOCAL_ROUTES = [
        'cuti/individu/index' => '/cuti/individu/index',
        'cuti/individu/pilih' => '/cuti/individu/pilih',
        'cuti/individu/ganti' => '/cuti/individu/ganti',
        'cuti/individu/statement' => '/cuti/individu/statement',
        'cuti/individu/entitlement' => '/cuti/individu/entitlement',
        'cuti/individu/list' => '/cuti/individu/list',
        'cuti/individu/substitute-list' => '/cuti/individu/substitute-list',
        'cuti/individu/gcr-list' => '/cuti/individu/gcr-list',
        'cuti/individu/apply-gcr' => '/cuti/individu/apply-gcr',
        'cuti/individu/ck' => '/cuti/individu/ck',
        'cuti/individu/cb' => '/cuti/individu/cb',
        'cuti/individu/cr1' => '/cuti/individu/cr1',
        'cuti/individu/cr1-aca' => '/cuti/individu/cr1-aca',
        'cuti/individu/cr2' => '/cuti/individu/cr2',
        'cuti/individu/ctr' => '/cuti/individu/ctr',
        'cuti/individu/cs1' => '/cuti/individu/cs1',
        'cuti/individu/cs2' => '/cuti/individu/cs2',
        'cuti/individu/hso' => '/cuti/individu/hso',
        'cuti/pegawai/senarai-peraku' => '/cuti/pegawai/senarai-peraku',
        'cuti/pegawai/senarai-lulus' => '/cuti/pegawai/senarai-lulus',
        'cuti/pegawai/gcr-list-checked' => '/cuti/pegawai/gcr-list-checked',
        'cuti/pegawai/list' => '/cuti/pegawai/list',
        'cuti/pegawai/staff-leave' => '/cuti/pegawai/staff-leave',
        'cuti/pegawai/sort' => '/cuti/pegawai/sort',
        'cuti/pegawai/bod-list' => '/cuti/pegawai/bod-list',
        'cuti/supervisor/sick-leave-list' => '/cuti/supervisor/sick-leave-list',
        'cuti/supervisor/supervised-staff' => '/cuti/supervisor/supervised-staff',
        'cuti/supervisor/leave-statement-report' => '/cuti/supervisor/leave-statement-report',
        'cuti/supervisor/leave-monitoring' => '/cuti/supervisor/leave-monitoring',
        'cuti/supervisor/calender' => '/cuti/supervisor/calender',
        'cuti/supervisor/gcr-list' => '/cuti/supervisor/gcr-list',
        'cuti/supervisor/gcr-list-checked' => '/cuti/supervisor/gcr-list-checked',
        'cuti/observer/my-staff' => '/cuti/observer/my-staff',
        'cuti/admin/cr2-list' => '/cuti/admin/cr2-list',
        'cuti/admin/behalf-list' => '/cuti/admin/behalf-list',
        'cuti/admin/access' => '/cuti/admin/access',
        'cuti/admin/gcr-application-list' => '/cuti/admin/gcr-application-list',
        'cuti/admin/open-application' => '/cuti/admin/open-application',
        'cuti/admin/ph-list' => '/cuti/admin/ph-list',
        'cuti/admin/cb-list' => '/cuti/admin/cb-list',
        'cuti/admin/admin-index' => '/cuti/admin/admin-index',
        'cuti/admin/staff-list' => '/cuti/admin/staff-list',
        'cuti/admin/report' => '/cuti/admin/report',
        'cuti/admin/report-leave-list' => '/cuti/admin/report-leave-list',
        'cuti/pegawai/report' => '/cuti/pegawai/report',
        'manage/list' => '/manage/list',
    ];

    public function __construct(
        private CutiAccessService $access,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function sectionsForStaff(string $icno): array
    {
        $flags = $this->access->flags($icno);
        $sections = [
            [
                'label' => 'Staff Menu',
                'icon' => 'user',
                'visible' => true,
                'items' => [
                    ['label' => 'Apply Leave', 'route' => 'cuti/individu/pilih', 'icon' => 'plane'],
                    ['label' => 'Substitute', 'route' => 'cuti/individu/ganti', 'icon' => 'users'],
                    ['label' => 'GCR & CBTH Application', 'route' => 'cuti/individu/apply-gcr', 'icon' => 'list', 'visible' => $flags['gcrVisible']],
                    ['label' => 'Leave Statement', 'route' => 'cuti/individu/statement', 'icon' => 'file-text'],
                    ['label' => 'Leave Entitlement', 'route' => 'cuti/individu/entitlement', 'icon' => 'badge-check'],
                    ['label' => 'Application List', 'route' => 'cuti/individu/list', 'icon' => 'list'],
                    ['label' => 'Substitute List', 'route' => 'cuti/individu/substitute-list', 'icon' => 'list'],
                    ['label' => 'My GCR & CBTH Application List', 'route' => 'cuti/individu/gcr-list', 'icon' => 'list'],
                ],
            ],
            [
                'label' => 'Officer Menu',
                'icon' => 'users',
                'visible' => $flags['officer'],
                'items' => [
                    ['label' => 'Pending Verification List', 'route' => 'cuti/pegawai/senarai-peraku', 'icon' => 'square-pen'],
                    ['label' => 'Pending Approval List', 'route' => 'cuti/pegawai/senarai-lulus', 'icon' => 'square-pen'],
                    ['label' => 'GCR & CBTH Application List', 'route' => 'cuti/pegawai/gcr-list-checked', 'icon' => 'list'],
                    ['label' => 'Approved Leave List', 'route' => 'cuti/pegawai/list', 'icon' => 'list'],
                    ['label' => 'Staff Under My Supervision', 'route' => 'cuti/pegawai/staff-leave', 'icon' => 'users'],
                    ['label' => 'GCR & CBTH Application List (Ketua Jabatan BSM)', 'route' => 'cuti/pegawai/sort', 'icon' => 'list', 'visible' => $flags['department230Chief']],
                    ['label' => 'Back On Duty', 'route' => 'cuti/pegawai/bod-list', 'icon' => 'calendar-check'],
                ],
            ],
            [
                'label' => 'Supervisor Menu',
                'icon' => 'star',
                'visible' => $flags['supervisor'],
                'items' => [
                    ['label' => 'Sick Leave List', 'route' => 'cuti/supervisor/sick-leave-list', 'icon' => 'plane'],
                    ['label' => 'Supervised Staff', 'route' => 'cuti/supervisor/supervised-staff', 'icon' => 'users'],
                    ['label' => 'Monthly Statement', 'route' => 'cuti/supervisor/leave-statement-report', 'icon' => 'file-text'],
                    ['label' => 'Monitor Staff Leave', 'route' => 'cuti/supervisor/leave-monitoring', 'icon' => 'monitor'],
                    ['label' => 'Leave Calendar', 'route' => 'cuti/supervisor/calender', 'icon' => 'calendar'],
                    ['label' => 'GCR & CBTH Application List', 'route' => 'cuti/supervisor/gcr-list', 'icon' => 'list'],
                    ['label' => 'Reset GCR & CBTH Application', 'route' => 'cuti/supervisor/gcr-list-checked', 'icon' => 'list'],
                    ['label' => 'Supervisor List', 'route' => 'cuti/admin/access', 'icon' => 'list'],
                ],
            ],
            [
                'label' => 'Admin Menu',
                'icon' => 'shield',
                'visible' => $flags['admin'],
                'items' => [
                    ['label' => 'Verify', 'route' => 'cuti/admin/cr2-list', 'icon' => 'plane'],
                    ['label' => 'On Behalf Of', 'route' => 'cuti/admin/behalf-list', 'icon' => 'users'],
                    ['label' => 'Access', 'route' => 'cuti/admin/access', 'icon' => 'file-text'],
                    ['label' => 'GCR Application List', 'route' => 'cuti/admin/gcr-application-list', 'icon' => 'badge-check'],
                    ['label' => 'Buka Permohonan GCR & CBTH', 'route' => 'cuti/admin/open-application', 'icon' => 'list'],
                    ['label' => 'Public Holiday Calendar', 'route' => 'cuti/admin/ph-list', 'icon' => 'calendar'],
                    ['label' => 'Maternity Leave', 'route' => 'cuti/admin/cb-list', 'icon' => 'calendar'],
                    ['label' => 'Add Apply GCR After Closed Date', 'route' => 'cuti/admin/admin-index', 'icon' => 'list'],
                    ['label' => 'Ketidakhadiran (Absent)', 'route' => 'cuti/admin/staff-list', 'icon' => 'list'],
                ],
            ],
            [
                'label' => 'Report',
                'icon' => 'bar-chart-3',
                'visible' => $flags['admin'],
                'items' => [
                    ['label' => 'Report by Department (Date Range)', 'route' => 'cuti/admin/report', 'icon' => 'list'],
                    ['label' => 'Statistic by Leave Type', 'route' => 'cuti/admin/report-leave-list', 'icon' => 'list'],
                ],
            ],
            [
                'label' => 'Sector HR',
                'icon' => 'file-cog',
                'visible' => $flags['sectorHr'],
                'items' => [
                    ['label' => 'Manage(Surat)', 'route' => 'manage/list', 'icon' => 'file-text'],
                ],
            ],
            [
                'label' => 'Pentadbiran',
                'icon' => 'briefcase',
                'visible' => $flags['departmentChiefOrPp'],
                'items' => [
                    ['label' => 'Senarai Permohonan HSO', 'route' => 'cuti/pegawai/report', 'icon' => 'list'],
                ],
            ],
            [
                'label' => 'Pemantau',
                'icon' => 'eye',
                'visible' => $flags['any'],
                'items' => [
                    ['label' => 'Senarai Kakitangan Seliaan', 'route' => 'cuti/observer/my-staff', 'icon' => 'list'],
                ],
            ],
        ];

        return $this->normalizeSections($sections);
    }

    public static function normalizeRoute(string $route): string
    {
        $route = trim($route);
        if ($route === '') {
            return '/cuti/individu/index';
        }

        if (str_starts_with($route, 'https://') || str_starts_with($route, 'http://')) {
            return $route;
        }

        $route = ltrim($route, '/');

        return self::LOCAL_ROUTES[$route] ?? LegacyUrl::to($route);
    }

    /**
     * @param list<array<string, mixed>> $sections
     * @return list<array<string, mixed>>
     */
    private function normalizeSections(array $sections): array
    {
        $normalized = [];
        foreach ($sections as $section) {
            if (($section['visible'] ?? true) !== true) {
                continue;
            }

            $items = [];
            foreach ($section['items'] as $item) {
                if (($item['visible'] ?? true) !== true) {
                    continue;
                }
                $url = self::normalizeRoute((string) $item['route']);
                $items[] = [
                    'label' => (string) $item['label'],
                    'route' => (string) $item['route'],
                    'url' => $url,
                    'icon' => (string) ($item['icon'] ?? 'circle'),
                    'external' => str_starts_with($url, 'http://') || str_starts_with($url, 'https://'),
                    'legacy' => LegacyUrl::isLegacy($url),
                    'badge' => 0,
                ];
            }

            if ($items === []) {
                continue;
            }

            $normalized[] = [
                'label' => (string) $section['label'],
                'icon' => (string) ($section['icon'] ?? 'folder'),
                'items' => $items,
            ];
        }

        return $normalized;
    }
}
