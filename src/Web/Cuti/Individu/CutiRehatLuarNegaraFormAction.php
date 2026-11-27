<?php

declare(strict_types=1);

namespace App\Web\Cuti\Individu;

use App\Shared\CutiApplicationService;
use App\Shared\CutiMenuService;
use App\Shared\EmployeeRepository;
use App\Shared\LegacySessionReader;
use Psr\Http\Message\ResponseFactoryInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class CutiRehatLuarNegaraFormAction extends LeaveFormAction
{
    public function __construct(
        WebViewRenderer $viewRenderer,
        ResponseFactoryInterface $responseFactory,
        LegacySessionReader $sessionReader,
        EmployeeRepository $employees,
        CutiMenuService $menu,
        CutiApplicationService $applications,
    ) {
        parent::__construct('cr2', 'cuti-rehat-luar-negara-form', $viewRenderer, $responseFactory, $sessionReader, $employees, $menu, $applications);
    }
}
