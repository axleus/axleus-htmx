<?php

declare(strict_types=1);

namespace Axleus\Htmx\Response;

use Axleus\Htmx\ResponseHeaders as Header;

use Laminas\Diactoros\Response\RedirectResponse as LaminasRedirectResponse;

final class RedirectResponse extends LaminasRedirectResponse
{
    public function __construct(
        string $uri,
    ) {
        // htmx does not evaluate headers with 3xx status codes
        $status = 200;
        $headers[Header::HX_Redirect->value] = $uri;
        parent::__construct($uri, $status, $headers);
    }
}
