<?php

declare(strict_types=1);

namespace Axleus\Htmx;

use Axleus\Htmx\ResponseHeaders as Htmx;

use function json_encode;

trait HtmxTriggerTrait
{
    final public const SYSTEM_MESSAGE = 'systemMessage';

    public function htmxTrigger(
        array $data,
        ?string $event = self::SYSTEM_MESSAGE
    ): void {
        $this->headers[Htmx::HX_Trigger->value] = json_encode([$event => $data]);
    }
}
