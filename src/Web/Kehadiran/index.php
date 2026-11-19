<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var array<string, mixed>|null $attendance
 * @var string $identityId
 */

$this->setTitle('STARS - ' . $applicationParams->name);
?>

<?php if ($attendance === null): ?>
    <main class="attendance-page">
        <section class="profile-card page-main-card">
            <div class="dashboard-band page-card-heading">
                <h1>STARS unavailable</h1>
                <p>The current legacy session <?= Html::encode($identityId) ?> is not a staff attendance session.</p>
            </div>
            <div class="page-section-body">
                <a class="button-link" href="/login">
                    <i data-lucide="log-in"></i>
                    <span>Back to login</span>
                </a>
            </div>
        </section>
    </main>
<?php else: ?>
    <?php
    /** @var array<string, mixed>|null $workingPattern */
    $workingPattern = $attendance['workingPattern'];
    /** @var array<string, mixed>|null $currentRecord */
    $currentRecord = $attendance['currentRecord'];
    /** @var array<string, mixed> $cardColor */
    $cardColor = $attendance['cardColor'];
    /** @var array<string, int> $pending */
    $pending = $attendance['pending'];
    /** @var list<array<string, mixed>> $menuSections */
    $menuSections = $attendance['menuSections'];
    $cardColour = (string) $cardColor['color'];
    $cardClass = match ($cardColour) {
        'green' => 'attendance-card-green',
        'yellow' => 'attendance-card-yellow',
        'red' => 'attendance-card-red',
        default => 'attendance-card-neutral',
    };
    $statusLabels = $currentRecord['statusLabels'] ?? [];
    ?>

    <main class="attendance-page">
        <section class="profile-card page-main-card attendance-main-card">
            <div class="dashboard-band dashboard-heading page-card-heading">
                <div>
                    <h1>STARS</h1>
                    <p><?= Html::encode((string) $attendance['staffName']) ?></p>
                </div>
                <a class="button-link button-link-secondary" href="/dashboard">
                    <i data-lucide="arrow-left"></i>
                    <span>Kembali</span>
                </a>
            </div>

            <?php require __DIR__ . '/_topMenu.php'; ?>

            <div class="attendance-notice page-card-section page-section-body">
                <i data-lucide="info"></i>
                <span><?= Html::encode((string) $attendance['notice']) ?></span>
            </div>

            <div class="dashboard-grid dashboard-metrics attendance-metrics page-section-body page-card-section" aria-label="STARS summary">
                <article class="metric-card">
                    <span>Hours Completed</span>
                    <strong><?= Html::encode((string) $attendance['hourComplete']) ?>%</strong>
                    <small><?= $currentRecord === null ? 'No current attendance record' : Html::encode((string) $currentRecord['totalHours']) . ' hours' ?></small>
                    <div class="attendance-progress" aria-hidden="true">
                        <span style="width: <?= Html::encode((string) $attendance['hourComplete']) ?>%"></span>
                    </div>
                </article>
                <article class="metric-card">
                    <span>Card Colour</span>
                    <strong class="attendance-colour-chip <?= Html::encode($cardClass) ?>"><?= Html::encode($cardColour) ?></strong>
                    <small><?= Html::encode((string) $cardColor['month']) ?>/<?= Html::encode((string) $cardColor['year']) ?></small>
                </article>
                <article class="metric-card">
                    <span>Work Mode</span>
                    <strong><?= Html::encode((string) $attendance['workMode']) ?></strong>
                    <small><?= $workingPattern === null ? '--Tiada WBB--' : Html::encode((string) $workingPattern['label']) ?></small>
                </article>
                <article class="metric-card">
                    <span>Pending Tasks</span>
                    <strong><?= Html::encode((string) $pending['all']) ?></strong>
                    <small><?= Html::encode((string) $pending['reason']) ?> reason &middot; <?= Html::encode((string) $pending['approval']) ?> approval</small>
                </article>
            </div>

            <div class="page-section-body page-card-section">
                <div class="content-inner-card attendance-info-card">
                    <div class="attendance-columns">
                        <section class="attendance-details">
                            <div class="dashboard-panel-header">
                                <h2>Details</h2>
                                <span><?= Html::encode((string) $attendance['today']) ?></span>
                            </div>
                            <dl class="attendance-detail-list">
                                <dt>Department</dt>
                                <dd><?= Html::encode((string) $attendance['department']) ?></dd>
                                <dt>Campus</dt>
                                <dd><?= Html::encode((string) $attendance['campus']) ?></dd>
                                <dt>Working Hours</dt>
                                <dd>
                                    <?php if ($workingPattern === null): ?>
                                        --Tiada WBB--
                                    <?php else: ?>
                                        <?= Html::encode((string) $workingPattern['startTime']) ?> - <?= Html::encode((string) $workingPattern['endTime']) ?>
                                        <small><?= Html::encode((string) $workingPattern['totalHours']) ?> hours</small>
                                    <?php endif; ?>
                                </dd>
                                <dt>Time In / Out</dt>
                                <dd><?= Html::encode((string) ($currentRecord['timeIn'] ?? '-')) ?> / <?= Html::encode((string) ($currentRecord['timeOut'] ?? '-')) ?></dd>
                                <dt>Non-compliance</dt>
                                <dd>
                                    <?php if ($statusLabels === []): ?>
                                        -
                                    <?php else: ?>
                                        <?php foreach ($statusLabels as $label): ?>
                                            <span class="status-badge status-badge-danger"><?= Html::encode((string) $label) ?></span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </dd>
                                <dt>IP Address</dt>
                                <dd><?= Html::encode((string) $attendance['clientIp']) ?> <small><?= Html::encode((string) $attendance['ipType']) ?></small></dd>
                                <dt>Location</dt>
                                <dd><a class="grid-action" href="https://www.google.com/maps" target="_blank" rel="noopener"><i data-lucide="map-pin"></i><span>Check Location</span></a></dd>
                            </dl>
                        </section>

                        <aside class="attendance-card-summary">
                            <div class="dashboard-panel-header">
                                <h2>Card Colour Status</h2>
                            </div>
                            <div class="attendance-card-swatch <?= Html::encode($cardClass) ?>"></div>
                            <dl class="attendance-compact-list">
                                <dt>Ketidakpatuhan</dt>
                                <dd><?= Html::encode((string) $cardColor['nonCompliance']) ?></dd>
                                <dt>Approved</dt>
                                <dd><?= Html::encode((string) $cardColor['approved']) ?></dd>
                                <dt>Disapproved</dt>
                                <dd><?= Html::encode((string) $cardColor['disapproved']) ?></dd>
                            </dl>
                        </aside>
                    </div>
                </div>
            </div>
        </section>
    </main>
<?php endif; ?>
