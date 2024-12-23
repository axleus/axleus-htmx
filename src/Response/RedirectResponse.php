<?php

declare(strict_types=1);

namespace Axleus\Htmx\Response;

use Laminas\Diactoros\Response\RedirectResponse as LaminasRedirectResponse;

final class RedirectResponse extends LaminasRedirectResponse
{
    public function __construct(
        string $uri,
        int $status = 200,

        array $headers = []
    ) {
        parent::__construct($uri, $status, $headers);
    }
}
