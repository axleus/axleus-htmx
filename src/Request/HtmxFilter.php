<?php

declare(strict_types=1);

namespace Axleus\Htmx\Request;

use Axleus\Htmx\RequestHeaders as Htmx;
use Laminas\Diactoros\ServerRequestFilter\FilterServerRequestInterface;
use Laminas\Diactoros\ServerRequestFilter\FilterUsingXForwardedHeaders;
use Psr\Http\Message\ServerRequestInterface;

final class HtmxFilter implements FilterServerRequestInterface
{
    public function __invoke(ServerRequestInterface $request): ServerRequestInterface
    {
        // maintain default behavior
        $request = FilterUsingXForwardedHeaders::trustReservedSubnets()($request);
        if (! empty($request->getHeader(Htmx::HX_Request->value))) {
            $request = $request->withAttribute(Htmx::HX_Request->value, true);
        }
        $trigger = $request->getHeader(Htmx::HX_Trigger->value);
        if (! empty($trigger)) {
            $request = $request->withAttribute(Htmx::HX_Trigger->value, $trigger[0]);
        }

        return $request;
    }
}
