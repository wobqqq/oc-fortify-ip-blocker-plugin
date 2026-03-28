<?php

declare(strict_types=1);

namespace Wobqqq\FortifyIpBlocker\Instances;

use October\Rain\Support\Traits\Singleton;
use Wobqqq\FortifyIpBlocker\Cache\IpBlockerDtoCache;
use Wobqqq\FortifyIpBlocker\Dto\IpBlockerDto;

final class IpBlockerDtoInstance
{
    use Singleton;

    private ?IpBlockerDto $ipBlockerDto = null;

    public function get(): IpBlockerDto
    {
        if ($this->ipBlockerDto instanceof IpBlockerDto) {
            return $this->ipBlockerDto;
        }

        /** @var IpBlockerDtoCache $ipBlockerDtoCache */
        $ipBlockerDtoCache = app(IpBlockerDtoCache::class);

        return $this->ipBlockerDto = $ipBlockerDtoCache->get();
    }
}
