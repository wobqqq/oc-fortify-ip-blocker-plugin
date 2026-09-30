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
        $enabled = (bool)Fortify::get('ip_firewall.ip_blocker_enabled');

        $view = Fortify::get('ip_firewall.ip_blocker_view');
        $view = is_string($view) && $view !== '' && IlluminateView::exists($view) ? $view : View::DENIED->value;

        $cidrRanges = [];
        $exactIps = [];

        if ($enabled) {
            $rows = Fortify::get('ip_firewall.ip_blocker_ips');

            foreach (is_array($rows) ? $rows : [] as $row) {
                $ip = is_array($row) && is_scalar($row['ip'] ?? null) ? trim((string)$row['ip']) : '';

                if ($ip === '') {
                    continue;
                }

                if (str_contains($ip, '/')) {
                    $cidrRanges[] = $ip;
                } else {
                    $exactIps[$ip] = 1;
                }
            }
        }

        return new IpBlockerDto(
            $enabled,
            $view,
            array_values(array_unique($cidrRanges)),
            $exactIps,
        );
    }
}
