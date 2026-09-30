<?php

declare(strict_types=1);

use Wobqqq\Fortify\Models\Fortify;
use Wobqqq\FortifyIpBlocker\Tests\TestCase;

pest()->extend(TestCase::class)->in('Unit', 'Feature');

/**
 * @param array<string, mixed> $settings
 */
function configureIpBlocker(array $settings): void
{
    Fortify::set('ip_firewall', array_merge([
        'ip_blocker_enabled' => true,
        'ip_blocker_view' => 'wobqqq.fortify::denied',
        'ip_blocker_ips' => [],
    ], $settings));

    TestCase::bootPlugins();
}
