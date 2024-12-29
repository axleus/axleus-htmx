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

        $headers = $request->getHeaders();
        $htmxHeaders = array_flip(Htmx::toArray(
            normalize: true,
            valueTreatment: 'strtolower',
        ));

        foreach ($headers as $header => $value) {
            if (isset($htmxHeaders[$header])) {
                $request = $request->withAttribute(
                    $header,
                    $value[0] === 'true' ? true : $value[0]
                );
            }
        }

        return $request;
    }
}
