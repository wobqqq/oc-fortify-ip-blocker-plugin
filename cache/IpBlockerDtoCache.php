<?php

declare(strict_types=1);

namespace Wobqqq\FortifyIpBlocker\Cache;

use Illuminate\Support\Facades\Cache;
use Wobqqq\Fortify\Cache\BasicCache;
use Wobqqq\FortifyIpBlocker\Dto\IpBlockerDto;
use Wobqqq\FortifyIpBlocker\Transformers\FortifyTransformer;

final class IpBlockerDtoCache extends BasicCache
{
    public function get(): IpBlockerDto
    {
        $cacheKey = $this->cacheKey();

        /** @var IpBlockerDto $ipBlockerDto */
        $ipBlockerDto = Cache::remember($cacheKey, self::TTL, function () {
            return FortifyTransformer::ipBlockerDto();
        });

        return $ipBlockerDto;
    }

    public function clear(): void
    {
        Cache::forget($this->cacheKey());
    }
}
