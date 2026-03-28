<?php

declare(strict_types=1);

namespace Wobqqq\FortifyIpBlocker\Services;

use App;
use Arr;
use Config;
use October\Rain\Router\CoreRouter;
use Symfony\Component\HttpFoundation\IpUtils;
use Wobqqq\Fortify\Models\Fortify;
use Wobqqq\FortifyIpBlocker\Http\Middlewares\IpBlockerMiddleware;
use Wobqqq\FortifyIpBlocker\Instances\IpBlockerDtoInstance;

final class IpBlockerService
{
    private static bool $addMiddleware = false;

    public function addMiddleware(): void
    {
        if (self::$addMiddleware) {
            return;
        }

        self::$addMiddleware = true;

        $ipBlockerDto = IpBlockerDtoInstance::instance()->get();

        if (!$ipBlockerDto->enabled) {
            return;
        }

        /** @var CoreRouter $coreRoute */
        $coreRoute = App::make('router');
        $coreRoute->aliasMiddleware(IpBlockerMiddleware::ALIAS, IpBlockerMiddleware::class);

        $this->overrideConfig('cms.middleware_group');
        $this->overrideConfig('backend.middleware_group');
    }

    public function check(string $ip): bool
    {
        $ipBlockerDto = IpBlockerDtoInstance::instance()->get();

        if (!$ipBlockerDto->enabled) {
            return true;
        }

        if (empty($ipBlockerDto->cidrRanges) && empty($ipBlockerDto->exactIps)) {
            return true;
        }

        if (isset($ipBlockerDto->exactIps[$ip])) {
            return false;
        }

        if (empty($ipBlockerDto->cidrRanges)
            || !IpUtils::checkIp($ip, $ipBlockerDto->cidrRanges)) {
            return true;
        }

        return false;
    }

    public function removeIp(string $ip): void
    {
        $ip = trim($ip);

        if (empty($ip)) {
            return;
        }

        /** @var array<string, mixed>|\Illuminate\Support\Collection<int, mixed> $ipFirewall */
        $ipFirewall = Fortify::get('ip_firewall');

        if ($ipFirewall instanceof \Illuminate\Support\Collection) {
            $ipFirewall = $ipFirewall->toArray();
        }

        $ipFirewall = !is_array($ipFirewall) ? [] : $ipFirewall;

        /** @var array<int, array<string, string>> $ipBlockerIps */
        $ipBlockerIps = Arr::get($ipFirewall, 'ip_blocker_ips', []);

        if (empty($ipBlockerIps)) {
            return;
        }

        foreach ($ipBlockerIps as $key => $ipBlockerIp) {
            /** @var string|null $ipBlockerIp */
            $ipBlockerIp = Arr::get((array)$ipBlockerIp, 'ip');
            $ipBlockerIp = trim((string)$ipBlockerIp);

            if ($ip === $ipBlockerIp) {
                unset($ipBlockerIps[$key]);
            }
        }

        $ipFirewall['ip_blocker_ips'] = $ipBlockerIps;

        Fortify::set('ip_firewall', $ipFirewall);
    }

    public function disable(): void
    {
        /** @var array<string, mixed>|\Illuminate\Support\Collection<int, mixed> $ipFirewall */
        $ipFirewall = Fortify::get('ip_firewall');

        if ($ipFirewall instanceof \Illuminate\Support\Collection) {
            $ipFirewall = $ipFirewall->toArray();
        }

        $ipFirewall = !is_array($ipFirewall) ? [] : $ipFirewall;

        $ipFirewall['ip_blocker_enabled'] = false;

        Fortify::set('ip_firewall', $ipFirewall);
    }

    private function overrideConfig(string $configName): void
    {
        /** @var string|null|array<int, string> $middleware */
        $middleware = Config::get($configName, []);

        if (is_string($middleware)) {
            $middleware = [$middleware];
        }

        if (empty($middleware)) {
            $middleware = [];
        }

        $middleware[] = IpBlockerMiddleware::ALIAS;
        /** @var array<int, string> $middleware */
        $middleware = array_unique($middleware);
        $middleware = array_filter($middleware);

        Config::set($configName, $middleware);
    }
}
