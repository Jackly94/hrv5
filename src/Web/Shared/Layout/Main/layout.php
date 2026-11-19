<?php

declare(strict_types=1);

use App\Shared\LegacyUrl;
use App\Web\Shared\Layout\Main\MainAsset;
use Yiisoft\Html\Html;

/**
 * @var \App\Shared\ApplicationParams $applicationParams
 * @var Yiisoft\Aliases\Aliases $aliases
 * @var Yiisoft\Assets\AssetManager $assetManager
 * @var string $content
 * @var Yiisoft\View\WebView $this
 */

$assetManager->register(MainAsset::class);

$this->addCssFiles($assetManager->getCssFiles());
$this->addCssStrings($assetManager->getCssStrings());
$this->addJsFiles($assetManager->getJsFiles());
$this->addJsStrings($assetManager->getJsStrings());
$this->addJsVars($assetManager->getJsVars());

$this->beginPage()
?>
<!DOCTYPE html>
<html lang="<?= Html::encode($applicationParams->locale) ?>">
<head>
    <meta charset="<?= Html::encode($applicationParams->charset) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="<?= $aliases->get('@baseUrl/favicon.svg') ?>" type="image/svg+xml">
    <title><?= Html::encode($this->getTitle()) ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<div class="app-toast-region" data-app-toast-region aria-live="polite" aria-atomic="true"></div>

<div class="app-shell">
    <header class="app-header">
        <button class="sidebar-toggle" type="button" aria-label="Toggle navigation" data-sidebar-toggle>
            <span></span>
            <span></span>
            <span></span>
        </button>
        <a class="brand header-brand" href="/dashboard">
            <img class="brand-logo" src="/images/hronline_logo.png" alt="HROnline">
            <span>
                <strong>HROnline</strong>
                <small>Yii3 migration</small>
            </span>
        </a>
        <nav class="app-nav" aria-label="Primary">
            <a href="/dashboard">Dashboard</a>
            <a href="/external-login">External</a>
        </nav>
    </header>

    <aside class="app-sidebar" data-sidebar>
        <a class="sidebar-brand" href="/dashboard">
            <img class="sidebar-brand-logo" src="/images/hronline_logo.png" alt="HROnline">
            <span><b>HR</b>Online</span>
        </a>
        <nav class="sidebar-nav" aria-label="Workspace">
            <a href="/dashboard">
                <span class="nav-icon">MW</span>
                <span>MyWorkspace</span>
            </a>
            <a href="/menu">
                <span class="nav-icon">MN</span>
                <span>Menu</span>
            </a>
            <a href="/kehadiran/index">
                <span class="nav-icon">ST</span>
                <span>STARS</span>
            </a>
            <a href="<?= Html::encode(LegacyUrl::to('keselamatan/index')) ?>">
                <span class="nav-icon">SK</span>
                <span>SKB</span>
            </a>
            <a href="/biodata/userview">
                <span class="nav-icon">PF</span>
                <span>Profil Staf</span>
            </a>
            <a href="https://docs.ums.edu.my/" target="_blank" rel="noopener">
                <span class="nav-icon">UD</span>
                <span>UMS Docs</span>
            </a>
            <a href="<?= Html::encode(LegacyUrl::to('support-system/index')) ?>">
                <span class="nav-icon">SS</span>
                <span>Support System</span>
            </a>
        </nav>
    </aside>

    <div class="app-body">
        <main class="content">
            <div class="content_i">
                <?= $content ?>
            </div>
        </main>
    </div>

    <footer class="footer">
        <span>&copy; <?= date('Y') ?> <?= Html::encode($applicationParams->name) ?></span>
    </footer>
</div>

<script>
document.querySelector('[data-sidebar-toggle]')?.addEventListener('click', () => {
    document.body.classList.toggle('sidebar-open');
});

const currentPath = window.location.pathname.replace(/\/+$/, '') || '/';
document.querySelectorAll('.sidebar-nav a').forEach((link) => {
    const href = link.getAttribute('href') || '';
    if (href.startsWith('http')) {
        return;
    }

    const linkPath = new URL(href, window.location.origin).pathname.replace(/\/+$/, '') || '/';
    const isDashboardAlias = currentPath === '/dashboard/index' && linkPath === '/dashboard';
    const isBiodataAlias = currentPath.startsWith('/biodata/') && linkPath === '/biodata/userview';
    const isAttendanceAlias = currentPath.startsWith('/kehadiran/') && linkPath === '/kehadiran/index';
    if (linkPath === currentPath || isDashboardAlias || isBiodataAlias || isAttendanceAlias) {
        link.classList.add('is-active');
    }
});
</script>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
