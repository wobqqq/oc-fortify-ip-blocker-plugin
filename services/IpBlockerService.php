<?php

declare(strict_types=1);

namespace Wobqqq\FortifyIpBlocker\Services;

use App;
use Config;
use Illuminate\Support\Collection;
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

        if (isset($ipBlockerDto->exactIps[$ip])) {
            return false;
        }

        $blockedIps = array_merge($ipBlockerDto->cidrRanges, array_map(strval(...), array_keys($ipBlockerDto->exactIps)));

        return $blockedIps === [] || !IpUtils::checkIp($ip, $blockedIps);
    }

    public function removeIp(string $ip): void
    {
        $ip = trim($ip);

        if ($ip === '') {
            return;
        }

        $ipFirewall = $this->ipFirewall();
        $ipBlockerIps = $ipFirewall['ip_blocker_ips'] ?? [];

        if (!is_array($ipBlockerIps) || $ipBlockerIps === []) {
            return;
        }

        $ipFirewall['ip_blocker_ips'] = array_values(array_filter(
            $ipBlockerIps,
            static fn (mixed $row): bool => !is_array($row) || !is_string($row['ip'] ?? null) || trim($row['ip']) !== $ip,
        ));

        Fortify::set('ip_firewall', $ipFirewall);
    }

    public function disable(): void
    {
        $ipFirewall = $this->ipFirewall();

        $ipFirewall['ip_blocker_enabled'] = false;

        Fortify::set('ip_firewall', $ipFirewall);
    }

    /**
     * @return array<string, mixed>
     */
    private function ipFirewall(): array
    {
        /** @var array<string, mixed>|Collection<string, mixed>|null $ipFirewall */
        $ipFirewall = Fortify::get('ip_firewall');

        if ($ipFirewall instanceof Collection) {
            $ipFirewall = $ipFirewall->all();
        }

        return is_array($ipFirewall) ? $ipFirewall : [];
    }

    private function overrideConfig(string $configName): void
    {
        $middleware = Config::get($configName, []);
        $middleware = is_string($middleware) ? [$middleware] : (is_array($middleware) ? $middleware : []);
        $middleware = array_filter($middleware, static fn (mixed $name): bool => is_string($name) && $name !== '');

        $middleware[] = IpBlockerMiddleware::ALIAS;

        Config::set($configName, array_values(array_unique($middleware)));
    }
}
