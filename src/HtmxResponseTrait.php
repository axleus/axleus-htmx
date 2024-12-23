<?php

declare(strict_types=1);

namespace Axleus\Htmx;

use Axleus\Htmx\ResponseHeaders as Header;

trait HtmxResponseTrait
{
    use HtmxTriggerTrait;

    public function htmxLocation(string $uri): void
    {
        $this->headers[Header::HX_Location->value] = $uri;
    }
}
