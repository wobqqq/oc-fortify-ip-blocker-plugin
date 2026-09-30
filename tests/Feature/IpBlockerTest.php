<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Wobqqq\Fortify\Models\Fortify;
use Wobqqq\FortifyIpBlocker\Http\Middlewares\IpBlockerMiddleware;
use Wobqqq\FortifyIpBlocker\Services\IpBlockerService;
use Wobqqq\FortifyIpBlocker\Tests\TestCase;

function ipBlockerVisit(string $ip): Response
{
    $request = Request::create('/page', 'GET', [], [], [], ['REMOTE_ADDR' => $ip]);
    $response = app(IpBlockerMiddleware::class)->handle($request, static fn (): Response => new Response('content'));

    return $response instanceof Response ? $response : throw new UnexpectedValueException('No response.');
}

it('adds its middleware to the site and the backend only while enabled', function (): void {
    expect(Config::get('cms.middleware_group'))->toBe('web');

    configureIpBlocker(['ip_blocker_enabled' => false]);

    expect(Config::get('cms.middleware_group'))->toBe('web');

    configureIpBlocker([]);

    expect(Config::get('cms.middleware_group'))->toBe(['web', IpBlockerMiddleware::ALIAS])
        ->and(Config::get('backend.middleware_group'))->toBe(['web', IpBlockerMiddleware::ALIAS]);
});

it('blocks the listed addresses and subnets with the configured page', function (string $ip): void {
    configureIpBlocker(['ip_blocker_ips' => [['ip' => '203.0.113.7'], ['ip' => '10.0.0.0/8'], ['ip' => '2001:db8::1'], ['ip' => '']]]);

    $response = ipBlockerVisit($ip);

    expect($response->getStatusCode())->toBe(403)
        ->and($response->getContent())->toContain('Access denied');
})->with([
    'an address' => '203.0.113.7',
    'an address in a subnet' => '10.20.30.40',
    'an IPv6 address written in full' => '2001:0db8:0000:0000:0000:0000:0000:0001',
    'an IPv6 address written short' => '2001:db8::1',
]);

it('lets every other visitor through', function (): void {
    configureIpBlocker(['ip_blocker_ips' => [['ip' => '203.0.113.7'], ['ip' => '10.0.0.0/8']]]);

    $response = ipBlockerVisit('198.51.100.1');

    expect($response->getStatusCode())->toBe(200)->and($response->getContent())->toBe('content');
});

it('blocks nobody while disabled or with an empty list', function (): void {
    configureIpBlocker(['ip_blocker_enabled' => false, 'ip_blocker_ips' => [['ip' => '203.0.113.7']]]);
    expect(app(IpBlockerService::class)->check('203.0.113.7'))->toBeTrue();

    configureIpBlocker(['ip_blocker_ips' => []]);
    expect(app(IpBlockerService::class)->check('203.0.113.7'))->toBeTrue();
});

it('falls back to the default page when the chosen one is gone', function (): void {
    configureIpBlocker(['ip_blocker_view' => 'acme.theme::missing', 'ip_blocker_ips' => [['ip' => '203.0.113.7']]]);

    expect(ipBlockerVisit('203.0.113.7')->getContent())->toContain('Access denied');
});

it('removes an address from the list from the console', function (): void {
    configureIpBlocker(['ip_blocker_ips' => [['ip' => '203.0.113.7'], ['ip' => '203.0.113.8']]]);

    expect(Artisan::call('wobqqq.fortify:ip-blocker:remove-ip', ['ip' => '203.0.113.7']))->toBe(0)
        ->and(Fortify::get('ip_firewall.ip_blocker_ips'))->toBe([['ip' => '203.0.113.8']]);

    TestCase::bootPlugins();

    expect(ipBlockerVisit('203.0.113.7')->getStatusCode())->toBe(200);
});

it('refuses to remove something that is not an IP', function (): void {
    expect(Artisan::call('wobqqq.fortify:ip-blocker:remove-ip', ['ip' => 'not-an-ip']))->toBe(1)
        ->and(Artisan::output())->toContain('not-an-ip is not an IP address.');
});

it('leaves an empty list alone', function (): void {
    configureIpBlocker(['ip_blocker_ips' => []]);

    app(IpBlockerService::class)->removeIp('203.0.113.7');
    app(IpBlockerService::class)->removeIp('  ');

    expect(Fortify::get('ip_firewall.ip_blocker_ips'))->toBe([]);
});

it('turns itself off from the console and keeps the other firewall settings', function (): void {
    configureIpBlocker(['admin_ip_access_enabled' => true]);

    expect(Artisan::call('wobqqq.fortify:ip-blocker:disable'))->toBe(0)
        ->and(Fortify::get('ip_firewall.ip_blocker_enabled'))->toBeFalse()
        ->and(Fortify::get('ip_firewall.admin_ip_access_enabled'))->toBeTrue();
});
