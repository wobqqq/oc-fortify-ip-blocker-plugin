<?php

declare(strict_types=1);

namespace Wobqqq\FortifyIpBlocker\Cache;

use Illuminate\Support\Facades\Cache;
use Throwable;
use Wobqqq\Fortify\Cache\BasicCache;
use Wobqqq\FortifyIpBlocker\Dto\IpBlockerDto;
use Wobqqq\FortifyIpBlocker\Transformers\FortifyTransformer;

final class IpBlockerDtoCache extends BasicCache
{
    public function get(): IpBlockerDto
    {
        $cacheKey = $this->cacheKey();

        try {
            $ipBlockerDto = Cache::remember($cacheKey, self::TTL, FortifyTransformer::ipBlockerDto(...));
        } catch (Throwable) {
            Cache::forget($cacheKey);
            $ipBlockerDto = null;
        }

        return $ipBlockerDto instanceof IpBlockerDto ? $ipBlockerDto : FortifyTransformer::ipBlockerDto();
    }

    public function clear(): void
    {
        Cache::forget($this->cacheKey());
    }
}
