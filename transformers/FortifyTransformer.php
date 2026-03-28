<?php

declare(strict_types=1);

namespace Wobqqq\FortifyIpBlocker\Transformers;

use Illuminate\Support\Facades\View as IlluminateView;
use Wobqqq\Fortify\Enums\View;
use Wobqqq\Fortify\Models\Fortify;
use Wobqqq\FortifyIpBlocker\Dto\IpBlockerDto;

final readonly class FortifyTransformer
{
    public static function ipBlockerDto(): IpBlockerDto
    {
        /** @var bool|int|null $enabled */
        $enabled = Fortify::get('ip_firewall.ip_blocker_enabled');
        $enabled = (bool)$enabled;

        /** @var string|null $view */
        $view = Fortify::get('ip_firewall.ip_blocker_view');
        $view = (string)$view;
        $view = empty($view) || !IlluminateView::exists($view) ? View::DENIED->value : $view;

        if ($enabled) {
            /** @var array<int, string>|null $ips */
            $ips = Fortify::get('ip_firewall.ip_blocker_ips');
            $ips = (empty($ips) || !is_array($ips)) ? [] : $ips;
            /** @var array<int, string> $ips */
            $ips = array_column($ips, 'ip');
            $ips = array_unique($ips);
            $ips = array_filter($ips);

            $cidrRanges = [];
            $exactIps = [];

            foreach ($ips as $ip) {
                if (str_contains($ip, '/')) {
                    $cidrRanges[] = $ip;
                } else {
                    $exactIps[$ip] = 1;
                }
            }
        } else {
            $cidrRanges = [];
            $exactIps = [];
        }

        return new IpBlockerDto(
            $enabled,
            $view,
            $cidrRanges,
            $exactIps,
        );
    }
}
