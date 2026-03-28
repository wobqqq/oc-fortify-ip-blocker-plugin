<?php

declare(strict_types=1);

namespace Wobqqq\FortifyIpBlocker\Dto;

final readonly class IpBlockerDto
{
    public function __construct(
        public bool   $enabled,
        public string $view,
        /** @var array<int, string> $cidrRanges */
        public array  $cidrRanges = [],
        /** @var array<string, int> $exactIps */
        public array  $exactIps = [],
    ) {
    }
}
