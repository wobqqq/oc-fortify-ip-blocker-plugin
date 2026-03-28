<?php

declare(strict_types=1);

namespace Wobqqq\FortifyIpBlocker\Console;

use Illuminate\Console\Command;
use Wobqqq\FortifyIpBlocker\Services\IpBlockerService;

final class IpBlockerRemoveIpCommand extends Command
{
    /** @var string */
    protected $name = 'wobqqq.fortify:ip-blocker:remove-ip';

    /** @var string */
    protected $signature = 'wobqqq.fortify:ip-blocker:remove-ip {ip}';

    /** @var string */
    protected $description = 'Remove an IP from the IP blocker blacklist.';

    public function handle(IpBlockerService $ipBlockerService): void
    {
        /** @var string|null $ip */
        $ip = $this->argument('ip');

        $ipBlockerService->removeIp((string)$ip);

        $this->info(sprintf('IP %s has been removed from the blacklist.', $ip));
    }
}
