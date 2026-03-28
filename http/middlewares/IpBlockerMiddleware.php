<?php

declare(strict_types=1);

namespace Wobqqq\FortifyIpBlocker\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View as IlluminateView;
use Wobqqq\Fortify\Enums\View;
use Wobqqq\FortifyIpBlocker\Instances\IpBlockerDtoInstance;
use Wobqqq\FortifyIpBlocker\Services\IpBlockerService;

final class IpBlockerMiddleware
{
    public const ALIAS = 'fortify_ip_blocker';

    public function __construct(private readonly IpBlockerService $ipBlockerService)
    {
    }

    /**
     * @param Request $request
     * @param Closure $next
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response|mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();

        if ($this->ipBlockerService->check((string)$ip)) {
            return $next($request);
        }

        $ipBlockerDto = IpBlockerDtoInstance::instance()->get();

        $view = IlluminateView::exists($ipBlockerDto->view)
            ? $ipBlockerDto->view
            : View::DENIED->value;

        /** @var \Illuminate\Routing\ResponseFactory $response */
        $response = response();

        return $response->view($view, [], 403);
    }
}
