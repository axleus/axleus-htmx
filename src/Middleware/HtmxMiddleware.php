<?php

declare(strict_types=1);

namespace Axleus\Htmx\Middleware;

use Axleus\Htmx\Request\HtmxFilter;
use Axleus\Htmx\RequestHeaders as Htmx;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function json_encode;

class HtmxMiddleware implements MiddlewareInterface
{
    public function __construct(
        private TemplateRendererInterface $template,
        private array $htmxConfig
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        if ($request->getAttribute(Htmx::HX_Request->value, false)) {
            $this->template->addDefaultParam(
                TemplateRendererInterface::TEMPLATE_ALL,
                'layout',
                false
            );
        }

        // todo: refactor this to use a custom container
        if ($this->htmxConfig['enable']) {
            $this->template->addDefaultParam(
                TemplateRendererInterface::TEMPLATE_ALL,
                'htmxConfig',
                json_encode($this->htmxConfig['config'])
            );
        }

        return $handler->handle($request);
    }
}
