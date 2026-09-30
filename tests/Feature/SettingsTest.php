<?php

declare(strict_types=1);

use Backend\Widgets\Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use System\Controllers\Settings;
use Wobqqq\Fortify\Dto\WidgetGroupItemDto;
use Wobqqq\Fortify\Enums\FortifyEvent;
use Wobqqq\Fortify\Enums\WidgetItemColor;
use Wobqqq\Fortify\Models\Fortify;
use Wobqqq\Fortify\Transformers\FortifyTransformer as CoreTransformer;
use Wobqqq\FortifyIpBlocker\Cache\IpBlockerDtoCache;
use Wobqqq\FortifyIpBlocker\Instances\IpBlockerDtoInstance;
use Wobqqq\FortifyIpBlocker\Services\IpBlockerService;

function ipBlockerSettingsForm(string $ip = '198.51.100.20'): Form
{
    app()->instance('request', Request::create('/admin/system/settings', 'GET', [], [], [], ['REMOTE_ADDR' => $ip]));

    $form = new Form(new Settings(), Fortify::instance());
    Event::dispatch('backend.form.extendFields', [$form]);

    return $form;
}

function ipBlockerSettings(string $ip = '198.51.100.20'): Fortify
{
    $model = ipBlockerSettingsForm($ip)->model;

    return $model instanceof Fortify ? $model : throw new UnexpectedValueException('The form is not the Fortify settings.');
}

it('adds its section to the Fortify settings form', function (): void {
    expect(ipBlockerSettingsForm()->tabFields)->toHaveKeys([
        'ip_firewall[ip_blocker_section]',
        'ip_firewall[ip_blocker_enabled]',
        'ip_firewall[ip_blocker_view]',
        'ip_firewall[ip_blocker_ips]',
    ]);
});

it('ignores a form that is not the Fortify settings', function (): void {
    $form = new Form(null, Fortify::instance());
    Event::dispatch('backend.form.extendFields', [$form]);

    expect($form->tabFields)->toBe([]);
});

it('sets its defaults even when another module set up the firewall first', function (): void {
    Fortify::set('ip_firewall', ['smart_ip_blocker_enabled' => true]);

    expect(ipBlockerSettings()->ip_firewall)->toMatchArray([
        'smart_ip_blocker_enabled' => true,
        'ip_blocker_enabled' => false,
        'ip_blocker_view' => 'wobqqq.fortify::denied',
    ]);
});

it('keeps the values already saved', function (): void {
    Fortify::set('ip_firewall', ['ip_blocker_enabled' => true, 'ip_blocker_view' => 'acme.theme::blocked']);

    expect(ipBlockerSettings()->ip_firewall)->toMatchArray([
        'ip_blocker_enabled' => true,
        'ip_blocker_view' => 'acme.theme::blocked',
    ]);
});

it('starts from the defaults on a fresh site', function (): void {
    Fortify::clearInternalCache();

    expect(Fortify::instance()->ip_firewall)->toMatchArray(['ip_blocker_enabled' => false, 'ip_blocker_view' => 'wobqqq.fortify::denied']);
});

it('drops the empty rows when the settings are saved', function (): void {
    Fortify::set('ip_firewall', ['ip_blocker_ips' => [['ip' => '203.0.113.7'], ['ip' => '  '], ['ip' => null], 'broken']]);

    expect(Fortify::get('ip_firewall.ip_blocker_ips'))->toBe([['ip' => '203.0.113.7']]);
});

it('validates the addresses and refuses to block the administrator', function (mixed $ips, bool $passes): void {
    $model = ipBlockerSettings('198.51.100.20');
    (new ReflectionProperty(app(), 'isRunningInConsole'))->setValue(app(), false);

    $data = [
        'config' => ['password_policy_min_length' => 12, 'session_lifetime' => 30],
        'ip_firewall' => ['ip_blocker_view' => 'wobqqq.fortify::denied', 'ip_blocker_ips' => $ips],
    ];

    expect(Validator::make($data, $model->rules)->passes())->toBe($passes);
})->with([
    'another address' => [[['ip' => '203.0.113.7']], true],
    'a subnet elsewhere' => [[['ip' => '10.0.0.0/8']], true],
    'an IPv6 address' => [[['ip' => '2001:db8::1']], true],
    'no address' => [[], true],
    'the administrator' => [[['ip' => '198.51.100.20']], false],
    'a subnet covering the administrator' => [[['ip' => '198.51.100.0/24']], false],
    'not an address' => [[['ip' => '203.0.113.7; drop']], false],
]);

it('lets the console save any list', function (): void {
    $model = ipBlockerSettings('198.51.100.20');

    $data = [
        'config' => ['password_policy_min_length' => 12, 'session_lifetime' => 30],
        'ip_firewall' => ['ip_blocker_view' => 'wobqqq.fortify::denied', 'ip_blocker_ips' => [['ip' => '198.51.100.20']]],
    ];

    expect(Validator::make($data, $model->rules)->passes())->toBeTrue();
});

it('names the address in the lock-out message', function (): void {
    $model = ipBlockerSettings('198.51.100.20');
    (new ReflectionProperty(app(), 'isRunningInConsole'))->setValue(app(), false);

    $validator = Validator::make([
        'config' => ['password_policy_min_length' => 12, 'session_lifetime' => 30],
        'ip_firewall' => ['ip_blocker_view' => 'wobqqq.fortify::denied', 'ip_blocker_ips' => [['ip' => '198.51.100.20']]],
    ], $model->rules);

    expect($validator->errors()->first('ip_firewall.ip_blocker_ips'))->toContain('198.51.100.20');
});

it('shows on the dashboard whether it is on', function (): void {
    $item = CoreTransformer::widgetGroupItemDto('placeholder');
    Event::dispatch(FortifyEvent::SERVICES_WIDGET_GROUP_ITEM_IP_BLOCKER->value, [&$item]);

    expect($item)->toBeInstanceOf(WidgetGroupItemDto::class)
        ->and($item->color)->toBe(WidgetItemColor::DANGER);

    configureIpBlocker([]);
    Event::dispatch(FortifyEvent::SERVICES_WIDGET_GROUP_ITEM_IP_BLOCKER->value, [&$item]);

    expect($item->color)->toBe(WidgetItemColor::SUCCESS);
});

it('applies a saved list at once, even to a settings instance older than the module', function (): void {
    configureIpBlocker(['ip_blocker_ips' => [['ip' => '203.0.113.7']]]);
    expect(app(IpBlockerDtoCache::class)->get()->exactIps)->toBe(['203.0.113.7' => 1]);

    Fortify::clearInternalCache();
    Fortify::instance();
    Fortify::set('ip_firewall', ['ip_blocker_enabled' => true, 'ip_blocker_ips' => [['ip' => '203.0.113.9']]]);
    IpBlockerDtoInstance::forgetInstance();

    expect(app(IpBlockerService::class)->check('203.0.113.9'))->toBeFalse()
        ->and(app(IpBlockerService::class)->check('203.0.113.7'))->toBeTrue();
});

it('rebuilds a cached list the previous version wrote in another shape', function (): void {
    configureIpBlocker(['ip_blocker_ips' => [['ip' => '203.0.113.7']]]);

    Cache::shouldReceive('remember')->once()->andThrow(new TypeError('Cannot assign string to property'));
    Cache::shouldReceive('forget')->once();

    expect(app(IpBlockerDtoCache::class)->get()->exactIps)->toBe(['203.0.113.7' => 1]);
});
