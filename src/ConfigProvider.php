<?php

declare(strict_types=1);

namespace Axleus\Htmx;

use Axleus\Core\ConfigProviderInterface;
use Laminas\Diactoros\ServerRequestFilter\FilterServerRequestInterface;
use Laminas\Form\View\Helper\Form as LaminasFormHelper;

final class ConfigProvider implements ConfigProviderInterface
{
    public function __invoke(): array
    {
        return [
            static::class        => $this->getAxleusConfig(),
            'dependencies'       => $this->getDependencies(),
            'view_helpers'       => $this->getViewHelpers(),
            'view_helper_config' => $this->getViewHelperConfig(),
            'htmx_config'        => $this->getHtmxConfig(),
        ];
    }

    public function getAxleusConfig(): array
    {
        return [
            'enable' => true,
        ];
    }

    public function getDependencies(): array
    {
        return [
            'factories' => [
                FilterServerRequestInterface::class => Request\HtmxFilter::class,
                //LaminasViewRenderer::class          => View\Renderer\RendererFactory::class,
                Middleware\HtmxMiddleware::class    => Middleware\HtmxMiddlewareFactory::class,
            ],
            'invokables' => [
                FilterServerRequestInterface::class => Request\HtmxFilter::class,
            ],
        ];
    }

    public function getViewHelpers(): array
    {
        return [
            'aliases'   => [
                'htmxTag' => View\Helper\HtmxTag::class,
            ],
            'delegators' => [
                LaminasFormHelper::class => [
                    Form\View\Helper\FormDelegatorFactory::class
                ],
            ],
            'invokables' => [
                View\Helper\HtmxTag::class => View\Helper\HtmxTag::class,
            ],
        ];
    }

    public function getViewHelperConfig(): array
    {
        return [];
    }

    /**
     * @link https://htmx.org/reference/#config
     * @return array
     */
    public function getHtmxConfig(): array
    {
        return [
            'enable'   => true,
            //'app_name' => 'Mastering Mezzio',
            'config'   => [
                'historyEnabled'          => true,
                'historyCacheSize'        => 10,
                'refreshOnHistoryMiss'    => false,
                'defaultSwapStyle'        => 'innerHtml',
                'defaultSettleDelay'      => 20,
                'includeIndicatorStyles'  => true,
                'indicatorClass'          => 'htmx-indicator',
                'requestClass'            => 'htmx-request',
                'addedClass'              => 'htmx-added',
                'settlingClass'           => 'htmx-settling',
                'swappingClass'           => 'htmx-swapping',
                'allowEval'               => true,
                'allowScriptTags'         => true,
                'inlineScriptNonce'       => '',
                'inlineStyleNonce'        => '',
                //'attributesToSettle'     => [],
                'wsReconnectDelay'        => 'full-jitter',
                'wsBinaryType'            => 'blob',
                'disableSelector'         => '[hx-disable], [data-hx-disable]',
                'withCredentials'         => false,
                'timeout'                 => 0,
                'scrollBehavior'          => 'instant',
                'defaultFocusScroll'      => false,
                'getCacheBusterParam'     => false,
                'globalViewTransitions'   => true,
                //'methodsThatUseUrlParams' => ['GET'],
                'selfRequestsOnly'        => true,
                'ignoreTitle'             => false,
                'scrollIntoViewOnBoost'   => true,
                'triggerSpecsCache'       => null,
                'allowNestedOobSwaps'     => true,
            ],
        ];
    }
}
