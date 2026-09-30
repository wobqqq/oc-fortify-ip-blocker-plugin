<?php

declare(strict_types=1);

namespace Wobqqq\FortifyIpBlocker\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View as IlluminateView;
use Symfony\Component\HttpFoundation\Response;
use Wobqqq\Fortify\Enums\View;
use Wobqqq\FortifyIpBlocker\Instances\IpBlockerDtoInstance;
use Wobqqq\FortifyIpBlocker\Services\IpBlockerService;

final readonly class IpBlockerMiddleware
{
    public const ALIAS = 'fortify_ip_blocker';

    public function __construct(private IpBlockerService $ipBlockerService)
    {
    }

    /**
     * @param Closure(Request): mixed $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if ($this->ipBlockerService->check((string)$request->ip())) {
            return $next($request);
        }

        $ipBlockerDto = IpBlockerDtoInstance::instance()->get();

        $view = IlluminateView::exists($ipBlockerDto->view)
            ? $ipBlockerDto->view
            : View::DENIED->value;

        /** @var \Illuminate\Routing\ResponseFactory $response */
        $response = response();

        return $response->view($view, [], Response::HTTP_FORBIDDEN);
    }
}
