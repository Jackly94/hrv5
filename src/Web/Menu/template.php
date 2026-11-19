<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var array<string, mixed>|null $menu
 * @var bool $showDebug
 */

$this->setTitle('Menu - ' . $applicationParams->name);
$lucideIcon = static function (string $icon): string {
    return match ($icon) {
        'book' => 'book-open',
        'car' => 'car',
        'shield' => 'shield',
        'university' => 'landmark',
        'users' => 'users',
        'wrench' => 'wrench',
        default => 'folder',
    };
};
?>

<main class="profile-page menu-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1>Menu</h1>
                <p><?= $menu === null ? 'Senarai modul HROnline' : Html::encode((string) $menu['staffName']) ?></p>
            </div>
            <div class="profile-actions">
                <a class="button-link button-link-secondary" href="/dashboard">
                    <i data-lucide="arrow-left"></i>
                    <span>Kembali</span>
                </a>
            </div>
        </div>

        <?php if ($menu === null): ?>
            <div class="profile-empty">Menu tidak tersedia untuk sesi semasa.</div>
        <?php else: ?>
            <?php /** @var list<array<string, mixed>> $categories */
            $categories = $menu['categories']; ?>
            <div class="page-card-section page-section-body">
                <div class="menu-grid">
                    <?php foreach ($categories as $category): ?>
                        <?php /** @var list<array<string, mixed>> $items */
                        $items = $category['items']; ?>
                        <section class="menu-category-card">
                            <header class="menu-category-header">
                                <span class="menu-category-icon">
                                    <i data-lucide="<?= Html::encode($lucideIcon((string) $category['icon'])) ?>"></i>
                                </span>
                                <span>
                                    <strong><?= Html::encode((string) $category['name']) ?></strong>
                                    <small><?= count($items) ?> item</small>
                                </span>
                            </header>
                            <div class="menu-item-list">
                                <?php foreach ($items as $item): ?>
                                    <?php /** @var list<array<string, mixed>> $children */
                                    $children = $item['children']; ?>
                                    <?php if ($children !== []): ?>
                                        <details class="menu-collapse">
                                            <summary>
                                                <span><?= Html::encode((string) $item['label']) ?></span>
                                                <i data-lucide="chevron-down"></i>
                                            </summary>
                                            <div class="menu-child-list">
                                                <?php foreach ($children as $child): ?>
                                                    <a href="<?= Html::encode((string) $child['url']) ?>"<?= $child['external'] ? ' target="_blank" rel="noopener"' : '' ?>>
                                                        <span><?= Html::encode((string) $child['label']) ?></span>
                                                        <?php if ($child['legacy']): ?>
                                                            <em>Yii2</em>
                                                        <?php elseif ($child['external']): ?>
                                                            <i data-lucide="external-link"></i>
                                                        <?php endif; ?>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        </details>
                                    <?php else: ?>
                                        <a class="menu-item-link" href="<?= Html::encode((string) $item['url']) ?>"<?= $item['external'] ? ' target="_blank" rel="noopener"' : '' ?>>
                                            <span><?= Html::encode((string) $item['label']) ?></span>
                                            <?php if ($item['legacy']): ?>
                                                <em>Yii2</em>
                                            <?php elseif ($item['external']): ?>
                                                <i data-lucide="external-link"></i>
                                            <?php else: ?>
                                                <i data-lucide="arrow-right"></i>
                                            <?php endif; ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>

                <?php if ($showDebug && ($menu['unsupportedRules'] ?? []) !== []): ?>
                    <details class="menu-debug">
                        <summary>Unsupported visibility rules hidden: <?= count($menu['unsupportedRules']) ?></summary>
                        <ul>
                            <?php foreach ($menu['unsupportedRules'] as $rule): ?>
                                <li><?= Html::encode((string) $rule) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
