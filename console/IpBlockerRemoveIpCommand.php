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

    public function handle(IpBlockerService $ipBlockerService): int
    {
        $ip = $this->argument('ip');
        $ip = is_string($ip) ? trim($ip) : '';

        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            $this->error(sprintf('%s is not an IP address.', $ip));

            return self::FAILURE;
        }

        $ipBlockerService->removeIp($ip);

        $this->info(sprintf('IP %s has been removed from the blacklist.', $ip));

        return self::SUCCESS;
    }
}
