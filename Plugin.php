<?php

declare(strict_types=1);

namespace Wobqqq\FortifyIpBlocker;

use Event;
use System\Classes\PluginBase;
use Validator;
use Wobqqq\FortifyIpBlocker\Console\IpBlockerDisableCommand;
use Wobqqq\FortifyIpBlocker\Console\IpBlockerRemoveIpCommand;
use Wobqqq\FortifyIpBlocker\Listeners\FortifyListener;
use Wobqqq\FortifyIpBlocker\Services\IpBlockerService;
use Wobqqq\FortifyIpBlocker\Validator\Rules\IpBlockerCurrentIpRule;

final class Plugin extends PluginBase
{
    /** @var array<int, string> */
    public $require = ['Wobqqq.Fortify'];

    public function register(): void
    {
        $this->registerConsoleCommand('wobqqq.fortify:ip-blocker:remove-ip', IpBlockerRemoveIpCommand::class);
        $this->registerConsoleCommand('wobqqq.fortify:ip-blocker:disable', IpBlockerDisableCommand::class);
    }

    public function boot(): void
    {
        $this->registerEvents();
        $this->registerValidatorRules();
        $this->runService();
    }

    private function registerEvents(): void
    {
        Event::subscribe(FortifyListener::class);
    }

    private function registerValidatorRules(): void
    {
        Validator::extend('ip_blocker_current_ip', IpBlockerCurrentIpRule::class);
    }

    private function runService(): void
    {
        /** @var IpBlockerService $ipBlockerService */
        $ipBlockerService = app(IpBlockerService::class);
        $ipBlockerService->addMiddleware();
    }
}
