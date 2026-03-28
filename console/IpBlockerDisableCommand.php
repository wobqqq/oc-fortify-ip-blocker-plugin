<?php

declare(strict_types=1);

namespace Wobqqq\FortifyIpBlocker\Console;

use Illuminate\Console\Command;
use Wobqqq\FortifyIpBlocker\Services\IpBlockerService;

final class IpBlockerDisableCommand extends Command
{
    /** @var string */
    protected $name = 'wobqqq.fortify:ip-blocker:disable';

    /** @var string */
    protected $description = 'Disable IP Blocker.';

    public function handle(IpBlockerService $ipBlockerService): void
    {
        $ipBlockerService->disable();

        $this->info('IP Blocker disabled.');
    }
}
