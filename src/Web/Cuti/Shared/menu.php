<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var list<array<string, mixed>> $menuSections
 */
?>

<nav class="attendance-top-menu cuti-top-menu" aria-label="Cuti module menu">
    <?php foreach ($menuSections as $section): ?>
        <?php /** @var list<array<string, mixed>> $items */
        $items = $section['items']; ?>
        <details class="attendance-top-menu-group cuti-top-menu-group">
            <summary>
                <i data-lucide="<?= Html::encode((string) $section['icon']) ?>"></i>
                <span><?= Html::encode((string) $section['label']) ?></span>
                <?php if ((int) ($section['badge'] ?? 0) > 0): ?>
                    <b><?= Html::encode((string) $section['badge']) ?></b>
                <?php endif; ?>
                <i data-lucide="chevron-down"></i>
            </summary>
            <div class="attendance-top-menu-panel cuti-top-menu-panel">
                <?php foreach ($items as $item): ?>
                    <a href="<?= Html::encode((string) $item['url']) ?>"<?= $item['external'] ? ' target="_blank" rel="noopener"' : '' ?>>
                        <i data-lucide="<?= Html::encode((string) $item['icon']) ?>"></i>
                        <span><?= Html::encode((string) $item['label']) ?></span>
                        <?php if ((int) ($item['badge'] ?? 0) > 0): ?>
                            <b><?= Html::encode((string) $item['badge']) ?></b>
                        <?php elseif ($item['legacy']): ?>
                            <em>Yii2</em>
                        <?php elseif ($item['external']): ?>
                            <i data-lucide="external-link"></i>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </details>
    <?php endforeach; ?>
</nav>
